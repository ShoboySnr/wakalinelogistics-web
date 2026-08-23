@extends('Admin::layout')

@section('title', 'Business Intelligence')

@section('content')
{{-- Tailwind's reset strips margins and list markers, so the markdown the
     analyst returns needs its own styling inside the answer bubble. --}}
<style>
    .ai-answer > *:first-child { margin-top: 0; }
    .ai-answer > *:last-child { margin-bottom: 0; }
    .ai-answer p { margin: 0 0 0.65rem; }
    .ai-answer strong { font-weight: 600; color: #111827; }
    .ai-answer em { font-style: italic; }
    .ai-answer ul, .ai-answer ol { margin: 0 0 0.65rem; padding-left: 1.25rem; }
    .ai-answer ul { list-style: disc; }
    .ai-answer ol { list-style: decimal; }
    .ai-answer li { margin: 0.15rem 0; }
    .ai-answer h1, .ai-answer h2, .ai-answer h3 {
        font-weight: 600; color: #111827; margin: 0.9rem 0 0.4rem; line-height: 1.3;
    }
    .ai-answer h1 { font-size: 1.05rem; }
    .ai-answer h2 { font-size: 1rem; }
    .ai-answer h3 { font-size: 0.925rem; }
    .ai-answer code {
        background: #f3f4f6; padding: 0.1rem 0.3rem; border-radius: 0.25rem;
        font-size: 0.8125rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    }
    .ai-answer pre {
        background: #f3f4f6; padding: 0.75rem; border-radius: 0.375rem;
        overflow-x: auto; margin: 0 0 0.65rem; font-size: 0.8125rem;
    }
    .ai-answer pre code { background: none; padding: 0; }
    .ai-answer a { color: #C1666B; text-decoration: underline; }
    .ai-answer blockquote {
        border-left: 3px solid #e5e7eb; padding-left: 0.75rem;
        margin: 0 0 0.65rem; color: #4b5563;
    }
    /* Tables can be wide — let them scroll rather than stretch the bubble. */
    .ai-answer table { display: block; overflow-x: auto; width: 100%; margin: 0 0 0.65rem; border-collapse: collapse; }
    .ai-answer th, .ai-answer td { border: 1px solid #e5e7eb; padding: 0.35rem 0.6rem; text-align: left; white-space: nowrap; }
    .ai-answer th { background: #f9fafb; font-weight: 600; }
</style>
<div class="px-4 sm:px-6 lg:px-0">

    <div class="mb-5 flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Business Intelligence</h1>
            <p class="text-sm text-gray-500 mt-1">Ask anything about orders, riders, clients, spend or performance.</p>
        </div>
        <form method="POST" action="{{ route('admin.business-intelligence.reset') }}">
            @csrf
            <button type="submit" class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 whitespace-nowrap">
                New conversation
            </button>
        </form>
    </div>

    <!-- Headline KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs font-medium text-gray-500">Revenue · {{ $kpis['month_label'] }}</p>
            <p class="text-xl font-bold text-gray-900 mt-1">₦{{ number_format($kpis['revenue'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs font-medium text-gray-500">Profit · {{ $kpis['month_label'] }}</p>
            <p class="text-xl font-bold {{ $kpis['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                ₦{{ number_format($kpis['profit'], 2) }}
            </p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs font-medium text-gray-500">Orders this month</p>
            <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($kpis['orders_this_month']) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ number_format($kpis['delivered_this_month']) }} delivered</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs font-medium text-gray-500">Unassigned</p>
            <p class="text-xl font-bold {{ $kpis['unassigned_orders'] > 0 ? 'text-orange-600' : 'text-gray-900' }} mt-1">
                {{ number_format($kpis['unassigned_orders']) }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">{{ number_format($kpis['pending_orders']) }} pending</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs font-medium text-gray-500">Active riders</p>
            <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($kpis['active_riders']) }}</p>
        </div>
    </div>

    @unless($aiConfigured)
    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm font-semibold text-yellow-900">AI analyst is not configured</p>
        <p class="text-xs text-yellow-700 mt-1">Set <code class="font-mono">ANTHROPIC_API_KEY</code> in the environment, then reload this page.</p>
    </div>
    @endunless

    <!-- Conversation -->
    <div class="bg-white rounded-lg shadow flex flex-col" style="min-height: 60vh;">
        <div id="chatLog" class="flex-1 overflow-y-auto p-5 space-y-4" style="max-height: 62vh;">

            @if(count($transcript) === 0)
            <div class="text-center py-10">
                <p class="text-sm text-gray-500 mb-4">Ask a question to get started.</p>
                <div class="flex flex-wrap justify-center gap-2 max-w-2xl mx-auto">
                    @foreach([
                        'How did revenue this month compare to last month?',
                        'Which riders delivered the most in the last 30 days?',
                        'What are our biggest expense categories this quarter?',
                        'Which orders are still unassigned?',
                        'What time of day do we get the most orders?',
                        'Which clients have not ordered in 60 days?',
                    ] as $suggestion)
                    <button type="button"
                            class="suggestion px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-full transition-colors">
                        {{ $suggestion }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            @foreach($transcript as $turn)
                @include('Admin::business-intelligence.partials.bubble', [
                    'role' => $turn['role'],
                    'text' => $turn['text'],
                    'html' => $turn['html'] ?? null,
                ])
            @endforeach
        </div>

        <!-- Composer -->
        <div class="border-t border-gray-200 p-4">
            <div id="pendingPanel" class="hidden mb-4"></div>

            <form id="askForm" class="flex gap-2 items-end">
                @csrf
                <textarea id="askInput" rows="1" placeholder="Ask about anything in the system…"
                          class="flex-1 resize-none px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-[#C1666B] focus:border-[#C1666B] text-sm"
                          @disabled(!$aiConfigured)></textarea>
                <button type="submit" id="askButton"
                        class="px-5 py-2.5 text-white rounded-md brand-accent-bg brand-accent-hover transition-colors text-sm font-medium disabled:opacity-50 whitespace-nowrap"
                        @disabled(!$aiConfigured)>
                    Ask
                </button>
            </form>
            <p class="text-xs text-gray-400 mt-2">
                Answers come from live queries against your data. Actions that change records always ask for your approval first.
            </p>
        </div>
    </div>
</div>

<script>
(function () {
    const log = document.getElementById('chatLog');
    const form = document.getElementById('askForm');
    const input = document.getElementById('askInput');
    const button = document.getElementById('askButton');
    const pendingPanel = document.getElementById('pendingPanel');
    const csrf = document.querySelector('#askForm input[name="_token"]').value;

    const routes = {
        ask: @json(route('admin.business-intelligence.ask')),
        confirm: @json(route('admin.business-intelligence.confirm')),
    };

    function scrollDown() {
        log.scrollTop = log.scrollHeight;
    }

    function clearEmptyState() {
        const empty = log.querySelector('.text-center.py-10');
        if (empty) empty.remove();
    }

    // Wrap a plain string (an error, a fallback) as safe assistant-bubble HTML.
    function plain(text) {
        return '<p>' + escapeHtml(String(text)).replace(/\n/g, '<br>') + '</p>';
    }

    function escapeHtml(text) {
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function bubble(role, html) {
        clearEmptyState();
        const wrap = document.createElement('div');
        wrap.className = role === 'user' ? 'flex justify-end' : 'flex justify-start';
        wrap.innerHTML = role === 'user'
            ? `<div class="max-w-[85%] bg-[#C1666B] text-white rounded-lg rounded-br-sm px-4 py-2.5 text-sm">${
                  escapeHtml(html).replace(/\n/g, '<br>')
              }</div>`
            : `<div class="ai-answer max-w-[90%] bg-gray-50 border border-gray-200 text-gray-800 rounded-lg rounded-bl-sm px-4 py-3 text-sm leading-relaxed">${html}</div>`;
        log.appendChild(wrap);
        scrollDown();
        return wrap;
    }

    function thinking() {
        clearEmptyState();
        const el = document.createElement('div');
        el.className = 'flex justify-start';
        el.innerHTML = `<div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
            <div class="flex gap-1.5 items-center">
                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                <span class="text-xs text-gray-500 ml-1.5">querying your data…</span>
            </div>
        </div>`;
        log.appendChild(el);
        scrollDown();
        return el;
    }

    function toolNote(tools) {
        if (!tools || !tools.length) return;
        const unique = [...new Set(tools)];
        const el = document.createElement('div');
        el.className = 'flex justify-start';
        el.innerHTML = `<p class="text-xs text-gray-400 pl-1">Ran: ${unique.join(', ')}</p>`;
        log.appendChild(el);
    }

    function setBusy(busy) {
        button.disabled = busy;
        input.disabled = busy;
        button.textContent = busy ? 'Working…' : 'Ask';
    }

    function showPending(pending) {
        const rows = pending.map(p => `
            <div class="border border-orange-200 bg-white rounded-md p-3 mb-2" data-tool-id="${p.id}">
                <p class="text-sm font-semibold text-gray-900">${
                    p.summary.replace(/&/g, '&amp;').replace(/</g, '&lt;')
                }</p>
                ${p.reason ? `<p class="text-xs text-gray-600 mt-1">${p.reason.replace(/&/g, '&amp;').replace(/</g, '&lt;')}</p>` : ''}
            </div>`).join('');

        pendingPanel.innerHTML = `
            <div class="border border-orange-300 bg-orange-50 rounded-lg p-4">
                <p class="text-sm font-semibold text-orange-900 mb-3">
                    ⚠️ ${pending.length === 1 ? 'This action will change your data' : `${pending.length} actions will change your data`}
                </p>
                ${rows}
                <div class="flex gap-2 mt-3">
                    <button type="button" id="approveAll" class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                        Approve${pending.length > 1 ? ' all' : ''}
                    </button>
                    <button type="button" id="denyAll" class="px-4 py-2 text-sm bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                        Decline
                    </button>
                </div>
            </div>`;
        pendingPanel.classList.remove('hidden');

        const decide = (verdict) => {
            const decisions = {};
            pending.forEach(p => { decisions[p.id] = verdict; });
            pendingPanel.classList.add('hidden');
            pendingPanel.innerHTML = '';
            send(routes.confirm, { decisions });
        };

        document.getElementById('approveAll').addEventListener('click', () => decide('approve'));
        document.getElementById('denyAll').addEventListener('click', () => decide('deny'));
    }

    async function send(url, payload) {
        setBusy(true);
        const spinner = thinking();

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();
            spinner.remove();

            if (data.status === 'answer') {
                toolNote(data.tools_used);
                bubble('assistant', data.answer_html || plain(data.answer || '(no answer returned)'));
            } else if (data.status === 'needs_confirmation') {
                toolNote(data.tools_used);
                if (data.preamble) bubble('assistant', data.preamble_html || plain(data.preamble));
                showPending(data.pending);
            } else {
                bubble('assistant', plain(data.message || 'Something went wrong.'));
            }
        } catch (err) {
            spinner.remove();
            bubble('assistant', plain('Request failed: ' + err.message));
        } finally {
            setBusy(false);
            input.focus();
        }
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        bubble('user', text);
        input.value = '';
        input.style.height = 'auto';
        send(routes.ask, { message: text });
    });

    // Enter sends, Shift+Enter newlines.
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 160) + 'px';
    });

    document.querySelectorAll('.suggestion').forEach(b => {
        b.addEventListener('click', () => {
            input.value = b.textContent.trim();
            form.requestSubmit();
        });
    });

    scrollDown();
})();
</script>
@endsection
