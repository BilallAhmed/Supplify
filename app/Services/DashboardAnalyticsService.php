<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductActivity;
use App\Models\ProductView;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    public function __construct(
        private readonly ProductViewTracker $tracker,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $tz = (string) config('app.timezone');
        $range = $this->resolveRange($request, $tz);
        $trackingStartedAt = $this->tracker->trackingStartedAt();
        $hasTracking = $trackingStartedAt !== null;

        $periodStart = $range['start'];
        $periodEnd = $range['end'];

        // Clamp reporting to when tracking actually began — never invent prior data.
        $effectiveStart = $hasTracking
            ? ($trackingStartedAt->greaterThan($periodStart) ? $trackingStartedAt->copy() : $periodStart->copy())
            : null;

        $productFilterId = $request->filled('product_id') ? (int) $request->input('product_id') : null;

        $summary = $this->summaryMetrics($effectiveStart, $periodEnd, $hasTracking, $range);
        $chart = $this->chartSeries($effectiveStart, $periodEnd, $productFilterId, $hasTracking);
        $topProducts = $this->topProducts($effectiveStart, $periodEnd, $hasTracking, $range);
        $activity = $this->recentActivity();
        $featuredCount = Product::query()->where('is_featured', true)->count();
        $featuredProducts = Product::query()
            ->where('is_featured', true)
            ->with(['images' => fn ($q) => $q->where('is_primary', true)])
            ->orderBy('name')
            ->limit(8)
            ->get();

        $filterProducts = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return [
            'range' => $range,
            'tracking_started_at' => $trackingStartedAt,
            'has_tracking' => $hasTracking,
            'summary' => $summary,
            'featured_count' => $featuredCount,
            'chart' => $chart,
            'top_products' => $topProducts,
            'recent_activity' => $activity,
            'featured_products' => $featuredProducts,
            'filter_products' => $filterProducts,
            'product_filter_id' => $productFilterId,
        ];
    }

    /**
     * @return array{preset: string, start: Carbon, end: Carbon, label: string, from: string, to: string}
     */
    public function resolveRange(Request $request, string $tz): array
    {
        $preset = $request->string('range')->toString();
        if (! in_array($preset, ['7', '30', 'custom'], true)) {
            $preset = '7';
        }

        $now = Carbon::now($tz);

        if ($preset === 'custom') {
            $from = $this->parseDate($request->input('from'), $tz) ?? $now->copy()->subDays(6)->startOfDay();
            $to = $this->parseDate($request->input('to'), $tz) ?? $now->copy()->endOfDay();

            if ($from->greaterThan($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            $from = $from->startOfDay();
            $to = $to->endOfDay();

            // Cap custom range length to keep aggregates snappy.
            if ($from->diffInDays($to) > 90) {
                $from = $to->copy()->subDays(90)->startOfDay();
            }

            return [
                'preset' => 'custom',
                'start' => $from,
                'end' => $to,
                'label' => $from->format('M j, Y').' – '.$to->format('M j, Y'),
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ];
        }

        $days = $preset === '30' ? 30 : 7;
        $end = $now->copy()->endOfDay();
        $start = $now->copy()->subDays($days - 1)->startOfDay();

        return [
            'preset' => (string) $days,
            'start' => $start,
            'end' => $end,
            'label' => 'Last '.$days.' days',
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon, label: string}  $range
     * @return array<string, mixed>
     */
    private function summaryMetrics(?Carbon $effectiveStart, Carbon $periodEnd, bool $hasTracking, array $range): array
    {
        $periodLabel = $range['label'];

        if (! $hasTracking || $effectiveStart === null || $effectiveStart->greaterThan($periodEnd)) {
            return [
                'total_views' => null,
                'unique_visitors' => null,
                'most_viewed' => null,
                'available' => false,
                'period_label' => $periodLabel,
                'note' => $hasTracking
                    ? 'No product views fall within this reporting period.'
                    : 'Product-view tracking has not recorded any visits yet.',
            ];
        }

        $base = ProductView::query()
            ->whereBetween('viewed_at', [$effectiveStart, $periodEnd]);

        $totalViews = (clone $base)->count();
        $uniqueVisitors = (int) (clone $base)->selectRaw('COUNT(DISTINCT visitor_key) as aggregate')->value('aggregate');

        $topRow = ProductView::query()
            ->select('product_id', DB::raw('COUNT(*) as views_count'))
            ->whereBetween('viewed_at', [$effectiveStart, $periodEnd])
            ->groupBy('product_id')
            ->orderByDesc('views_count')
            ->first();

        $mostViewed = null;
        if ($topRow) {
            $product = Product::query()
                ->withTrashed()
                ->with(['images' => fn ($q) => $q->where('is_primary', true)])
                ->find($topRow->product_id);

            if ($product) {
                $mostViewed = [
                    'product' => $product,
                    'views' => (int) $topRow->views_count,
                ];
            }
        }

        return [
            'total_views' => $totalViews,
            'unique_visitors' => $uniqueVisitors,
            'most_viewed' => $mostViewed,
            'available' => true,
            'period_label' => $periodLabel,
            'effective_from' => $effectiveStart,
            'note' => $effectiveStart->greaterThan($range['start'])
                ? 'Reporting starts from when tracking began ('.$effectiveStart->timezone(config('app.timezone'))->format('M j, Y g:i A').').'
                : null,
        ];
    }

    /**
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>, table: list<array<string, mixed>>, empty: bool, empty_reason: string|null}
     */
    private function chartSeries(?Carbon $effectiveStart, Carbon $periodEnd, ?int $productFilterId, bool $hasTracking): array
    {
        if (! $hasTracking || $effectiveStart === null || $effectiveStart->greaterThan($periodEnd)) {
            return [
                'labels' => [],
                'datasets' => [],
                'table' => [],
                'empty' => true,
                'empty_reason' => $hasTracking
                    ? 'No product views in this period to chart.'
                    : 'Charts appear after the first tracked product-page visit.',
            ];
        }

        $driver = DB::connection()->getDriverName();
        $dayExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', viewed_at)",
            'pgsql' => 'to_char(viewed_at, \'YYYY-MM-DD\')',
            default => 'DATE(viewed_at)',
        };

        $topIdsQuery = ProductView::query()
            ->select('product_id', DB::raw('COUNT(*) as views_count'))
            ->whereBetween('viewed_at', [$effectiveStart, $periodEnd])
            ->groupBy('product_id')
            ->orderByDesc('views_count');

        if ($productFilterId) {
            $topIds = collect([$productFilterId]);
        } else {
            $topIds = $topIdsQuery->limit(5)->pluck('product_id');
        }

        if ($topIds->isEmpty()) {
            return [
                'labels' => [],
                'datasets' => [],
                'table' => [],
                'empty' => true,
                'empty_reason' => 'No product views in this period to chart.',
            ];
        }

        $products = Product::query()
            ->withTrashed()
            ->whereIn('id', $topIds)
            ->get()
            ->keyBy('id');

        $rows = ProductView::query()
            ->select(DB::raw("{$dayExpr} as view_day"), 'product_id', DB::raw('COUNT(*) as views_count'))
            ->whereBetween('viewed_at', [$effectiveStart, $periodEnd])
            ->whereIn('product_id', $topIds)
            ->groupBy('view_day', 'product_id')
            ->orderBy('view_day')
            ->get();

        $labels = [];
        $cursor = $effectiveStart->copy()->startOfDay();
        $endDay = $periodEnd->copy()->startOfDay();
        while ($cursor->lte($endDay)) {
            $labels[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $byProductDay = [];
        foreach ($rows as $row) {
            $byProductDay[(int) $row->product_id][(string) $row->view_day] = (int) $row->views_count;
        }

        $palette = ['#054648', '#2a9d8f', '#e9c46a', '#f4a261', '#e76f51'];
        $datasets = [];
        $table = [];

        foreach ($topIds->values() as $index => $productId) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $data = [];
            foreach ($labels as $day) {
                $count = $byProductDay[$productId][$day] ?? 0;
                $data[] = $count;
                if ($count > 0) {
                    $table[] = [
                        'date' => $day,
                        'product' => $product->name,
                        'views' => $count,
                    ];
                }
            }

            $datasets[] = [
                'label' => $product->name,
                'product_id' => $productId,
                'data' => $data,
                'borderColor' => $palette[$index % count($palette)],
                'backgroundColor' => $palette[$index % count($palette)],
            ];
        }

        usort($table, fn ($a, $b) => [$a['date'], $a['product']] <=> [$b['date'], $b['product']]);

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'table' => $table,
            'empty' => $datasets === [],
            'empty_reason' => null,
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon}  $range
     * @return list<array<string, mixed>>
     */
    private function topProducts(?Carbon $effectiveStart, Carbon $periodEnd, bool $hasTracking, array $range): array
    {
        if (! $hasTracking || $effectiveStart === null || $effectiveStart->greaterThan($periodEnd)) {
            return [];
        }

        $rows = ProductView::query()
            ->select('product_id', DB::raw('COUNT(*) as views_count'), DB::raw('COUNT(DISTINCT visitor_key) as unique_visitors'))
            ->whereBetween('viewed_at', [$effectiveStart, $periodEnd])
            ->groupBy('product_id')
            ->orderByDesc('views_count')
            ->limit(10)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $products = Product::query()
            ->withTrashed()
            ->with(['images' => fn ($q) => $q->where('is_primary', true)])
            ->whereIn('id', $rows->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $periodLength = max(1, (int) $range['start']->diffInSeconds($range['end']));
        $prevEnd = $effectiveStart->copy()->subSecond();
        $prevStart = $prevEnd->copy()->subSeconds($periodLength);
        $trackingStarted = $this->tracker->trackingStartedAt();
        $canCompare = $trackingStarted !== null && $trackingStarted->lte($prevStart);

        $prevMap = collect();
        if ($canCompare) {
            $prevMap = ProductView::query()
                ->select('product_id', DB::raw('COUNT(*) as views_count'))
                ->whereBetween('viewed_at', [$prevStart, $prevEnd])
                ->whereIn('product_id', $rows->pluck('product_id'))
                ->groupBy('product_id')
                ->pluck('views_count', 'product_id');
        }

        $ranked = [];
        $rank = 1;
        foreach ($rows as $row) {
            $product = $products->get($row->product_id);
            if (! $product) {
                continue;
            }

            $views = (int) $row->views_count;
            $prevViews = $canCompare ? (int) ($prevMap[$row->product_id] ?? 0) : null;
            $delta = $prevViews !== null ? $views - $prevViews : null;

            $ranked[] = [
                'rank' => $rank++,
                'product' => $product,
                'views' => $views,
                'unique_visitors' => (int) $row->unique_visitors,
                'previous_views' => $prevViews,
                'views_delta' => $delta,
                'compare_available' => $canCompare,
            ];
        }

        return $ranked;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentActivity(): array
    {
        $items = ProductActivity::query()
            ->with(['product' => fn ($q) => $q->withTrashed()])
            ->latest()
            ->limit(12)
            ->get();

        return $items->map(function (ProductActivity $activity) {
            $product = $activity->product;

            return [
                'action' => $activity->action,
                'label' => $activity->label(),
                'product_name' => $product?->name ?? 'Deleted product',
                'product_id' => $activity->product_id,
                'when' => $activity->created_at?->timezone(config('app.timezone')),
                'when_human' => $activity->created_at?->timezone(config('app.timezone'))->diffForHumans(),
                'edit_url' => $product && ! $product->trashed()
                    ? route('admin.products.edit', $product)
                    : null,
                'storefront_url' => $product && ! $product->trashed() && $product->is_active
                    ? route('products.show', $product->slug)
                    : null,
            ];
        })->all();
    }

    private function parseDate(mixed $value, string $tz): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value, $tz);
        } catch (\Throwable) {
            return null;
        }
    }
}
