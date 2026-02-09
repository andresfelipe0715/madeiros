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

        return view('orders.edit', compact('order', 'allStages'));
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

        DB::transaction(function () use ($order, $newStage) {
            // Add with a temporary high sequence to avoid immediate conflict
            OrderStage::create([
                'order_id' => $order->id,
                'stage_id' => $newStage->id,
                'sequence' => 9999, // Temporary high sequence
            ]);

            $this->resequenceOrderStages($order);
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

        DB::transaction(function () use ($order, $orderStage) {
            $orderStage->delete();
            $this->resequenceOrderStages($order);
        });

        return back()->with('success', 'Etapa eliminada exitosamente.');
    }

    /**
     * Resequence all stages for an order based on their default sequence.
     * This method avoids UNIQUE constraint violations by using a two-step update.
     */
    private function resequenceOrderStages(Order $order): void
    {
        // 1. Shift all current sequences to a temporary range (+10000) to avoid any UNIQUE collision
        // This is done as a raw query to be statement-safe and bypass model events/state.
        DB::table('order_stages')
            ->where('order_id', $order->id)
            ->update(['sequence' => DB::raw('sequence + 10000')]);

        // 2. Get the records sorted by their global default sequence
        $records = DB::table('order_stages')
            ->join('stages', 'order_stages.stage_id', '=', 'stages.id')
            ->where('order_stages.order_id', $order->id)
            ->orderBy('stages.default_sequence')
            ->select('order_stages.id')
            ->get();

        // 3. Re-assign sequential sequences starting from 1
        $i = 1;
        foreach ($records as $record) {
            DB::table('order_stages')
                ->where('id', $record->id)
                ->update(['sequence' => $i++]);
        }
    }
}
