<?php

namespace App\Services\Ai;

use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\ClientCredit;
use App\Modules\Admin\Models\CreditTransaction;
use App\Modules\Admin\Models\Expense;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Models\SupportTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tool surface the AI analyst is allowed to touch.
 *
 * Read tools execute immediately. Write tools are declared here but never
 * executed by this class without the caller having already collected an
 * explicit human approval — see AiAnalystService.
 */
class AnalystToolkit
{
    /** Tools that mutate data and must be approved by the admin before running. */
    public const WRITE_TOOLS = [
        'assign_rider_to_order',
        'update_order_status',
        'adjust_client_credits',
        'create_expense',
    ];

    /** Hard ceiling on rows any single tool call can pull back. */
    private const MAX_ROWS = 500;

    public function isWriteTool(string $name): bool
    {
        return in_array($name, self::WRITE_TOOLS, true);
    }

    /**
     * Tool definitions in the shape the Messages API expects.
     * Kept in a stable order so the prompt prefix stays cacheable.
     */
    public function definitions(): array
    {
        return [
            [
                'name' => 'query_orders',
                'description' => 'List individual delivery orders with filters. Use when the question is about specific orders or you need to inspect rows. For counts, sums and averages prefer aggregate_orders — it is far cheaper. Returns matching rows plus totals for the whole filtered set (not just the returned page).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => ['type' => 'string', 'enum' => ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'], 'description' => 'Filter by order status.'],
                        'date_field' => ['type' => 'string', 'enum' => ['created_at', 'delivery_date', 'pickup_date'], 'description' => 'Which date column the date range applies to. Use delivery_date for revenue questions, created_at for order-volume questions. Defaults to created_at.'],
                        'date_from' => ['type' => 'string', 'description' => 'Inclusive start date, YYYY-MM-DD.'],
                        'date_to' => ['type' => 'string', 'description' => 'Inclusive end date, YYYY-MM-DD.'],
                        'rider_id' => ['type' => 'integer', 'description' => 'Only orders assigned to this rider.'],
                        'client_id' => ['type' => 'integer', 'description' => 'Only orders belonging to this business client.'],
                        'customer_phone' => ['type' => 'string', 'description' => 'Exact or partial customer phone number.'],
                        'search' => ['type' => 'string', 'description' => 'Free text match against order number, customer name, addresses and item description.'],
                        'city' => ['type' => 'string', 'description' => 'Matches either pickup_city or delivery_city.'],
                        'priority_level' => ['type' => 'string', 'description' => 'e.g. normal, high, urgent.'],
                        'unassigned' => ['type' => 'boolean', 'description' => 'True to return only orders with no rider assigned.'],
                        'limit' => ['type' => 'integer', 'description' => 'Rows to return, max 500. Defaults to 25.'],
                    ],
                ],
            ],
            [
                'name' => 'aggregate_orders',
                'description' => 'Group and aggregate orders — the primary tool for analytics questions (revenue by month, orders per rider, delivery rate by city, busiest hour, and so on). Always prefer this over pulling rows and counting them yourself.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'group_by' => ['type' => 'string', 'enum' => ['status', 'rider', 'client', 'delivery_city', 'pickup_city', 'payment_method', 'priority_level', 'day', 'week', 'month', 'hour_of_day', 'day_of_week', 'customer'], 'description' => 'Dimension to group on. "hour_of_day" and "day_of_week" are derived from the chosen date_field.'],
                        'status' => ['type' => 'string', 'enum' => ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'], 'description' => 'Restrict to one status before grouping. Use "delivered" for revenue figures.'],
                        'date_field' => ['type' => 'string', 'enum' => ['created_at', 'delivery_date', 'pickup_date'], 'description' => 'Defaults to created_at.'],
                        'date_from' => ['type' => 'string', 'description' => 'Inclusive start date, YYYY-MM-DD.'],
                        'date_to' => ['type' => 'string', 'description' => 'Inclusive end date, YYYY-MM-DD.'],
                        'rider_id' => ['type' => 'integer'],
                        'client_id' => ['type' => 'integer'],
                        'order_by' => ['type' => 'string', 'enum' => ['revenue', 'order_count', 'group'], 'description' => 'Sort key. Defaults to revenue for value questions, group for time series.'],
                        'direction' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                        'limit' => ['type' => 'integer', 'description' => 'Max groups to return, default 50.'],
                    ],
                    'required' => ['group_by'],
                ],
            ],
            [
                'name' => 'financial_summary',
                'description' => 'Revenue, expenses, profit and margin for a date range, with the equivalent figures for the immediately preceding period of the same length so you can state growth. Revenue counts delivered orders by delivery_date; expenses count by expense_date.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'date_from' => ['type' => 'string', 'description' => 'Inclusive start date, YYYY-MM-DD.'],
                        'date_to' => ['type' => 'string', 'description' => 'Inclusive end date, YYYY-MM-DD.'],
                    ],
                    'required' => ['date_from', 'date_to'],
                ],
            ],
            [
                'name' => 'query_riders',
                'description' => 'List riders with their delivery counts and revenue generated inside an optional date window. Use to answer questions about rider performance, utilisation, idle riders or headcount.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => ['type' => 'string', 'description' => 'e.g. active, inactive, suspended.'],
                        'search' => ['type' => 'string', 'description' => 'Match name, phone, email or vehicle number.'],
                        'date_from' => ['type' => 'string', 'description' => 'Scope the delivery/revenue stats to this range, YYYY-MM-DD.'],
                        'date_to' => ['type' => 'string'],
                        'has_active_orders' => ['type' => 'boolean', 'description' => 'True to return only riders currently holding pending/confirmed/in_transit orders.'],
                        'limit' => ['type' => 'integer', 'description' => 'Default 50, max 500.'],
                    ],
                ],
            ],
            [
                'name' => 'query_clients',
                'description' => 'List business clients with order counts, spend, credit balance and subscription state. Use for questions about accounts, churn risk, top customers or credit exposure.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => ['type' => 'string', 'description' => 'Match name, company, contact person, phone or email.'],
                        'is_active' => ['type' => 'boolean'],
                        'date_from' => ['type' => 'string', 'description' => 'Scope order/spend stats to this range, YYYY-MM-DD.'],
                        'date_to' => ['type' => 'string'],
                        'order_by' => ['type' => 'string', 'enum' => ['total_spent', 'order_count', 'created_at', 'name'], 'description' => 'Defaults to total_spent.'],
                        'limit' => ['type' => 'integer', 'description' => 'Default 25, max 500.'],
                    ],
                ],
            ],
            [
                'name' => 'query_expenses',
                'description' => 'Expenses, either as individual rows or grouped by category. Use for cost questions and expense breakdowns.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'group_by_category' => ['type' => 'boolean', 'description' => 'True returns one row per category with totals instead of individual expenses.'],
                        'category' => ['type' => 'string'],
                        'date_from' => ['type' => 'string', 'description' => 'Inclusive, YYYY-MM-DD.'],
                        'date_to' => ['type' => 'string'],
                        'search' => ['type' => 'string', 'description' => 'Match description, vendor or receipt number.'],
                        'limit' => ['type' => 'integer', 'description' => 'Default 50, max 500.'],
                    ],
                ],
            ],
            [
                'name' => 'query_credit_transactions',
                'description' => 'Credit ledger movements for prepaid clients — purchases, deductions, refunds and manual adjustments. Use for questions about credit usage or billing disputes.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'client_id' => ['type' => 'integer'],
                        'type' => ['type' => 'string', 'description' => 'Transaction type, e.g. purchase, deduction, refund, adjustment.'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                        'limit' => ['type' => 'integer', 'description' => 'Default 50, max 500.'],
                    ],
                ],
            ],
            [
                'name' => 'query_support_tickets',
                'description' => 'Support tickets with status and age. Use for questions about the support backlog or response times.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => ['type' => 'string', 'description' => 'e.g. open, pending, resolved, closed.'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                        'limit' => ['type' => 'integer', 'description' => 'Default 50, max 500.'],
                    ],
                ],
            ],
            [
                'name' => 'run_readonly_query',
                'description' => 'Escape hatch for questions the typed tools above cannot express — unusual joins, correlations, window-style comparisons. Accepts a single read-only SELECT statement against the application database and returns up to 500 rows. Anything other than a lone SELECT is rejected. Reach for a typed tool first; only use this when none of them fit.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'sql' => ['type' => 'string', 'description' => 'One SELECT statement. No semicolons, no CTE-wrapped writes, no multiple statements. A LIMIT is appended if you omit one.'],
                        'purpose' => ['type' => 'string', 'description' => 'One line on what this query answers. Recorded in the audit log.'],
                    ],
                    'required' => ['sql', 'purpose'],
                ],
            ],

            // ---- Write tools. Never executed without explicit admin approval. ----
            [
                'name' => 'assign_rider_to_order',
                'description' => 'Assign a rider to an order. Requires the admin to approve before it runs, so state plainly why you are proposing it.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'order_id' => ['type' => 'integer'],
                        'rider_id' => ['type' => 'integer'],
                        'reason' => ['type' => 'string', 'description' => 'Why this rider for this order. Shown to the admin on the approval prompt.'],
                    ],
                    'required' => ['order_id', 'rider_id', 'reason'],
                ],
            ],
            [
                'name' => 'update_order_status',
                'description' => 'Change an order status. Requires admin approval before it runs.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'order_id' => ['type' => 'integer'],
                        'status' => ['type' => 'string', 'enum' => ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled']],
                        'reason' => ['type' => 'string', 'description' => 'Why the status should change. Shown to the admin on the approval prompt.'],
                    ],
                    'required' => ['order_id', 'status', 'reason'],
                ],
            ],
            [
                'name' => 'adjust_client_credits',
                'description' => 'Add or remove delivery credits on a client account. Credits are whole units. Requires admin approval before it runs.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'client_id' => ['type' => 'integer'],
                        'credits' => ['type' => 'integer', 'description' => 'Positive to add credits, negative to deduct.'],
                        'reason' => ['type' => 'string', 'description' => 'Justification. Stored on the ledger entry and shown to the admin.'],
                    ],
                    'required' => ['client_id', 'credits', 'reason'],
                ],
            ],
            [
                'name' => 'create_expense',
                'description' => 'Record a business expense. Requires admin approval before it runs.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'category' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'amount' => ['type' => 'number', 'description' => 'Amount in Naira.'],
                        'expense_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD.'],
                        'vendor_name' => ['type' => 'string'],
                    ],
                    'required' => ['category', 'description', 'amount', 'expense_date'],
                ],
            ],
        ];
    }

    /**
     * Human-readable one-liner for the approval prompt, resolved against real
     * records so the admin sees names rather than bare IDs.
     */
    public function describeWriteAction(string $name, array $input): string
    {
        return match ($name) {
            'assign_rider_to_order' => sprintf(
                'Assign rider %s to order %s',
                $this->riderLabel($input['rider_id'] ?? null),
                $this->orderLabel($input['order_id'] ?? null),
            ),
            'update_order_status' => sprintf(
                'Set order %s to "%s"',
                $this->orderLabel($input['order_id'] ?? null),
                $input['status'] ?? '?',
            ),
            'adjust_client_credits' => sprintf(
                '%s %s credits %s %s',
                ($input['credits'] ?? 0) >= 0 ? 'Add' : 'Deduct',
                number_format(abs((int) ($input['credits'] ?? 0))),
                ($input['credits'] ?? 0) >= 0 ? 'to' : 'from',
                $this->clientLabel($input['client_id'] ?? null),
            ),
            'create_expense' => sprintf(
                'Record a ₦%s %s expense dated %s',
                number_format($input['amount'] ?? 0, 2),
                $input['category'] ?? 'uncategorised',
                $input['expense_date'] ?? '?',
            ),
            default => $name,
        };
    }

    /**
     * Run a tool and return a string payload for the tool_result block.
     * Write tools reaching this method have already been approved upstream.
     */
    public function execute(string $name, array $input, int $actorId): string
    {
        try {
            $result = match ($name) {
                'query_orders' => $this->queryOrders($input),
                'aggregate_orders' => $this->aggregateOrders($input),
                'financial_summary' => $this->financialSummary($input),
                'query_riders' => $this->queryRiders($input),
                'query_clients' => $this->queryClients($input),
                'query_expenses' => $this->queryExpenses($input),
                'query_credit_transactions' => $this->queryCreditTransactions($input),
                'query_support_tickets' => $this->querySupportTickets($input),
                'run_readonly_query' => $this->runReadonlyQuery($input),
                'assign_rider_to_order' => $this->assignRider($input, $actorId),
                'update_order_status' => $this->updateOrderStatus($input, $actorId),
                'adjust_client_credits' => $this->adjustCredits($input, $actorId),
                'create_expense' => $this->createExpense($input, $actorId),
                default => ['error' => "Unknown tool: {$name}"],
            };
        } catch (\Throwable $e) {
            Log::warning('AI analyst tool failed', ['tool' => $name, 'input' => $input, 'error' => $e->getMessage()]);

            return json_encode(['error' => $e->getMessage()]);
        }

        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    // ------------------------------------------------------------------
    // Read tools
    // ------------------------------------------------------------------

    private function queryOrders(array $in): array
    {
        $q = Order::query();
        $this->applyOrderFilters($q, $in);

        $totals = (clone $q)
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(price), 0) as revenue, COALESCE(AVG(price), 0) as avg_order_value')
            ->first();

        $rows = $q->with(['rider:id,name', 'client:id,name,company_name'])
            ->orderByDesc('created_at')
            ->limit($this->limit($in, 25))
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'status' => $o->status,
                'price' => (float) $o->price,
                'customer_name' => $o->customer_name,
                'customer_phone' => $o->customer_phone,
                'pickup_city' => $o->pickup_city,
                'delivery_city' => $o->delivery_city,
                'priority_level' => $o->priority_level,
                'rider' => $o->rider?->name,
                'rider_id' => $o->rider_id,
                'client' => $o->client?->company_name ?: $o->client?->name,
                'client_id' => $o->client_id,
                'created_at' => (string) $o->created_at,
                'pickup_date' => (string) $o->pickup_date,
                'delivery_date' => (string) $o->delivery_date,
            ]);

        return [
            'totals_for_full_filter' => [
                'order_count' => (int) $totals->order_count,
                'revenue' => round((float) $totals->revenue, 2),
                'avg_order_value' => round((float) $totals->avg_order_value, 2),
            ],
            'returned_rows' => $rows->count(),
            'orders' => $rows,
        ];
    }

    private function aggregateOrders(array $in): array
    {
        $dateField = $this->dateField($in);
        $q = Order::query();
        $this->applyOrderFilters($q, $in);

        [$select, $groupSql, $label] = match ($in['group_by']) {
            'status' => ['status as grp', 'status', 'status'],
            'delivery_city' => ['delivery_city as grp', 'delivery_city', 'delivery_city'],
            'pickup_city' => ['pickup_city as grp', 'pickup_city', 'pickup_city'],
            'payment_method' => ['payment_method as grp', 'payment_method', 'payment_method'],
            'priority_level' => ['priority_level as grp', 'priority_level', 'priority_level'],
            'customer' => ['customer_phone as grp', 'customer_phone', 'customer_phone'],
            'rider' => ['rider_id as grp', 'rider_id', 'rider_id'],
            'client' => ['client_id as grp', 'client_id', 'client_id'],
            'day' => ["DATE({$dateField}) as grp", "DATE({$dateField})", 'day'],
            'week' => ["DATE_FORMAT({$dateField}, '%x-W%v') as grp", "DATE_FORMAT({$dateField}, '%x-W%v')", 'week'],
            'month' => ["DATE_FORMAT({$dateField}, '%Y-%m') as grp", "DATE_FORMAT({$dateField}, '%Y-%m')", 'month'],
            'hour_of_day' => ["HOUR({$dateField}) as grp", "HOUR({$dateField})", 'hour_of_day'],
            'day_of_week' => ["DAYNAME({$dateField}) as grp", "DAYNAME({$dateField})", 'day_of_week'],
            default => throw new \InvalidArgumentException("Unsupported group_by: {$in['group_by']}"),
        };

        $orderBy = $in['order_by'] ?? (in_array($in['group_by'], ['day', 'week', 'month', 'hour_of_day'], true) ? 'group' : 'revenue');
        $direction = ($in['direction'] ?? ($orderBy === 'group' ? 'asc' : 'desc')) === 'asc' ? 'asc' : 'desc';

        $rows = $q->selectRaw("{$select}, COUNT(*) as order_count, COALESCE(SUM(price), 0) as revenue, COALESCE(AVG(price), 0) as avg_order_value")
            ->groupBy(DB::raw($groupSql))
            ->orderBy(DB::raw($orderBy === 'group' ? $groupSql : $orderBy), $direction)
            ->limit($this->limit($in, 50))
            ->get();

        // Resolve rider/client IDs to names in one extra query rather than N.
        $names = [];
        if ($in['group_by'] === 'rider') {
            $names = Rider::whereIn('id', $rows->pluck('grp')->filter())->pluck('name', 'id')->all();
        } elseif ($in['group_by'] === 'client') {
            $names = Client::whereIn('id', $rows->pluck('grp')->filter())
                ->get(['id', 'name', 'company_name'])
                ->mapWithKeys(fn ($c) => [$c->id => $c->company_name ?: $c->name])
                ->all();
        }

        return [
            'grouped_by' => $label,
            'date_field' => $dateField,
            'groups' => $rows->map(fn ($r) => array_filter([
                'group' => $r->grp,
                'name' => $names[$r->grp] ?? null,
                'order_count' => (int) $r->order_count,
                'revenue' => round((float) $r->revenue, 2),
                'avg_order_value' => round((float) $r->avg_order_value, 2),
            ], fn ($v) => $v !== null)),
        ];
    }

    private function financialSummary(array $in): array
    {
        $from = \Carbon\Carbon::parse($in['date_from'])->startOfDay();
        $to = \Carbon\Carbon::parse($in['date_to'])->endOfDay();

        $lengthDays = $from->diffInDays($to);
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subDays($lengthDays)->startOfDay();

        $period = fn ($start, $end) => [
            'revenue' => round((float) Order::whereBetween('delivery_date', [$start, $end])
                ->where('status', 'delivered')->sum('price'), 2),
            'expenses' => round((float) Expense::whereBetween('expense_date', [$start, $end])->sum('amount'), 2),
            'delivered_orders' => Order::whereBetween('delivery_date', [$start, $end])
                ->where('status', 'delivered')->count(),
            'orders_created' => Order::whereBetween('created_at', [$start, $end])->count(),
        ];

        $current = $period($from, $to);
        $previous = $period($prevFrom, $prevTo);

        foreach ([&$current, &$previous] as &$p) {
            $p['profit'] = round($p['revenue'] - $p['expenses'], 2);
            $p['margin_percent'] = $p['revenue'] > 0 ? round($p['profit'] / $p['revenue'] * 100, 2) : null;
        }
        unset($p);

        $growth = fn ($now, $before) => $before > 0 ? round(($now - $before) / $before * 100, 2) : null;

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'current' => $current,
            'previous_period' => ['from' => $prevFrom->toDateString(), 'to' => $prevTo->toDateString()] + $previous,
            'change_percent' => [
                'revenue' => $growth($current['revenue'], $previous['revenue']),
                'expenses' => $growth($current['expenses'], $previous['expenses']),
                'profit' => $growth($current['profit'], $previous['profit']),
            ],
            'note' => 'null in change_percent means the previous period had a zero base, so growth is undefined rather than 0.',
        ];
    }

    private function queryRiders(array $in): array
    {
        $from = isset($in['date_from']) ? \Carbon\Carbon::parse($in['date_from'])->startOfDay() : null;
        $to = isset($in['date_to']) ? \Carbon\Carbon::parse($in['date_to'])->endOfDay() : null;

        $scope = function ($q) use ($from, $to) {
            $q->where('status', 'delivered');
            if ($from && $to) {
                $q->whereBetween('delivery_date', [$from, $to]);
            }
        };

        $q = Rider::query()
            ->withCount(['orders as delivered_orders' => $scope])
            ->withSum(['orders as revenue' => $scope], 'price')
            ->withCount(['orders as active_orders' => fn ($q) => $q->whereIn('status', ['pending', 'confirmed', 'in_transit'])]);

        if (! empty($in['status'])) {
            $q->where('status', $in['status']);
        }
        if (! empty($in['search'])) {
            $s = $in['search'];
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('vehicle_number', 'like', "%{$s}%"));
        }
        if (! empty($in['has_active_orders'])) {
            $q->whereHas('orders', fn ($h) => $h->whereIn('status', ['pending', 'confirmed', 'in_transit']));
        }

        return [
            'date_scope' => $from && $to ? ['from' => $from->toDateString(), 'to' => $to->toDateString()] : 'all time',
            'riders' => $q->orderByDesc('delivered_orders')->limit($this->limit($in, 50))->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'phone' => $r->phone,
                    'status' => $r->status,
                    'vehicle_type' => $r->vehicle_type,
                    'rating' => $r->rating,
                    'delivered_orders' => (int) $r->delivered_orders,
                    'revenue' => round((float) ($r->revenue ?? 0), 2),
                    'active_orders' => (int) $r->active_orders,
                ]),
        ];
    }

    private function queryClients(array $in): array
    {
        $from = isset($in['date_from']) ? \Carbon\Carbon::parse($in['date_from'])->startOfDay() : null;
        $to = isset($in['date_to']) ? \Carbon\Carbon::parse($in['date_to'])->endOfDay() : null;

        $scope = function ($q) use ($from, $to) {
            if ($from && $to) {
                $q->whereBetween('created_at', [$from, $to]);
            }
        };

        $q = Client::query()
            ->withCount(['orders as order_count' => $scope])
            ->withSum(['orders as total_spent' => function ($q) use ($from, $to) {
                $q->where('status', 'delivered');
                if ($from && $to) {
                    $q->whereBetween('delivery_date', [$from, $to]);
                }
            }], 'price');

        if (isset($in['is_active'])) {
            $q->where('is_active', (bool) $in['is_active']);
        }
        if (! empty($in['search'])) {
            $s = $in['search'];
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                ->orWhere('company_name', 'like', "%{$s}%")
                ->orWhere('contact_person', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"));
        }

        $orderBy = match ($in['order_by'] ?? 'total_spent') {
            'order_count' => 'order_count',
            'created_at' => 'created_at',
            'name' => 'name',
            default => 'total_spent',
        };

        $credits = ClientCredit::pluck('available_credits', 'client_id')->all();

        return [
            'date_scope' => $from && $to ? ['from' => $from->toDateString(), 'to' => $to->toDateString()] : 'all time',
            'clients' => $q->orderByDesc($orderBy)->limit($this->limit($in, 25))->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->company_name ?: $c->name,
                    'contact_person' => $c->contact_person,
                    'phone' => $c->phone,
                    'email' => $c->email,
                    'city' => $c->city,
                    'is_active' => (bool) $c->is_active,
                    'order_count' => (int) $c->order_count,
                    'total_spent' => round((float) ($c->total_spent ?? 0), 2),
                    'available_credits' => isset($credits[$c->id]) ? (int) $credits[$c->id] : null,
                    'onboarded' => (string) $c->created_at,
                    'last_login_at' => (string) $c->last_login_at,
                ]),
        ];
    }

    private function queryExpenses(array $in): array
    {
        $q = Expense::query();
        if (! empty($in['category'])) {
            $q->where('category', $in['category']);
        }
        if (! empty($in['date_from'])) {
            $q->where('expense_date', '>=', \Carbon\Carbon::parse($in['date_from'])->startOfDay());
        }
        if (! empty($in['date_to'])) {
            $q->where('expense_date', '<=', \Carbon\Carbon::parse($in['date_to'])->endOfDay());
        }
        if (! empty($in['search'])) {
            $s = $in['search'];
            $q->where(fn ($w) => $w->where('description', 'like', "%{$s}%")
                ->orWhere('vendor_name', 'like', "%{$s}%")
                ->orWhere('receipt_number', 'like', "%{$s}%"));
        }

        $total = round((float) (clone $q)->sum('amount'), 2);

        if (! empty($in['group_by_category'])) {
            return [
                'total_expenses' => $total,
                'by_category' => $q->selectRaw('category, SUM(amount) as amount, COUNT(*) as entries')
                    ->groupBy('category')->orderByDesc('amount')->get()
                    ->map(fn ($r) => [
                        'category' => $r->category,
                        'amount' => round((float) $r->amount, 2),
                        'entries' => (int) $r->entries,
                        'percent_of_total' => $total > 0 ? round($r->amount / $total * 100, 2) : 0,
                    ]),
            ];
        }

        return [
            'total_expenses' => $total,
            'expenses' => $q->orderByDesc('expense_date')->limit($this->limit($in, 50))->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'category' => $e->category,
                    'description' => $e->description,
                    'amount' => round((float) $e->amount, 2),
                    'expense_date' => (string) $e->expense_date,
                    'vendor_name' => $e->vendor_name,
                    'payment_method' => $e->payment_method,
                ]),
        ];
    }

    private function queryCreditTransactions(array $in): array
    {
        $q = CreditTransaction::query();
        if (! empty($in['client_id'])) {
            $q->where('client_id', $in['client_id']);
        }
        if (! empty($in['type'])) {
            $q->where('type', $in['type']);
        }
        if (! empty($in['date_from'])) {
            $q->where('created_at', '>=', \Carbon\Carbon::parse($in['date_from'])->startOfDay());
        }
        if (! empty($in['date_to'])) {
            $q->where('created_at', '<=', \Carbon\Carbon::parse($in['date_to'])->endOfDay());
        }

        return [
            'note' => '"credits" is signed: negative means credits left the account.',
            'transactions' => $q->orderByDesc('created_at')->limit($this->limit($in, 50))->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'reference' => $t->transaction_reference,
                    'client_id' => $t->client_id,
                    'type' => $t->type,
                    'status' => $t->status,
                    'credits' => (int) $t->credits,
                    'balance_before' => (int) $t->balance_before,
                    'balance_after' => (int) $t->balance_after,
                    'amount_paid' => $t->amount_paid !== null ? round((float) $t->amount_paid, 2) : null,
                    'order_id' => $t->order_id,
                    'description' => $t->description,
                    'created_at' => (string) $t->created_at,
                ]),
        ];
    }

    private function querySupportTickets(array $in): array
    {
        $q = SupportTicket::query();
        if (! empty($in['status'])) {
            $q->where('status', $in['status']);
        }
        if (! empty($in['date_from'])) {
            $q->where('created_at', '>=', \Carbon\Carbon::parse($in['date_from'])->startOfDay());
        }
        if (! empty($in['date_to'])) {
            $q->where('created_at', '<=', \Carbon\Carbon::parse($in['date_to'])->endOfDay());
        }

        return [
            'by_status' => SupportTicket::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'tickets' => $q->orderByDesc('created_at')->limit($this->limit($in, 50))->get()->map(fn ($t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
                'status' => $t->status,
                'priority' => $t->priority,
                'category' => $t->category,
                'client_id' => $t->client_id,
                'created_at' => (string) $t->created_at,
                'age_days' => $t->created_at?->diffInDays(now()),
                'resolved_at' => (string) $t->resolved_at,
            ]),
        ];
    }

    /**
     * Constrained SELECT-only escape hatch. Rejects anything that is not a
     * single read statement and caps the row count.
     */
    private function runReadonlyQuery(array $in): array
    {
        $sql = trim($in['sql']);
        $sql = rtrim($sql, "; \t\n\r");

        if (! preg_match('/^\s*(select|with)\s/i', $sql)) {
            return ['error' => 'Rejected: only SELECT statements are allowed.'];
        }
        if (str_contains($sql, ';')) {
            return ['error' => 'Rejected: multiple statements are not allowed.'];
        }
        if (preg_match('/\b(insert|update|delete|drop|alter|truncate|create|replace|grant|revoke|rename|lock|call|do|handler|load\s+data|into\s+outfile|into\s+dumpfile|load_file|sleep|benchmark)\b/i', $sql)) {
            return ['error' => 'Rejected: statement contains a disallowed keyword.'];
        }
        if (! preg_match('/\blimit\s+\d+/i', $sql)) {
            $sql .= ' LIMIT '.self::MAX_ROWS;
        }

        Log::info('AI analyst read-only SQL', ['purpose' => $in['purpose'], 'sql' => $sql]);

        $rows = DB::select($sql);
        if (count($rows) > self::MAX_ROWS) {
            $rows = array_slice($rows, 0, self::MAX_ROWS);
        }

        return ['executed_sql' => $sql, 'row_count' => count($rows), 'rows' => $rows];
    }

    // ------------------------------------------------------------------
    // Write tools — only reached after human approval
    // ------------------------------------------------------------------

    private function assignRider(array $in, int $actorId): array
    {
        $order = Order::findOrFail($in['order_id']);
        $rider = Rider::findOrFail($in['rider_id']);

        $previous = $order->rider_id;
        $order->rider_id = $rider->id;
        if ($order->status === 'pending') {
            $order->status = 'confirmed';
        }
        $order->save();

        $this->audit('assign_rider_to_order', $actorId, $in, "order {$order->order_number} -> rider {$rider->name}");

        return [
            'ok' => true,
            'order_number' => $order->order_number,
            'rider' => $rider->name,
            'previous_rider_id' => $previous,
            'status' => $order->status,
        ];
    }

    private function updateOrderStatus(array $in, int $actorId): array
    {
        $order = Order::findOrFail($in['order_id']);
        $from = $order->status;
        $order->status = $in['status'];

        if ($in['status'] === 'delivered' && ! $order->delivery_date) {
            $order->delivery_date = now();
        }
        $order->save();

        $this->audit('update_order_status', $actorId, $in, "order {$order->order_number}: {$from} -> {$in['status']}");

        return ['ok' => true, 'order_number' => $order->order_number, 'from' => $from, 'to' => $order->status];
    }

    private function adjustCredits(array $in, int $actorId): array
    {
        $client = Client::findOrFail($in['client_id']);
        $credits = (int) $in['credits'];

        if ($credits === 0) {
            return ['ok' => false, 'error' => 'Refused: adjustment of zero credits is a no-op.'];
        }

        return DB::transaction(function () use ($client, $credits, $in, $actorId) {
            $credit = $client->getOrCreateCredits();
            $before = (int) $credit->available_credits;

            if ($credits < 0 && $before < abs($credits)) {
                return ['ok' => false, 'error' => sprintf(
                    'Refused: client holds %d credits, cannot deduct %d.', $before, abs($credits)
                )];
            }

            $credits > 0
                ? $credit->addCredits($credits)
                : $credit->deductCredits(abs($credits));

            $after = (int) $credit->fresh()->available_credits;

            CreditTransaction::create([
                'client_id' => $client->id,
                'transaction_reference' => CreditTransaction::generateReference(),
                'type' => 'adjustment',
                'credits' => $credits,
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => $in['reason'],
                'processed_by' => $actorId,
                'metadata' => [
                    'adjustment_type' => $credits > 0 ? 'add' : 'deduct',
                    'admin_note' => $in['reason'],
                    'source' => 'ai_analyst_approved',
                ],
            ]);

            $this->audit('adjust_client_credits', $actorId, $in, "client {$client->id}: {$before} -> {$after}");

            return [
                'ok' => true,
                'client' => $client->company_name ?: $client->name,
                'balance_before' => $before,
                'balance_after' => $after,
            ];
        });
    }

    private function createExpense(array $in, int $actorId): array
    {
        $expense = Expense::create([
            'category' => $in['category'],
            'description' => $in['description'],
            'amount' => $in['amount'],
            'expense_date' => $in['expense_date'],
            'vendor_name' => $in['vendor_name'] ?? null,
            'notes' => 'Recorded via AI analyst, approved by admin.',
            'created_by' => $actorId,
        ]);

        $this->audit('create_expense', $actorId, $in, "expense {$expense->id}");

        return ['ok' => true, 'expense_id' => $expense->id, 'amount' => (float) $expense->amount];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function applyOrderFilters($q, array $in): void
    {
        $dateField = $this->dateField($in);

        if (! empty($in['status'])) {
            $q->where('status', $in['status']);
        }
        if (! empty($in['date_from'])) {
            $q->where($dateField, '>=', \Carbon\Carbon::parse($in['date_from'])->startOfDay());
        }
        if (! empty($in['date_to'])) {
            $q->where($dateField, '<=', \Carbon\Carbon::parse($in['date_to'])->endOfDay());
        }
        if ($dateField !== 'created_at' && (! empty($in['date_from']) || ! empty($in['date_to']))) {
            $q->whereNotNull($dateField);
        }
        if (! empty($in['rider_id'])) {
            $q->where('rider_id', $in['rider_id']);
        }
        if (! empty($in['client_id'])) {
            $q->where('client_id', $in['client_id']);
        }
        if (! empty($in['customer_phone'])) {
            $q->where('customer_phone', 'like', '%'.$in['customer_phone'].'%');
        }
        if (! empty($in['priority_level'])) {
            $q->where('priority_level', $in['priority_level']);
        }
        if (! empty($in['unassigned'])) {
            $q->whereNull('rider_id');
        }
        if (! empty($in['city'])) {
            $c = $in['city'];
            $q->where(fn ($w) => $w->where('pickup_city', 'like', "%{$c}%")->orWhere('delivery_city', 'like', "%{$c}%"));
        }
        if (! empty($in['search'])) {
            $s = $in['search'];
            $q->where(fn ($w) => $w->where('order_number', 'like', "%{$s}%")
                ->orWhere('customer_name', 'like', "%{$s}%")
                ->orWhere('pickup_address', 'like', "%{$s}%")
                ->orWhere('delivery_address', 'like', "%{$s}%")
                ->orWhere('item_description', 'like', "%{$s}%"));
        }
    }

    private function dateField(array $in): string
    {
        return in_array($in['date_field'] ?? '', ['created_at', 'delivery_date', 'pickup_date'], true)
            ? $in['date_field']
            : 'created_at';
    }

    private function limit(array $in, int $default): int
    {
        return max(1, min((int) ($in['limit'] ?? $default), self::MAX_ROWS));
    }

    private function orderLabel(?int $id): string
    {
        $o = $id ? Order::find($id) : null;

        return $o ? "{$o->order_number} (#{$o->id}, currently {$o->status})" : "#{$id} (not found)";
    }

    private function riderLabel(?int $id): string
    {
        $r = $id ? Rider::find($id) : null;

        return $r ? "{$r->name} (#{$r->id})" : "#{$id} (not found)";
    }

    private function clientLabel(?int $id): string
    {
        $c = $id ? Client::find($id) : null;

        return $c ? (($c->company_name ?: $c->name)." (#{$c->id})") : "#{$id} (not found)";
    }

    private function audit(string $tool, int $actorId, array $input, string $summary): void
    {
        Log::info('AI analyst write executed', [
            'tool' => $tool,
            'actor_user_id' => $actorId,
            'input' => $input,
            'summary' => $summary,
        ]);
    }
}
