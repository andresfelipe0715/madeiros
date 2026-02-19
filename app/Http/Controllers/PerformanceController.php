<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Carbon\Carbon;

class PerformanceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('view-performance');

        // 1. Handle Date Filtering
        $dateRange = $request->input('date_range', 90);
        $dateTo = Carbon::now();
        $dateFrom = match ($dateRange) {
            '7' => Carbon::now()->subDays(7),
            '30' => Carbon::now()->subDays(30),
            '60' => Carbon::now()->subDays(60),
            '90' => Carbon::now()->subDays(90),
            'custom' => $request->input('date_from') ? Carbon::parse($request->input('date_from'))->startOfDay() : Carbon::now()->subDays(90),
            default => Carbon::now()->subDays(90),
        };

        if ($dateRange === 'custom' && $request->input('date_to')) {
            $dateTo = Carbon::parse($request->input('date_to'))->endOfDay();
        } else {
            $dateTo = Carbon::now()->endOfDay();
        }

        $search = $request->input('search');

        // 2. Main User Performance Query
        $query = User::with(['role'])->where('active', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('document', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        foreach ($users as $user) {
            // Attribution strictly to completed_by
            $stages = \App\Models\OrderStage::where('completed_by', $user->id)
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->whereRaw('completed_at >= started_at') // Exclude invalid
                ->whereBetween('completed_at', [$dateFrom, $dateTo])
                ->get();

            $totalStages = $stages->count();
            $uniqueOrders = $stages->pluck('order_id')->unique()->count();

            $totalSeconds = 0;
            foreach ($stages as $os) {
                $totalSeconds += (int) abs($os->completed_at->diffInSeconds($os->started_at));
            }

            $user->performance_summary = (object) [
                'total_stages' => $totalStages,
                'unique_orders' => $uniqueOrders,
                'total_time_human' => $this->formatSeconds($totalSeconds),
                'avg_time_human' => $totalStages > 0 ? $this->formatSeconds($totalSeconds / $totalStages) : 'N/A',
                'total_seconds' => $totalSeconds,
                'avg_seconds' => $totalStages > 0 ? $totalSeconds / $totalStages : 0,
            ];
        }

        // 3. Stage Comparison (Manager Insights)
        $stagesList = \App\Models\Stage::orderBy('default_sequence')->get();
        $benchmarking = [];

        foreach ($stagesList as $stage) {
            $statsQuery = \App\Models\OrderStage::where('stage_id', $stage->id)
                ->whereNotNull('completed_at')
                ->whereNotNull('started_at')
                ->whereNotNull('completed_by')
                ->whereRaw('completed_at >= started_at')
                ->whereBetween('completed_at', [$dateFrom, $dateTo]);

            // Database specific time difference
            $driver = \Illuminate\Support\Facades\DB::getDriverName();
            if ($driver === 'sqlite') {
                $timeDiff = '(julianday(completed_at) - julianday(started_at)) * 86400';
            } else {
                $timeDiff = 'TIMESTAMPDIFF(SECOND, started_at, completed_at)';
            }

            $stats = $statsQuery->selectRaw("completed_by, AVG(ABS($timeDiff)) as avg_seconds")
                ->groupBy('completed_by')
                ->get();

            if ($stats->isNotEmpty()) {
                $avgAll = $stats->avg('avg_seconds');
                $fastestRaw = $stats->sortBy('avg_seconds')->first();
                $slowestRaw = $stats->sortByDesc('avg_seconds')->first();

                // Hydrate users for display
                $fastestUser = User::find($fastestRaw->completed_by);
                $slowestUser = User::find($slowestRaw->completed_by);

                if ($fastestUser && $slowestUser) {
                    $benchmarking[] = (object) [
                        'stage_name' => $stage->name,
                        'avg_all_human' => $this->formatSeconds($avgAll),
                        'fastest' => (object) [
                            'user' => $fastestUser,
                            'time_human' => $this->formatSeconds($fastestRaw->avg_seconds),
                        ],
                        'slowest' => (object) [
                            'user' => $slowestUser,
                            'time_human' => $this->formatSeconds($slowestRaw->avg_seconds),
                        ],
                    ];
                }
            }
        }

        return view('performance.index', compact('users', 'benchmarking', 'stagesList', 'dateRange', 'dateFrom', 'dateTo'));
    }

    /**
     * AJAX Details for Modal Pagination
     */
    public function details(Request $request, User $user)
    {
        Gate::authorize('view-performance');

        $dateRange = $request->input('date_range', 90);
        $stageId = $request->input('stage_id'); // New Stage Filter

        $dateTo = Carbon::now();
        $dateFrom = match ($dateRange) {
            '7' => Carbon::now()->subDays(7),
            '30' => Carbon::now()->subDays(30),
            '60' => Carbon::now()->subDays(60),
            '90' => Carbon::now()->subDays(90),
            'custom' => $request->input('date_from') ? Carbon::parse($request->input('date_from'))->startOfDay() : Carbon::now()->subDays(90),
            default => Carbon::now()->subDays(90),
        };

        if ($dateRange === 'custom' && $request->input('date_to')) {
            $dateTo = Carbon::parse($request->input('date_to'))->endOfDay();
        } else {
            $dateTo = Carbon::now()->endOfDay();
        }

        $query = \App\Models\OrderStage::where('completed_by', $user->id)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->whereRaw('completed_at >= started_at')
            ->whereBetween('completed_at', [$dateFrom, $dateTo]);

        if ($stageId && $stageId !== 'all') {
            $query->where('stage_id', $stageId);
        }

        $stages = $query->with(['order', 'stage'])
            ->latest('completed_at')
            ->paginate(15);

        // Transform for display
        $stages->getCollection()->transform(function ($os) {
            $seconds = (int) abs($os->completed_at->diffInSeconds($os->started_at));
            return [
                'order_invoice' => $os->order->invoice_number,
                'stage_name' => $os->stage->name,
                'started_at' => $os->started_at->format('d/m H:i'),
                'completed_at' => $os->completed_at->format('d/m H:i'),
                'duration_human' => $this->formatSeconds($seconds),
            ];
        });

        return response()->json([
            'user_name' => $user->name,
            'data' => $stages->items(),
            'pagination' => [
                'current_page' => $stages->currentPage(),
                'last_page' => $stages->lastPage(),
                'total' => $stages->total(),
                'next_page_url' => $stages->nextPageUrl(),
                'prev_page_url' => $stages->previousPageUrl(),
            ],
            'summary' => [
                'total_stages' => $stages->total(),
            ]
        ]);
    }

    private function formatSeconds(int $seconds): string
    {
        $seconds = (int) abs($seconds);

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return "{$minutes}m " . ($remainingSeconds > 0 ? "{$remainingSeconds}s" : "0s");
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return "{$hours}h {$remainingMinutes}m";
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        return "{$days}d {$remainingHours}h";
    }
}
