<?php

namespace App\Http\Controllers;

use App\Models\OrderStage;
use App\Services\StageAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderStageController extends Controller
{
    public function __construct(
        protected StageAuthorizationService $authService
    ) {}

    public function start(OrderStage $orderStage)
    {
        if (! $this->authService->canActOnStage(Auth::user(), $orderStage->order, $orderStage->stage_id)) {
            return back()->withErrors(['auth' => 'No autorizado para esta etapa.']);
        }

        DB::transaction(function () use ($orderStage) {
            // Clear any remit reasons for this order as it's moving forward again
            $orderStage->order->orderStages()->update(['remit_reason' => null]);

            $orderStage->update([
                'started_at' => now(),
                'started_by' => Auth::id(),
            ]);
        });

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
        if (! $this->authService->canActOnStage(Auth::user(), $orderStage->order, $orderStage->stage_id)) {
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
            'notes' => 'nullable|string',
        ]);

        // 1. Reset timeline for all stages starting from target back to current
        $targetSequence = $orderStage->order->orderStages()
            ->where('stage_id', $request->target_stage_id)
            ->value('sequence');

        $orderStage->order->orderStages()
            ->where('sequence', '>=', $targetSequence)
            ->update([
                'started_at' => null,
                'completed_at' => null,
                'started_by' => null,
                'completed_by' => null,
            ]);

        // 2. Save the remission reason ONLY on the current stage record
        $orderStage->update([
            'remit_reason' => $request->notes,
        ]);

        return back()->with('status', 'Pedido remitido.');
    }

    public function updateNotes(Request $request, OrderStage $orderStage)
    {
        if (! $this->authService->canActOnStage(Auth::user(), $orderStage->order, $orderStage->stage_id)) {
            return back()->withErrors(['auth' => 'No autorizado para esta etapa.']);
        }

        $request->validate([
            'notes' => 'nullable|string|max:300',
        ]);

        $orderStage->update([
            'notes' => $request->notes,
        ]);

        return back()->with('status', 'Observaciones actualizadas.');
    }
}
