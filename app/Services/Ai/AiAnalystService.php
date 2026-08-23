<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Drives the Claude conversation for the admin analyst.
 *
 * The loop is written by hand rather than using the SDK tool runner because
 * write tools must pause mid-turn and round-trip through the browser for
 * human approval before they execute.
 */
class AiAnalystService
{
    private const MODEL = 'claude-opus-5';

    private const MAX_TOKENS = 16000;

    /** Guard against a tool loop that never converges. */
    private const MAX_STEPS = 12;

    private const STATE_TTL_MINUTES = 120;

    private Client $client;

    public function __construct(private AnalystToolkit $toolkit)
    {
        $this->client = new Client(apiKey: config('services.anthropic.key'));
    }

    public function isConfigured(): bool
    {
        return ! empty(config('services.anthropic.key'));
    }

    public function newConversationId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Send a user question and run the tool loop until Claude answers or asks
     * for approval on a write.
     *
     * @return array{status:string, conversation_id:string, ...}
     */
    public function ask(string $conversationId, string $question, int $actorId): array
    {
        $state = $this->loadState($conversationId);
        $state['messages'][] = ['role' => 'user', 'content' => $question];

        return $this->run($conversationId, $state, $actorId);
    }

    /**
     * Resume after the admin has approved or denied the pending write actions.
     *
     * @param  array<string,string>  $decisions  tool_use_id => 'approve'|'deny'
     */
    public function resume(string $conversationId, array $decisions, int $actorId): array
    {
        $state = $this->loadState($conversationId);

        if (empty($state['pending'])) {
            return ['status' => 'error', 'conversation_id' => $conversationId, 'message' => 'There are no actions awaiting approval.'];
        }

        // Read-tool results computed before the pause are replayed in the same
        // user turn — the API requires one tool_result per tool_use block.
        $results = $state['held_results'];

        foreach ($state['pending'] as $p) {
            $decision = $decisions[$p['id']] ?? 'deny';

            if ($decision === 'approve') {
                $payload = $this->toolkit->execute($p['name'], $p['input'], $actorId);
            } else {
                $payload = json_encode([
                    'executed' => false,
                    'reason' => 'The admin declined this action. Do not retry it. Acknowledge and continue with what remains.',
                ]);
            }

            $results[] = [
                'type' => 'tool_result',
                'tool_use_id' => $p['id'],
                'content' => $payload,
            ];
        }

        $state['messages'][] = ['role' => 'user', 'content' => $results];
        $state['pending'] = [];
        $state['held_results'] = [];

        return $this->run($conversationId, $state, $actorId);
    }

    public function reset(string $conversationId): void
    {
        Cache::forget($this->key($conversationId));
    }

    /**
     * @return array<int, array{role:string, text:string}>
     */
    public function transcript(string $conversationId): array
    {
        $state = $this->loadState($conversationId);
        $out = [];

        foreach ($state['messages'] as $m) {
            $text = $this->plainText($m['content']);
            if ($text === '') {
                continue;
            }

            $out[] = [
                'role' => $m['role'],
                'text' => $text,
                // User turns stay literal — only the assistant writes markdown.
                'html' => $m['role'] === 'assistant' ? $this->renderMarkdown($text) : null,
            ];
        }

        return $out;
    }

    // ------------------------------------------------------------------

    private function run(string $conversationId, array $state, int $actorId): array
    {
        $toolsUsed = [];

        for ($step = 0; $step < self::MAX_STEPS; $step++) {
            try {
                $response = $this->client->messages->create(
                    maxTokens: self::MAX_TOKENS,
                    messages: $state['messages'],
                    model: self::MODEL,
                    system: $this->systemPrompt(),
                    thinking: ['type' => 'adaptive'],
                    tools: $this->toolkit->definitions(),
                );
            } catch (\Throwable $e) {
                Log::error('AI analyst request failed', ['error' => $e->getMessage()]);
                $this->saveState($conversationId, $state);

                return [
                    'status' => 'error',
                    'conversation_id' => $conversationId,
                    'message' => 'Could not reach the AI service: '.$e->getMessage(),
                ];
            }

            // Echo the assistant turn back verbatim — thinking blocks and
            // tool_use blocks must survive the round trip unmodified.
            $assistantContent = array_map(
                fn ($block) => $block->jsonSerialize(),
                $response->content,
            );
            $state['messages'][] = ['role' => 'assistant', 'content' => $assistantContent];

            if ($response->stopReason === 'refusal') {
                $this->saveState($conversationId, $state);

                return [
                    'status' => 'error',
                    'conversation_id' => $conversationId,
                    'message' => 'The AI declined to answer that request. Rephrasing it usually helps.',
                ];
            }

            if ($response->stopReason !== 'tool_use') {
                $this->saveState($conversationId, $state);
                $answer = $this->plainText($assistantContent);

                if ($response->stopReason === 'max_tokens' && $answer !== '') {
                    $answer .= "\n\n_(Response was cut short at the length limit — ask for a narrower slice to see the rest.)_";
                }

                $answer = $answer !== '' ? $answer : '(The model returned no text. Try rephrasing.)';

                return [
                    'status' => 'answer',
                    'conversation_id' => $conversationId,
                    'answer' => $answer,
                    'answer_html' => $this->renderMarkdown($answer),
                    'tools_used' => $toolsUsed,
                ];
            }

            $calls = array_values(array_filter(
                $assistantContent,
                fn ($b) => ($b['type'] ?? null) === 'tool_use',
            ));

            $results = [];
            $pending = [];

            foreach ($calls as $call) {
                $name = $call['name'];
                $input = $call['input'] ?? [];

                if ($this->toolkit->isWriteTool($name)) {
                    $pending[] = [
                        'id' => $call['id'],
                        'name' => $name,
                        'input' => $input,
                        'summary' => $this->toolkit->describeWriteAction($name, $input),
                        'reason' => $input['reason'] ?? null,
                    ];

                    continue;
                }

                $toolsUsed[] = $name;
                $results[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $call['id'],
                    'content' => $this->toolkit->execute($name, $input, $actorId),
                ];
            }

            if ($pending !== []) {
                // Hold the read results until every write in this turn has been
                // decided; the API wants them all in one user message.
                $state['pending'] = $pending;
                $state['held_results'] = $results;
                $this->saveState($conversationId, $state);

                $preamble = $this->plainText($assistantContent);

                return [
                    'status' => 'needs_confirmation',
                    'conversation_id' => $conversationId,
                    'preamble' => $preamble,
                    'preamble_html' => $this->renderMarkdown($preamble),
                    'pending' => $pending,
                    'tools_used' => $toolsUsed,
                ];
            }

            $state['messages'][] = ['role' => 'user', 'content' => $results];
        }

        $this->saveState($conversationId, $state);

        return [
            'status' => 'error',
            'conversation_id' => $conversationId,
            'message' => 'Stopped after '.self::MAX_STEPS.' tool steps without a final answer. Try narrowing the question.',
        ];
    }

    /**
     * Stable instructions + schema first (cached), volatile clock second.
     */
    private function systemPrompt(): array
    {
        return [
            [
                'type' => 'text',
                'text' => $this->instructions(),
                'cacheControl' => ['type' => 'ephemeral'],
            ],
            [
                'type' => 'text',
                'text' => 'Current date and time: '.now()->format('l, j F Y, H:i').' ('.config('app.timezone').').',
            ],
        ];
    }

    private function instructions(): string
    {
        return <<<'PROMPT'
You are the in-house analyst for Waka Line Logistics, a bike-courier and delivery
company operating in Lagos, Nigeria. You are talking to an administrator inside
the company's admin dashboard. Currency is the Nigerian Naira (₦).

## How to answer

Answer from data you have actually queried. Never estimate, guess, or carry a
number over from memory — if you have not called a tool for a figure in this
conversation, call one. If a question cannot be answered with the tools you
have, say so plainly and describe what data would be needed.

Lead with the answer. Give the number or finding in the first sentence, then the
supporting detail. Keep responses tight: a simple question gets a direct sentence,
not headers and sections. Use a table only for genuinely tabular results, and
keep explanation in the surrounding prose rather than in cells.

Prefer aggregate_orders over pulling rows and counting them yourself. Combine
several tool calls in one turn when the question needs more than one angle —
they run in parallel.

State the window you measured ("in October", "over the last 30 days") so the
admin knows what the figure covers. When you compute a rate or a comparison,
show the two inputs, not just the result. If a result looks anomalous — a zero
where you expected volume, a sudden spike — say so rather than reporting it flat.

Do not pad answers with generic business advice. Recommendations are welcome
when the data supports a specific one, and should name the evidence.

## Domain notes

- Order status runs pending -> confirmed -> in_transit -> delivered, with
  cancelled as a terminal alternative.
- Revenue means the price of delivered orders, measured by delivery_date.
  Order volume means orders created, measured by created_at. Mixing these two
  is the most common way to get a wrong answer here — be deliberate about
  which date field a question is really asking about.
- Expenses are separate from orders and are dated by expense_date.
- Some customers are one-off senders identified only by customer_phone; others
  belong to a registered business client (client_id). "Customer" and "client"
  are not interchangeable — ask which one is meant if it matters.
- Clients can hold prepaid delivery credits, tracked as whole units.

## Actions

Four tools change data: assign_rider_to_order, update_order_status,
adjust_client_credits and create_expense. You cannot execute these yourself —
proposing one shows the admin an approval prompt, and it runs only if they
accept. So propose an action only when the admin has asked for it or the data
makes it clearly necessary, and always say in your message what you are about
to propose and why. Never propose a batch of speculative changes. If an action
is declined, accept it and move on without re-proposing.
PROMPT;
    }

    /**
     * Render an answer to HTML.
     *
     * This is the single renderer for both the live reply and the transcript
     * replayed on page load — doing it in two places is how the two ended up
     * disagreeing. Model output is untrusted, so raw HTML is stripped and
     * unsafe link schemes are refused.
     */
    public function renderMarkdown(string $text): string
    {
        if (trim($text) === '') {
            return '';
        }

        return Str::markdown($text, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'renderer' => ['soft_break' => "<br>\n"],
        ]);
    }

    /** Flatten message content down to displayable text. */
    private function plainText(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }
        if (! is_array($content)) {
            return '';
        }

        $parts = [];
        foreach ($content as $block) {
            if (($block['type'] ?? null) === 'text' && ! empty($block['text'])) {
                $parts[] = $block['text'];
            }
        }

        return trim(implode("\n\n", $parts));
    }

    private function loadState(string $conversationId): array
    {
        return Cache::get($this->key($conversationId), [
            'messages' => [],
            'pending' => [],
            'held_results' => [],
        ]);
    }

    private function saveState(string $conversationId, array $state): void
    {
        Cache::put($this->key($conversationId), $state, now()->addMinutes(self::STATE_TTL_MINUTES));
    }

    private function key(string $conversationId): string
    {
        return 'ai_analyst:'.$conversationId;
    }
}
