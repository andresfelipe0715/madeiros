<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddStageRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderStage;
use App\Models\SpecialService;
use App\Models\Stage;
use App\Services\InventoryService;
use App\Traits\CompressesImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrderManagementController extends Controller
{
    use CompressesImages;

    public function __construct(
        protected InventoryService $inventory
    ) {}

    /**
     * Display a listing of the orders.
     */
    public function index(): View
    {
        Gate::authorize('view-orders');

        $query = Order::with(['client', 'orderStages.stage', 'createdBy']);

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('client', function ($sub) use ($search) {
                        $sub->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('document', 'LIKE', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest()
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', compact('orders'));
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order): View
    {
        Gate::authorize('edit-orders');

        $order->load(['client', 'orderStages.stage', 'orderMaterials.material', 'orderSpecialServices.specialService']);

        $allStages = Stage::where('active', true)
            ->where(function ($query) {
                $query->whereNull('stage_group_id')
                    ->orWhereHas('stageGroup', function ($q) {
                        $q->where('active', true);
                    });
            })
            ->orderBy('default_sequence')
            ->get();
        $materials = Material::all();
        $specialServices = SpecialService::where('active', true)->get();
        $firstStageId = Stage::orderBy('default_sequence', 'asc')->value('id');
        $finalStageId = Stage::orderBy('default_sequence', 'desc')->value('id');

        return view('orders.edit', compact('order', 'allStages', 'firstStageId', 'finalStageId', 'materials', 'specialServices'));
    }

    /**
     * Update the specified order in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        Gate::authorize('edit-orders');
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($order, $validated, $request) {
                // Update Order-level fields only if not delivered
                if (! $order->delivered_at) {
                    $order->update([
                        'invoice_number' => $validated['invoice_number'],
                        'notes' => $validated['notes'] ?? null,
                        'lleva_herrajeria' => $request->has('lleva_herrajeria'),
                        'lleva_manual_armado' => $request->has('lleva_manual_armado'),
                    ]);

                    // Sync Special Services
                    if (isset($validated['special_services'])) {
                        foreach ($validated['special_services'] as $data) {
                            if (isset($data['id'])) {
                                $oss = $order->orderSpecialServices()->findOrFail($data['id']);
                                if (isset($data['cancelled']) && $data['cancelled']) {
                                    $oss->update(['cancelled_at' => now()]);
                                } else {
                                    $oss->update([
                                        'service_id' => $data['service_id'],
                                        'notes' => $data['notes'] ?? null,
                                        'cancelled_at' => null, // Reactivate if it was cancelled
                                    ]);
                                }
                            } else {
                                $order->orderSpecialServices()->create([
                                    'service_id' => $data['service_id'],
                                    'notes' => $data['notes'] ?? null,
                                ]);
                            }
                        }
                    }

                    // Handle main order PDF file replacement
                    if ($request->hasFile('order_file')) {
                        $ordenType = \App\Models\FileType::firstOrCreate(['name' => 'Orden']);

                        // Delete existing PDF if any
                        $existingFile = $order->orderFiles()->where('file_type_id', $ordenType->id)->first();
                        if ($existingFile) {
                            Storage::disk('public')->delete($existingFile->file_path);
                            $existingFile->delete();
                        }

                        $path = $request->file('order_file')->store('orders', 'public');
                        $order->orderFiles()->create([
                            'file_type_id' => $ordenType->id,
                            'file_path' => $path,
                            'uploaded_by' => Auth::id(),
                        ]);
                    }
                }

                // Materials adjustment: Handle both pre- and post-consumption via InventoryService
                $this->inventory->adjust($order, $validated['materials']);

                // 2. Handle Evidence Photos Deletion
                if ($request->has('delete_files')) {
                    $filesToDelete = $order->orderFiles()->whereIn('id', $request->delete_files)->get();
                    foreach ($filesToDelete as $file) {
                        Storage::disk('public')->delete($file->file_path);
                        $file->delete();
                    }
                }

                // 3. Handle Evidence Photos Upload (Max 2 total)
                if ($request->hasFile('evidence_photos')) {
                    $evidenciaType = \App\Models\FileType::firstOrCreate(['name' => 'Evidencia']);

                    foreach ($request->file('evidence_photos') as $photo) {
                        $currentCount = $order->orderFiles()->where('file_type_id', $evidenciaType->id)->count();
                        if ($currentCount < 2) {
                            $path = $this->compressAndStore($photo, 'evidence');
                            if ($path) {
                                $order->orderFiles()->create([
                                    'file_type_id' => $evidenciaType->id,
                                    'file_path' => $path,
                                    'uploaded_by' => Auth::id(),
                                ]);
                            }
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

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

        $newStage = Stage::where('active', true)
            ->where(function ($query) {
                $query->whereNull('stage_group_id')
                    ->orWhereHas('stageGroup', function ($q) {
                        $q->where('active', true);
                    });
            })
            ->findOrFail($stageId);

        // If a delivery stage exists, insert right before it.
        $deliveryOrderStage = $order->orderStages()
            ->whereHas('stage', function ($q) {
                $q->where('is_delivery_stage', true);
            })
            ->first();

        if ($deliveryOrderStage) {
            $position = $deliveryOrderStage->sequence;
        } else {
            // Append to the end of the frozen workflow.
            $maxSequence = $order->orderStages()->max('sequence') ?? 0;
            $position = $maxSequence + 1;
        }

        DB::transaction(function () use ($order, $newStage, $position, $deliveryOrderStage) {
            // If inserting before delivery, shift delivery (and anything after) up.
            if ($deliveryOrderStage) {
                $this->shiftStagesUp($order, $position);
            }

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

        // Integrity check: Prevent removing the first or final stage
        $isMandatoryStage = Stage::where('id', $stage->id)
            ->where(function ($query) {
                $query->where('default_sequence', Stage::min('default_sequence'))
                    ->orWhere('default_sequence', Stage::max('default_sequence'));
            })
            ->exists();

        if ($isMandatoryStage) {
            return back()->with('error', 'No se puede eliminar una etapa obligatoria (primera o última).');
        }

        DB::transaction(function () use ($order, $orderStage) {
            $deletedSequence = $orderStage->sequence;
            $orderStage->delete();

            // Shift existing stages down to fill the gap
            $this->shiftStagesDown($order, $deletedSequence);
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

    /**
     * Increment sequences of existing stages by 1 starting from a given position.
     * Updates individually in descending order to avoid unique constraint violations.
     */
    private function shiftStagesUp(Order $order, int $fromSequence): void
    {
        $stages = $order->orderStages()
            ->where('sequence', '>=', $fromSequence)
            ->orderBy('sequence', 'desc')
            ->get();

        foreach ($stages as $stage) {
            $stage->update(['sequence' => $stage->sequence + 1]);
        }
    }

    /**
     * Decrement sequences of existing stages by 1 for sequences greater than a given position.
     * Updates individually in ascending order to avoid unique constraint violations.
     */
    private function shiftStagesDown(Order $order, int $greaterThanSequence): void
    {
        $stages = $order->orderStages()
            ->where('sequence', '>', $greaterThanSequence)
            ->orderBy('sequence', 'asc')
            ->get();

        foreach ($stages as $stage) {
            $stage->update(['sequence' => $stage->sequence - 1]);
        }
    }
}
