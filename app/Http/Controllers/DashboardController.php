<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Stage;
use App\Services\StageAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected StageAuthorizationService $authService
    ) {
    }

    /**
     * Show the main dashboard menu.
     */
    public function index()
    {
        $user = Auth::user();

        // Get accessible stages based on role for the menu
        $accessibleStages = $user->role->stages;

        return view('dashboard', [
            'accessibleStages' => $accessibleStages
        ]);
    }

    /**
     * Show a specific production stage (module).
     */
    public function showStage(Stage $stage)
    {
        $user = Auth::user();

        // Authorization: Check if user has access to this stage
        if (!$user->role->stages->contains($stage->id)) {
            abort(403, 'No tiene acceso a este módulo.');
        }

        // Fetch orders for this stage (next pending in sequence) with pagination
        $orders = Order::whereHas('orderStages', function ($query) use ($stage) {
            $query->where('stage_id', $stage->id)
                ->whereNull('completed_at')
                ->whereNotExists(function ($sub) {
                    $sub->select('id')
                        ->from('order_stages as os2')
                        ->whereColumn('os2.order_id', 'order_stages.order_id')
                        ->whereColumn('os2.sequence', '<', 'order_stages.sequence')
                        ->whereNull('os2.completed_at');
                });
        })
            ->with(['client', 'orderStages.stage', 'orderFiles.fileType', 'createdBy'])
            ->paginate(15);

        // 1. Fetch relevant remit logs in a single query for the orders on this page
        $orderIds = $orders->pluck('id');
        $allRemitLogs = \App\Models\OrderLog::whereIn('order_id', $orderIds)
            ->where('action', 'like', 'remit|%')
            ->orderBy('id', 'desc')
            ->get();

        // 2. Group and filter logs for the CURRENT stage (last 2 only)
        $remitLogs = $allRemitLogs->groupBy('order_id')->map(function ($logs) use ($stage) {
            return $logs->filter(function ($log) use ($stage) {
                $data = $log->remit_data;
                return $data && $data['to'] == $stage->id;
            })->take(2)->values();
        });

        // 3. Get stage names for efficient lookup in Blade (no DB queries in views)
        $stageNames = \App\Models\Stage::pluck('name', 'id');

        return view('stages.show', [
            'stage' => $stage,
            'orders' => $orders,
            'remitLogs' => $remitLogs, // Pass structured data to Blade
            'stageNames' => $stageNames,
            'authService' => $this->authService
        ]);
    }
}
