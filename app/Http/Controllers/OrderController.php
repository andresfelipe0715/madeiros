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
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Show the form for creating a new order.
     */
    public function create(): View
    {
        Gate::authorize('create-orders');

        $stages = Stage::orderBy('default_sequence')->get();

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

        $order = DB::transaction(function () use ($validated, $request) {
            $order = Order::create([
                'client_id' => $validated['client_id'],
                'material' => $validated['material'],
                'invoice_number' => $validated['invoice_number'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

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
                $url = \Illuminate\Support\Facades\Storage::disk('public')->url($path);

                $fileType = \App\Models\FileType::firstOrCreate(['name' => 'archivo_orden']);

                \App\Models\OrderFile::create([
                    'order_id' => $order->id,
                    'file_type_id' => $fileType->id,
                    'file_url' => $url,
                    'uploaded_by' => Auth::id(),
                ]);
            }

            return $order;
        });

        return redirect()->route('orders.index')
            ->with('success', 'Orden creada exitosamente.');
    }
}
