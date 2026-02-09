<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderStage;
use App\Models\Stage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    private const DEFAULT_WORKFLOW = [
        'Corte',
        'Enchape',
        'Servicios Especiales',
        'Revisión',
        'Entrega',
    ];

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
     * Show the form for creating a new order.
     */
    public function create(): View
    {
        Gate::authorize('create-orders');

        $stages = Stage::all()->sortBy(function ($stage) {
            $index = array_search($stage->name, self::DEFAULT_WORKFLOW);

            return $index === false ? 999 : $index;
        });

        $selectedClient = null;
        if (old('client_id')) {
            $selectedClient = Client::find(old('client_id'));
        }

        return view('orders.create', compact('stages', 'selectedClient'));
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        Gate::authorize('create-orders');

        $validated = $request->validated();

        $order = DB::transaction(function () use ($validated) {
            $order = Order::create([
                'client_id' => $validated['client_id'],
                'material' => $validated['material'],
                'invoice_number' => $validated['invoice_number'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Get selected stages and sort them by default workflow
            $selectedStages = Stage::whereIn('id', $validated['stages'])->get()
                ->sortBy(function ($stage) {
                    $index = array_search($stage->name, self::DEFAULT_WORKFLOW);

                    return $index === false ? 999 : $index;
                })->values();

            foreach ($selectedStages as $index => $stage) {
                OrderStage::create([
                    'order_id' => $order->id,
                    'stage_id' => $stage->id,
                    'sequence' => $index + 1,
                ]);
            }

            return $order;
        });

        return redirect()->route('orders.index')
            ->with('success', 'Orden creada exitosamente.');
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order): View
    {
        Gate::authorize('edit-orders');

        $order->load(['client', 'orderStages.stage']);

        $allStages = Stage::all()->sortBy(function ($stage) {
            $index = array_search($stage->name, self::DEFAULT_WORKFLOW);

            return $index === false ? 999 : $index;
        });

        return view('orders.edit', compact('order', 'allStages'));
    }

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('edit-orders');

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:50|unique:orders,invoice_number,'.$order->id,
            'material' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $order->update($validated);

        return redirect()->route('orders.index')
            ->with('success', 'Orden actualizada exitosamente.');
    }

    /**
     * Add a stage to the order.
     */
    public function addStage(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('edit-orders');

        $validated = $request->validate([
            'stage_id' => 'required|exists:stages,id',
        ]);

        // Check if stage already exists in order
        if ($order->orderStages()->where('stage_id', $validated['stage_id'])->exists()) {
            return back()->with('error', 'La etapa ya existe en esta orden.');
        }

        // Get current stage (last one that started or last completed)
        $currentStage = $order->orderStages()
            ->whereNotNull('started_at')
            ->orderByDesc('sequence')
            ->first();

        $lastSequence = $order->orderStages()->max('sequence') ?? 0;

        // If no stage has started, add at the end or handle accordingly.
        // The requirement says: "Can only add stages AFTER the current stage of the order."
        // If no stage started, we can add anywhere? Usually after existing sequences.

        $newSequence = $lastSequence + 1;
        if ($currentStage) {
            // Need to shift sequences if we want to insert RIGHT AFTER current stage
            // But usually adding is just adding to the end of the production route.
            // Let's assume inserting after current stage means shifting others.

            $startIndex = $currentStage->sequence + 1;
            DB::transaction(function () use ($order, $startIndex, $validated) {
                $order->orderStages()->where('sequence', '>=', $startIndex)->increment('sequence');
                OrderStage::create([
                    'order_id' => $order->id,
                    'stage_id' => $validated['stage_id'],
                    'sequence' => $startIndex,
                ]);
            });
        } else {
            OrderStage::create([
                'order_id' => $order->id,
                'stage_id' => $validated['stage_id'],
                'sequence' => $newSequence,
            ]);
        }

        return back()->with('success', 'Etapa añadida exitosamente.');
    }

    /**
     * Remove a stage from the order.
     */
    public function removeStage(Order $order, Stage $stage): RedirectResponse
    {
        Gate::authorize('edit-orders');

        $orderStage = $order->orderStages()->where('stage_id', $stage->id)->firstOrFail();

        if ($orderStage->started_at) {
            return back()->with('error', 'No se puede eliminar una etapa que ya ha iniciado.');
        }

        DB::transaction(function () use ($order, $orderStage) {
            $sequence = $orderStage->sequence;
            $orderStage->delete();
            $order->orderStages()->where('sequence', '>', $sequence)->decrement('sequence');
        });

        return back()->with('success', 'Etapa eliminada exitosamente.');
    }
}
