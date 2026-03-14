<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Client;
use App\Models\FileType;
use App\Models\Material;
use App\Models\Order;
use App\Models\SpecialService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    /**
     * Show the form for creating a new order.
     */
    public function create(Request $request): View
    {
        \Illuminate\Support\Facades\Gate::authorize('create-orders');

        $clientId = $request->query('client_id') ?? old('client_id');
        $selectedClient = $clientId ? Client::find($clientId) : null;
        $materials = Material::all();
        $stageGroups = \App\Models\StageGroup::where('active', true)
            ->with([
                'stages' => function ($query) {
                    $query->where('active', true)->orderBy('default_sequence');
                },
            ])->get()
            ->filter(function ($group) {
                return $group->stages->isNotEmpty();
            })
            ->sortBy(function ($group) {
                return $group->stages->min('default_sequence');
            });

        $specialServices = SpecialService::where('active', true)->get();

        return view('orders.create', compact('selectedClient', 'materials', 'stageGroups', 'specialServices'));
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $request) {
                $order = Order::create([
                    'client_id' => $validated['client_id'],
                    'invoice_number' => $validated['invoice_number'],
                    'notes' => $validated['notes'] ?? null,
                    'lleva_herrajeria' => $request->has('lleva_herrajeria'),
                    'lleva_manual_armado' => $request->has('lleva_manual_armado'),
                    'created_by' => Auth::id(),
                ]);

                // Create OrderStages
                if (isset($validated['stages'])) {
                    foreach ($validated['stages'] as $stageData) {
                        $order->orderStages()->create([
                            'stage_id' => $stageData['stage_id'],
                            'sequence' => $stageData['sequence'],
                        ]);
                    }
                }

                // Reserve Materials
                $this->inventory->reserve($order, $validated['materials']);

                // Save Special Services
                if (isset($validated['special_services'])) {
                    foreach ($validated['special_services'] as $serviceData) {
                        $order->orderSpecialServices()->create([
                            'service_id' => $serviceData['service_id'],
                            'notes' => $serviceData['notes'] ?? null,
                        ]);
                    }
                }

                // Handle File Upload
                if ($request->hasFile('order_file')) {
                    $path = $request->file('order_file')->store('orders', 'public');
                    \Illuminate\Support\Facades\Log::info('Order file stored', [
                        'order_id' => $order->id,
                        'path' => $path,
                        'is_string' => is_string($path),
                    ]);
                    $fileType = FileType::firstOrCreate(['name' => 'Orden']);
                    $order->orderFiles()->create([
                        'file_type_id' => $fileType->id,
                        'file_path' => $path,
                        'uploaded_by' => Auth::id(),
                    ]);
                }
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Error al crear la orden: '.$e->getMessage())->withInput();
        }

        return redirect()->route('orders.index')
            ->with('success', 'Orden creada exitosamente.');
    }
}
