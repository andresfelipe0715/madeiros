<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddStageRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\OrderStage;
use App\Models\Stage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderManagementController extends Controller
{
    /**
     * Display a listing of the orders.
     */
    public function index(): View
    {
        Gate::authorize('view-orders');

        $orders = Order::with(['client', 'orderStages.stage'])
            ->latest()
            ->paginate(15);

        return view('orders.index', compact('orders'));
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order): View
    {
        Gate::authorize('edit-orders');

        $order->load(['client', 'orderStages.stage']);

        $allStages = Stage::orderBy('default_sequence')->get();
        $finalStageId = Stage::orderBy('default_sequence', 'desc')->value('id');

        return view('orders.edit', compact('order', 'allStages', 'finalStageId'));
    }

    /**
     * Update the specified order in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        Gate::authorize('edit-orders');

        $order->update($request->validated());

        return redirect()->route('orders.index')
            ->with('success', 'Orden actualizada exitosamente.');
    }

    /**
     * Add a stage to the order.
     */
    public function addStage(AddStageRequest $request, Order $order): RedirectResponse
    {
        Gate::authorize('edit-orders');

        $validated = $request->validated();
        $stageId = $validated['stage_id'];

        // Check if stage already exists in order
        if ($order->orderStages()->where('stage_id', $stageId)->exists()) {
            return back()->with('error', 'La etapa ya existe en esta orden.');
        }

        $newStage = Stage::findOrFail($stageId);

        // Validation rule: Block if any stage with a HIGHER default_sequence has already been completed.
        // "No se puede insertar esta etapa porque ya se completaron etapas posteriores."
        $hasLaterCompletedStage = $order->orderStages()
            ->whereNotNull('completed_at')
            ->whereHas('stage', function ($query) use ($newStage) {
                $query->where('default_sequence', '>', $newStage->default_sequence);
            })
            ->exists();

        if ($hasLaterCompletedStage) {
            return back()->with('error', 'No se puede insertar esta etapa porque ya se completaron etapas posteriores.');
        }

        // Determine the correct insertion position (sequence) for the frozen workflow.
        // We look for the current sequence of the stage that should logically follow this new stage.
        $nextLogicalStage = $order->orderStages()
            ->join('stages', 'order_stages.stage_id', '=', 'stages.id')
            ->where('stages.default_sequence', '>', $newStage->default_sequence)
            ->orderBy('stages.default_sequence', 'asc')
            ->first();

        // If no follow-up stage exists, it goes to the end.
        if ($nextLogicalStage) {
            $position = $nextLogicalStage->sequence;
        } else {
            $position = $order->orderStages()->max('sequence') + 1;
        }

        DB::transaction(function () use ($order, $newStage, $position) {
            // 1. Shift existing stages up (+1) from the calculated position
            $order->orderStages()
                ->where('sequence', '>=', $position)
                ->increment('sequence');

            // 2. Insert new stage at the calculated sequence
            OrderStage::create([
                'order_id' => $order->id,
                'stage_id' => $newStage->id,
                'sequence' => $position,
            ]);
        });

        return back()->with('success', 'Etapa añadida exitosamente.');
    }

    /**
     * Remove a stage from the order.
     */
    public function removeStage(Order $order, Stage $stage): RedirectResponse
    {
        Gate::authorize('edit-orders');

        $orderStage = OrderStage::where('order_id', $order->id)
            ->where('stage_id', $stage->id)
            ->firstOrFail();

        if ($orderStage->started_at) {
            return back()->with('error', 'No se puede eliminar una etapa que ya ha iniciado.');
        }

        // Integrity check: Prevent removing the final stage (highest default_sequence)
        $isFinalStage = Stage::where('id', $stage->id)
            ->where('default_sequence', Stage::max('default_sequence'))
            ->exists();

        if ($isFinalStage) {
            return back()->with('error', 'No se puede eliminar la etapa final de entrega.');
        }

        DB::transaction(function () use ($order, $orderStage) {
            $deletedSequence = $orderStage->sequence;
            $orderStage->delete();

            // Shift existing stages down to fill the gap
            $order->orderStages()
                ->where('sequence', '>', $deletedSequence)
                ->decrement('sequence');
        });

        return back()->with('success', 'Etapa eliminada exitosamente.');
    }

    /**
     * Resequence all stages for an order solely based on their CURRENT sequence.
     * This is used as a safety/compacter and never uses the global default_sequence.
     */
    private function resequenceOrderStages(Order $order): void
    {
        // Get the records sorted by their current sequence (order-specific truth)
        $records = $order->orderStages()
            ->orderBy('sequence')
            ->get();

        // Re-assign sequential sequences starting from 1 to ensure no gaps
        $i = 1;
        foreach ($records as $record) {
            $record->update(['sequence' => $i++]);
        }
    }
}
