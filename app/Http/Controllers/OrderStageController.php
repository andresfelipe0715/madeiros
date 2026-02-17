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
        $user = Auth::user();

        // 1. Basic Authorization (Role Access + Internal Sequence + Queue/Admin Override)
        if (! $this->authService->canActOnStage($user, $orderStage->order, $orderStage->stage_id)) {
            // Check if it's just a queue issue to provide better feedback
            if (! $this->authService->isNextInQueue($orderStage->order, $orderStage->stage_id)) {
                return back()->withErrors(['auth' => 'Este pedido no es el siguiente en la fila.']);
            }

            return back()->withErrors(['auth' => 'No autorizado para esta etapa.']);
        }

        DB::transaction(function () use ($orderStage) {
            $orderStage->update([
                'started_at' => now(),
                'started_by' => Auth::id(),
            ]);
        });

        return back()->with('status', 'Etapa iniciada.');
    }

    public function pause(OrderStage $orderStage)
    {
        if (! $this->authService->canActOnStage(Auth::user(), $orderStage->order, $orderStage->stage_id)) {
            return back()->withErrors(['auth' => 'No autorizado para esta etapa.']);
        }

        // Pause logic (could be more complex, but for now we just clear started_at or similar)
        // Given the instructions, we'll just implement a simple state change.
        $orderStage->update([
            'started_at' => null, // Simple "pause" resets the start time in this simple model
        ]);

        return back()->with('status', 'Etapa pausada.');
    }

    public function finish(OrderStage $orderStage): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        if (! $this->authService->canActOnStage($user, $orderStage->order, $orderStage->stage_id)) {
            if (! $this->authService->isNextInQueue($orderStage->order, $orderStage->stage_id)) {
                return back()->withErrors(['auth' => 'Este pedido no es el siguiente en la fila.']);
            }

            return back()->withErrors(['auth' => 'No autorizado para esta etapa.']);
        }

        DB::transaction(function () use ($orderStage) {
            // 1. Mark current stage as completed
            $orderStage->update([
                'completed_at' => now(),
                'completed_by' => Auth::id(),
            ]);

            // 2. Load fresh order data to ensure we have the latest state and correct model type
            $order = \App\Models\Order::find($orderStage->order_id);

            // 3. Determine if this is the last stage and all are completed using Eloquent
            $maxSequence = $order->orderStages()->max('sequence');
            $hasIncompleteStages = $order->orderStages()->whereNull('completed_at')->exists();

            // 4. Update the Order only if current sequence is the max and no stages remain incomplete
            if ($orderStage->sequence == $maxSequence && ! $hasIncompleteStages) {
                $order->update([
                    'delivered_at' => now(),
                    'delivered_by' => Auth::id(),
                ]);
            }
        });

        return back()->with('status', 'Etapa finalizada.');
    }

    public function remit(Request $request, OrderStage $orderStage)
    {
        $user = Auth::user();

        if (! $this->authService->canActOnStage($user, $orderStage->order, $orderStage->stage_id)) {
            if (! $this->authService->isNextInQueue($orderStage->order, $orderStage->stage_id)) {
                return back()->withErrors(['auth' => 'Este pedido no es el siguiente en la fila.']);
            }

            return back()->withErrors(['auth' => 'No autorizado para esta etapa.']);
        }

        try {
            $request->validate([
                'target_stage_id' => 'required|exists:stages,id',
                'notes' => 'required|string',
            ], [
                'target_stage_id.required' => 'Seleccione una etapa.',
                'notes.required' => 'El campo notas es obligatorio.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput()->with('failed_remit_id', $orderStage->id);
        }

        return DB::transaction(function () use ($request, $orderStage) {
            $order = $orderStage->order;
            $targetStageId = $request->target_stage_id;

            // Integrity Check: target_stage_id must belong to the same order and have a lower sequence
            $targetStage = $order->orderStages()
                ->where('stage_id', $targetStageId)
                ->first();

            if (! $targetStage || $targetStage->sequence === null || $targetStage->sequence >= $orderStage->sequence) {
                abort(400, 'Invalid remittance target.');
            }

            $targetSequence = $targetStage->sequence;
            $reason = str_replace(['|', ':'], [' ', ' '], $request->notes); // Sanitize to avoid format breakage

            // 1. Create the structured log entry
            \App\Models\OrderLog::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'action' => "remit|from:{$orderStage->stage_id}|to:{$targetStageId}|reason:{$reason}",
            ]);

            // 2. Clear delivery status if it was delivered
            $order->update([
                'delivered_at' => null,
                'delivered_by' => null,
            ]);

            // 3. Reset execution data of all stages starting from target back to current
            $order->orderStages()
                ->where('sequence', '>=', $targetSequence)
                ->update([
                    'started_at' => null,
                    'completed_at' => null,
                    'started_by' => null,
                    'completed_by' => null,
                ]);

            return back()->with('status', 'Pedido remitido.');
        });
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
