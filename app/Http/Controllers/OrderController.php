<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Client;
use App\Models\FileType;
use App\Models\Material;
use App\Models\Order;
use App\Models\Stage;
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
        $clientId = $request->query('client_id');
        $selectedClient = $clientId ? Client::find($clientId) : null;
        $materials = Material::all();
        $stages = Stage::orderBy('default_sequence')->get();

        return view('orders.create', compact('selectedClient', 'materials', 'stages'));
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
                    foreach ($validated['stages'] as $index => $stageId) {
                        $order->orderStages()->create([
                            'stage_id' => $stageId,
                            'sequence' => $index + 1,
                        ]);
                    }
                }

                // Reserve Materials
                $this->inventory->reserve($order, $validated['materials']);

                // Handle File Upload
                if ($request->hasFile('order_file')) {
                    $path = $request->file('order_file')->store('orders', 'public');
                    $fileType = FileType::firstOrCreate(['name' => 'Orden']);
                    $order->orderFiles()->create([
                        'file_type_id' => $fileType->id,
                        'file_path' => $path,
                        'uploaded_by' => Auth::id(),
                    ]);
                }
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Error al crear la orden: '.$e->getMessage())->withInput();
        }

        return redirect()->route('orders.index')
            ->with('success', 'Orden creada exitosamente.');
    }
}
