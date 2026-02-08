<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderStage;
use App\Models\Stage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
     * Show the form for creating a new order.
     */
    public function create(): View
    {
        $user = Auth::user();
        if ($user->role->name !== 'Admin') {
            abort(403, 'No tiene permisos para crear órdenes.');
        }

        $clients = Client::all();
        $stages = Stage::all()->sortBy(function ($stage) {
            $index = array_search($stage->name, self::DEFAULT_WORKFLOW);

            return $index === false ? 999 : $index;
        });

        return view('orders.create', compact('clients', 'stages'));
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
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

        $firstOrderStage = $order->orderStages()->orderBy('sequence')->first();

        return redirect()->route('dashboard.stage', $firstOrderStage->stage_id)
            ->with('success', 'Orden creada exitosamente. Iniciando en la etapa de '.$firstOrderStage->stage->name.'.');
    }
}
