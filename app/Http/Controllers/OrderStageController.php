<?php

namespace App\Http\Controllers;

use App\Models\OrderStage;
use App\Services\StageAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderStageController extends Controller
{
    public function __construct(
        protected StageAuthorizationService $authService
    ) {
    }

    public function start(OrderStage $orderStage)
    {
        if (!$this->authService->canActOnStage(Auth::user(), $orderStage->order, $orderStage->stage_id)) {
            return back()->withErrors(['auth' => 'No autorizado para esta etapa.']);
        }

        $orderStage->update([
            'started_at' => now(),
            'started_by' => Auth::id(),
        ]);

        return back()->with('status', 'Etapa iniciada.');
    }

    public function pause(OrderStage $orderStage)
    {
        // Pause logic (could be more complex, but for now we just clear started_at or similar)
        // Given the instructions, we'll just implement a simple state change.
        $orderStage->update([
            'started_at' => null, // Simple "pause" resets the start time in this simple model
        ]);

        return back()->with('status', 'Etapa pausada.');
    }

    public function finish(OrderStage $orderStage)
    {
        if (!$this->authService->canActOnStage(Auth::user(), $orderStage->order, $orderStage->stage_id)) {
            return back()->withErrors(['auth' => 'No autorizado para esta etapa.']);
        }

        $orderStage->update([
            'completed_at' => now(),
            'completed_by' => Auth::id(),
        ]);

        return back()->with('status', 'Etapa finalizada.');
    }

    public function remit(Request $request, OrderStage $orderStage)
    {
        $request->validate([
            'target_stage_id' => 'required|exists:stages,id',
            'notes' => 'nullable|string'
        ]);

        // Remit logic: Reset self and all stages after the target
        $orderStage->order->orderStages()
            ->where('sequence', '>=', function ($query) use ($request) {
                $query->select('sequence')
                    ->from('order_stages')
                    ->where('stage_id', $request->target_stage_id)
                    ->limit(1);
            })
            ->update([
                'started_at' => null,
                'completed_at' => null,
                'started_by' => null,
                'completed_by' => null,
                'notes' => $request->notes // Add remit notes to target? 
            ]);

        return back()->with('status', 'Pedido remitido.');
    }
}
