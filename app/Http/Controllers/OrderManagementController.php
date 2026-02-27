<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddStageRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderStage;
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

        $order->load(['client', 'orderStages.stage', 'orderMaterials.material']);

        $allStages = Stage::orderBy('default_sequence')->get();
        $materials = Material::all();
        $firstStageId = Stage::orderBy('default_sequence', 'asc')->value('id');
        $finalStageId = Stage::orderBy('default_sequence', 'desc')->value('id');

        return view('orders.edit', compact('order', 'allStages', 'firstStageId', 'finalStageId', 'materials'));
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
                if ($order->delivered_at) {
                    // POST-DELIVERY: Only actual_quantity corrections allowed
                    foreach ($validated['materials'] as $data) {
                        $om = $order->orderMaterials()->findOrFail($data['id']);
                        if (array_key_exists('actual_quantity', $data) && (float) $data['actual_quantity'] != (float) $om->actual_quantity) {
                            $this->inventory->correctActual($om, (float) $data['actual_quantity']);
                        }
                    }
                } else {
                    // BEFORE DELIVERY: Standard update
                    $order->update([
                        'invoice_number' => $validated['invoice_number'],
                        'notes' => $validated['notes'] ?? null,
                        'lleva_herrajeria' => $request->has('lleva_herrajeria'),
                        'lleva_manual_armado' => $request->has('lleva_manual_armado'),
                    ]);

                    $this->inventory->adjust($order, $validated['materials']);

                    // 1. Handle main order PDF file replacement
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

        $newStage = Stage::findOrFail($stageId);

        // Validation rule: Block if any stage with a HIGHER default_sequence has already been started or completed.
        // "No se puede insertar esta etapa porque ya se completaron o iniciaron etapas posteriores."
        $hasLaterStartedOrCompletedStage = $order->orderStages()
            ->where(function ($query) {
                $query->whereNotNull('completed_at')
                    ->orWhereNotNull('started_at');
            })
            ->whereHas('stage', function ($query) use ($newStage) {
                $query->where('default_sequence', '>', $newStage->default_sequence);
            })
            ->exists();

        if ($hasLaterStartedOrCompletedStage) {
            return back()->with('error', 'No se puede insertar antes de una etapa en progreso o completada. Debe pausarla primero.');
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
            $this->shiftStagesUp($order, $position);

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
