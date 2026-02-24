<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Client;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderStage;
use App\Models\Stage;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    /**
     * Show the form for creating a new order.
     */
    public function create(): View
    {
        Gate::authorize('create-orders');

        $stages = Stage::orderBy('default_sequence')->get();
        $materials = Material::all();

        $selectedClient = null;
        if (old('client_id')) {
            $selectedClient = Client::find(old('client_id'));
        }

        return view('orders.create', compact('stages', 'selectedClient', 'materials'));
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        Gate::authorize('create-orders');

        $validated = $request->validated();

        $order = DB::transaction(function () use ($validated, $request) {
            $order = Order::create([
                'client_id' => $validated['client_id'],
                'lleva_herrajeria' => $request->has('lleva_herrajeria'),
                'lleva_manual_armado' => $request->has('lleva_manual_armado'),
                'invoice_number' => $validated['invoice_number'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Handle Material Reservations
            $this->inventory->reserve($order, $validated['materials']);

            // Get selected stages and sort them by default_sequence
            $selectedStages = Stage::whereIn('id', $validated['stages'])
                ->orderBy('default_sequence')
                ->get();

            foreach ($selectedStages as $index => $stage) {
                OrderStage::create([
                    'order_id' => $order->id,
                    'stage_id' => $stage->id,
                    'sequence' => $index + 1,
                ]);
            }

            // Handle optional Archivo de la Orden
            if ($request->hasFile('order_file')) {
                $file = $request->file('order_file');
                $path = $file->store('orders', 'public');

                $fileType = \App\Models\FileType::firstOrCreate(['name' => 'archivo_orden']);

                \App\Models\OrderFile::create([
                    'order_id' => $order->id,
                    'file_type_id' => $fileType->id,
                    'file_path' => $path,
                    'uploaded_by' => Auth::id(),
                ]);
            }

            return $order;
        });

        return redirect()->route('orders.index')
            ->with('success', 'Orden creada exitosamente.');
    }

    /**
     * Show the form for editing the order.
     */
    public function edit(Order $order): View
    {
        Gate::authorize('edit-orders');

        $allStages = Stage::orderBy('default_sequence')->get();
        $materials = Material::all();
        $finalStageId = Stage::where('is_delivery_stage', true)->value('id');

        return view('orders.edit', compact('order', 'allStages', 'finalStageId', 'materials'));
    }

    /**
     * Update the order in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($order, $validated, $request) {
            $order->update([
                'invoice_number' => $validated['invoice_number'],
                'notes' => $validated['notes'] ?? null,
                'lleva_herrajeria' => $request->has('lleva_herrajeria'),
                'lleva_manual_armado' => $request->has('lleva_manual_armado'),
            ]);

            // Handle Material Adjustments
            $this->inventory->adjust($order, $validated['materials']);
        });

        return redirect()->route('orders.index')
            ->with('success', 'Orden actualizada exitosamente.');
    }
}
