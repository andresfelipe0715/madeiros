<?php

namespace App\Http\Controllers;

use App\Models\OrderStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderStageController extends Controller
{
    use \App\Traits\CompressesImages;

    public function __construct(
        protected \App\Services\StageAuthorizationService $authService,
        protected \App\Services\InventoryService $inventory
    ) {}

    /**
     * Enforce strict workflow integrity before any action.
     * Blocks if lower stages lack completion.
     */
    protected function checkWorkflowIntegrity(OrderStage $orderStage): ?\Illuminate\Http\RedirectResponse
    {
        $hasIncompleteLowerStages = $orderStage->order->orderStages()
            ->where('sequence', '<', $orderStage->sequence)
            ->whereNull('completed_at')
            ->exists();

        if ($hasIncompleteLowerStages) {
            return back()->withErrors(['auth' => 'Acción bloqueada: Existen etapas previas sin completar. El flujo del pedido ha cambiado, por favor recargue la página.']);
        }

        return null;
    }

    public function start(OrderStage $orderStage)
    {
        if ($error = $this->checkWorkflowIntegrity($orderStage)) {
            return $error;
        }

        $user = Auth::user();

        // 1. Basic Authorization (Role Access + Internal Sequence + Queue/Admin Override)
        if (! $this->authService->canActOnStage($user, $orderStage->order, $orderStage->stage_id)) {
            // Check if it's just a queue issue to provide better feedback
            if (! $this->authService->isNextInQueue($orderStage->order, $orderStage->stage_id)) {
                return back()->withErrors(['auth' => 'Este pedido no es el siguiente en la fila.']);
            }

            return back()->withErrors(['auth' => 'No autorizado para esta etapa.']);
        }

        try {
            DB::transaction(function () use ($orderStage) {
                $orderStage->lockForUpdate();

                if ($orderStage->order->delivered_at) {
                    throw new \Exception('No se puede modificar un pedido que ya ha sido entregado.');
                }

                // 2. State Validation: Cannot start what is already started or completed
                if ($orderStage->started_at) {
                    throw new \Exception('Esta etapa ya ha sido iniciada.');
                }

                if ($orderStage->completed_at) {
                    throw new \Exception('Esta etapa ya ha sido completada.');
                }

                // 3. Pending Validation: A pending stage cannot be started
                if ($orderStage->is_pending) {
                    throw new \Exception('Este pedido está marcado como pendiente y no puede procesarse.');
                }

                $orderStage->update([
                    'started_at' => now(),
                    'started_by' => Auth::id(),
                ]);
            });
        } catch (\Exception $e) {
            return back()->withErrors(['auth' => $e->getMessage()]);
        }

        return back()->with('status', 'Etapa iniciada.');
    }

    public function pause(OrderStage $orderStage)
    {
        if ($error = $this->checkWorkflowIntegrity($orderStage)) {
            return $error;
        }

        if (! Auth::user()->role->hasPermission('orders', 'edit')) {
            return back()->withErrors(['auth' => 'No tiene permisos para pausar el proceso.']);
        }

        try {
            DB::transaction(function () use ($orderStage) {
                $orderStage->lockForUpdate();

                if ($orderStage->order->delivered_at) {
                    throw new \Exception('No se puede modificar un pedido que ya ha sido entregado.');
                }

                // State Validation: Cannot pause if not started, already completed, or already pending
                if (! $orderStage->started_at) {
                    throw new \Exception('No se puede pausar una etapa que no ha sido iniciada.');
                }

                if ($orderStage->completed_at) {
                    throw new \Exception('No se puede pausar una etapa que ya ha sido completada.');
                }

                if ($orderStage->is_pending) {
                    throw new \Exception('No se puede pausar una etapa que ya está marcada como pendiente.');
                }

                $orderStage->update([
                    'started_at' => null,
                    'started_by' => null,
                ]);
            });
        } catch (\Exception $e) {
            return back()->withErrors(['auth' => $e->getMessage()]);
        }

        return back()->with('status', 'Etapa pausada.');
    }

    public function finish(OrderStage $orderStage): \Illuminate\Http\RedirectResponse
    {
        if ($error = $this->checkWorkflowIntegrity($orderStage)) {
            return $error;
        }

        $user = Auth::user();

        if (! $this->authService->canActOnStage($user, $orderStage->order, $orderStage->stage_id)) {
            if (! $this->authService->isNextInQueue($orderStage->order, $orderStage->stage_id)) {
                return back()->withErrors(['auth' => 'Este pedido no es el siguiente en la fila.']);
            }

            return back()->withErrors(['auth' => 'No autorizado para esta etapa.']);
        }

        try {
            DB::transaction(function () use ($orderStage) {
                $orderStage->lockForUpdate();

                if ($orderStage->order->delivered_at) {
                    throw new \Exception('No se puede modificar un pedido que ya ha sido entregado.');
                }

                // 2. State Validation: Cannot finish what hasn't been started or is already completed
                if (! $orderStage->started_at) {
                    throw new \Exception('No se puede finalizar una etapa que no ha sido iniciada.');
                }

                if ($orderStage->completed_at) {
                    throw new \Exception('Esta etapa ya ha sido completada.');
                }

                // 3. Pending Validation: A pending stage cannot be finished
                if ($orderStage->is_pending) {
                    throw new \Exception('Este pedido está marcado como pendiente y no puede procesarse.');
                }

                // 1. Mark current stage as completed
                $orderStage->update([
                    'completed_at' => now(),
                    'completed_by' => Auth::id(),
                ]);

                // 2. Load fresh order data
                $order = \App\Models\Order::find($orderStage->order_id);

                // Check for "Corte" stage to trigger consumption
                if ($orderStage->stage->stageGroup && $orderStage->stage->stageGroup->name === 'Corte') {
                    $this->inventory->consume($order);
                }

                // 3. Determine if this is the last stage and all are completed using Eloquent
                $maxSequence = $order->orderStages()->max('sequence');
                $hasIncompleteStages = $order->orderStages()->whereNull('completed_at')->exists();

                // 4. Update the Order only if current sequence is the max and no stages remain incomplete
                if ($orderStage->sequence == $maxSequence && ! $hasIncompleteStages) {
                    $order->update([
                        'delivered_at' => now(),
                        'delivered_by' => Auth::id(),
                    ]);
                }
            });
        } catch (\Exception $e) {
            return back()->withErrors(['auth' => $e->getMessage()]);
        }

        return back()->with('status', 'Etapa finalizada.');
    }

    public function remit(Request $request, OrderStage $orderStage)
    {
        if ($error = $this->checkWorkflowIntegrity($orderStage)) {
            return $error;
        }

        $user = Auth::user();

        if (! $this->authService->canActOnStage($user, $orderStage->order, $orderStage->stage_id)) {
            if (! $this->authService->isNextInQueue($orderStage->order, $orderStage->stage_id)) {
                return back()->withErrors(['auth' => 'Este pedido no es el siguiente en la fila.']);
            }

            return back()->withErrors(['auth' => 'No autorizado para esta etapa.']);
        }

        // State Validation: Cannot remit an already completed stage
        if ($orderStage->completed_at) {
            return back()->withErrors(['auth' => 'No se puede remitir una etapa que ya ha sido completada.']);
        }

        // 1.5. DB-Driven Validation: Check if remit is allowed from this stage
        if (! $orderStage->stage->can_remit) {
            return back()->withErrors(['auth' => 'No se permite remitir pedidos desde esta etapa.']);
        }

        // Prevent remitting if stage is pending
        if ($orderStage->is_pending) {
            return back()->withErrors([
                'auth' => 'Este pedido está marcado como pendiente y no puede procesarse.',
            ]);
        }

        try {
            $request->validate([
                'target_stage_id' => 'required|exists:stages,id',
                'notes' => 'required|string|max:250',
            ], [
                'target_stage_id.required' => 'Seleccione una etapa.',
                'notes.required' => 'El campo notas es obligatorio.',
                'notes.max' => 'La razón no puede exceder los 250 caracteres.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput()->with('failed_remit_id', $orderStage->id);
        }

        try {
            return DB::transaction(function () use ($request, $orderStage) {
                // Concurrency Locking
                $orderStage->order->lockForUpdate();

                if ($orderStage->order->delivered_at) {
                    throw new \Exception('No se puede remitir un pedido que ya ha sido entregado.');
                }

                $order = $orderStage->order;
                $targetStageId = $request->target_stage_id;

                // Integrity Check: target_stage_id must belong to the same order and have a lower sequence
                $targetStage = $order->orderStages()
                    ->where('stage_id', $targetStageId)
                    ->first();

                if (! $targetStage || $targetStage->sequence === null || $targetStage->sequence >= $orderStage->sequence) {
                    throw new \Exception('Destino de remisión inválido.');
                }

                $targetSequence = $targetStage->sequence;
                $reason = str_replace(['|', ':'], [' ', ' '], $request->notes); // Sanitize to avoid format breakage

                // 1. Create the structured log entry
                \App\Models\OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'action' => "remit|from:{$orderStage->stage_id}|to:{$targetStageId}|reason:{$reason}",
                ]);

                // 1.5 Handle Consumption Reversal if applicable
                $corteStage = $order->orderStages()->whereHas('stage.stageGroup', function ($q) {
                    $q->where('name', 'Corte');
                })->first();

                $isCorteReset = $corteStage && $targetSequence <= $corteStage->sequence;

                if ($isCorteReset && $order->orderMaterials()->whereNotNull('consumed_at')->exists()) {
                    $this->inventory->reverseConsumption($order);
                }

                // 2. Clear ALL delivery status fields
                $order->update([
                    'delivered_at' => null,
                    'delivered_by' => null,
                    'herrajeria_delivered_at' => null,
                    'herrajeria_delivered_by' => null,
                    'manual_armado_delivered_at' => null,
                    'manual_armado_delivered_by' => null,
                ]);

                // 3. Reset execution data of all stages starting from target back to current
                $order->orderStages()
                    ->where('sequence', '>=', $targetSequence)
                    ->update([
                        'started_at' => null,
                        'completed_at' => null,
                        'started_by' => null,
                        'completed_by' => null,
                    ]);

                return back()->with('status', 'Pedido remitido.');
            });
        } catch (\Exception $e) {
            return back()->withErrors(['auth' => $e->getMessage()]);
        }
    }

    public function updateNotes(Request $request, OrderStage $orderStage)
    {
        if ($error = $this->checkWorkflowIntegrity($orderStage)) {
            return $error;
        }

        if (! $this->authService->canActOnStage(Auth::user(), $orderStage->order, $orderStage->stage_id)) {
            return back()->withErrors(['auth' => 'No autorizado para esta etapa.']);
        }

        if ($orderStage->order->delivered_at) {
            return back()->withErrors(['auth' => 'No se puede modificar un pedido que ya ha sido entregado.']);
        }

        $request->validate([
            'notes' => 'nullable|string|max:300',
        ]);

        $orderStage->update([
            'notes' => $request->notes,
        ]);

        return back()->with('status', 'Observaciones actualizadas.');
    }

    public function deliverHardware(OrderStage $orderStage)
    {
        if ($error = $this->checkWorkflowIntegrity($orderStage)) {
            return $error;
        }

        $user = Auth::user();
        if (! $this->authService->canActOnStage($user, $orderStage->order, $orderStage->stage_id)) {
            return back()->withErrors(['auth' => 'No autorizado para esta acción.']);
        }

        try {
            DB::transaction(function () use ($orderStage, $user) {
                $orderStage->order->lockForUpdate();

                if ($orderStage->is_pending) {
                    throw new \Exception('No se puede entregar herrajería mientras el pedido esté pendiente.');
                }

                if ($orderStage->order->herrajeria_delivered_at) {
                    throw new \Exception('La herrajería ya ha sido entregada.');
                }

                $orderStage->order->update([
                    'herrajeria_delivered_at' => now(),
                    'herrajeria_delivered_by' => $user->id,
                ]);
            });
        } catch (\Exception $e) {
            return back()->withErrors(['auth' => $e->getMessage()]);
        }

        return back()->with('status', 'Herrajería entregada.');
    }

    public function deliverManual(OrderStage $orderStage)
    {
        if ($error = $this->checkWorkflowIntegrity($orderStage)) {
            return $error;
        }

        $user = Auth::user();
        if (! $this->authService->canActOnStage($user, $orderStage->order, $orderStage->stage_id)) {
            return back()->withErrors(['auth' => 'No autorizado para esta acción.']);
        }

        try {
            DB::transaction(function () use ($orderStage, $user) {
                $orderStage->order->lockForUpdate();

                if ($orderStage->is_pending) {
                    throw new \Exception('No se puede entregar el manual mientras el pedido esté pendiente.');
                }

                if ($orderStage->order->manual_armado_delivered_at) {
                    throw new \Exception('El manual ya ha sido entregado.');
                }

                $orderStage->order->update([
                    'manual_armado_delivered_at' => now(),
                    'manual_armado_delivered_by' => $user->id,
                ]);
            });
        } catch (\Exception $e) {
            return back()->withErrors(['auth' => $e->getMessage()]);
        }

        return back()->with('status', 'Manual de armado entregado.');
    }

    public function markAsPending(Request $request, OrderStage $orderStage)
    {
        if ($error = $this->checkWorkflowIntegrity($orderStage)) {
            return $error;
        }

        $user = Auth::user();

        // Only users with can_edit permission (Admin/Authorized) can mark as pending
        if (! $user->role->hasPermission('orders', 'edit')) {
            return back()->withErrors(['auth' => 'No tiene permisos para marcar como pendiente.']);
        }

        try {
            DB::transaction(function () use ($request, $orderStage) {
                $orderStage->lockForUpdate();

                if ($orderStage->order->delivered_at) {
                    throw new \Exception('No se puede marcar como pendiente un pedido que ya ha sido entregado.');
                }

                // State Validation: Cannot mark a completed stage as pending
                if ($orderStage->completed_at) {
                    throw new \Exception('No se puede marcar como pendiente una etapa que ya ha sido completada.');
                }

                $orderStage->update([
                    'is_pending' => true,
                    'pending_reason' => $request->pending_reason,
                    'pending_marked_by' => Auth::id(),
                    'pending_marked_at' => now(),
                    'started_at' => null,
                    'started_by' => null,
                ]);

                \App\Models\OrderLog::create([
                    'order_id' => $orderStage->order_id,
                    'user_id' => Auth::id(),
                    'action' => 'Etapa marcada como pendiente: '.substr($request->pending_reason, 0, 370),
                ]);
            });
        } catch (\Exception $e) {
            return back()->withErrors(['auth' => $e->getMessage()]);
        }

        return back()->with('status', 'Etapa marcada como pendiente.');
    }

    public function removePending(OrderStage $orderStage)
    {
        if ($error = $this->checkWorkflowIntegrity($orderStage)) {
            return $error;
        }

        $user = Auth::user();

        // Only users with can_edit permission (Admin/Authorized) can remove pending status
        if (! $user->role->hasPermission('orders', 'edit')) {
            return back()->withErrors(['auth' => 'No tiene permisos para quitar el estado pendiente.']);
        }

        try {
            DB::transaction(function () use ($orderStage) {
                $orderStage->lockForUpdate();

                if ($orderStage->order->delivered_at) {
                    throw new \Exception('No se puede modificar un pedido que ya ha sido entregado.');
                }

                // State Validation: Cannot remove pending if it's not pending or already completed
                if (! $orderStage->is_pending) {
                    throw new \Exception('Esta etapa no está marcada como pendiente.');
                }

                if ($orderStage->completed_at) {
                    throw new \Exception('No se puede modificar el estado de una etapa que ya ha sido completada.');
                }

                $orderStage->update([
                    'is_pending' => false,
                ]);

                \App\Models\OrderLog::create([
                    'order_id' => $orderStage->order_id,
                    'user_id' => Auth::id(),
                    'action' => 'Pendiente removido',
                ]);
            });
        } catch (\Exception $e) {
            return back()->withErrors(['auth' => $e->getMessage()]);
        }

        return back()->with('status', 'Estado pendiente removido.');
    }

    public function uploadEvidence(Request $request, OrderStage $orderStage)
    {
        if ($error = $this->checkWorkflowIntegrity($orderStage)) {
            return $error;
        }

        $user = Auth::user();
        if (! $this->authService->canActOnStage($user, $orderStage->order, $orderStage->stage_id)) {
            return back()->withErrors(['auth' => 'No autorizado para esta acción.']);
        }

        $request->validate([
            'evidence_photos' => 'required|array|max:2',
            'evidence_photos.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ], [
            'evidence_photos.max' => 'Máximo 2 fotos de evidencia por pedido.',
            'evidence_photos.*.image' => 'El archivo debe ser una imagen.',
            'evidence_photos.*.max' => 'La imagen no debe pesar más de 5MB.',
        ]);

        try {
            DB::transaction(function () use ($request, $orderStage, $user) {
                $orderStage->order->lockForUpdate();

                // Allow uploading evidence even after delivery to fulfill user request of "uploading it later"

                $evidenciaType = \App\Models\FileType::firstOrCreate(['name' => 'Evidencia']);

                foreach ($request->file('evidence_photos') as $photo) {
                    // Check existing evidence count
                    $count = $orderStage->order->orderFiles()->where('file_type_id', $evidenciaType->id)->count();
                    if ($count >= 2) {
                        throw new \Exception('Ya se han subido las 2 fotos de evidencia permitidas.');
                    }

                    $path = $this->compressAndStore($photo, 'evidence');

                    if ($path) {
                        $orderStage->order->orderFiles()->create([
                            'file_type_id' => $evidenciaType->id,
                            'file_path' => $path,
                            'uploaded_by' => $user->id,
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            return back()->withErrors(['auth' => $e->getMessage()]);
        }

        return back()->with('status', 'Evidencia subida correctamente.');
    }

    public function deleteFile(\App\Models\OrderFile $orderFile)
    {
        $user = Auth::user();

        // Authorization: Admin or the user who uploaded it
        if (! $user->role->hasPermission('orders', 'edit') && $orderFile->uploaded_by !== $user->id) {
            return back()->withErrors(['auth' => 'No autorizado para eliminar este archivo.']);
        }

        // Allow managing files even after delivery to fulfill user request of "uploading it later"

        try {
            DB::transaction(function () use ($orderFile) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($orderFile->file_path);
                $orderFile->delete();
            });
        } catch (\Exception $e) {
            return back()->withErrors(['auth' => 'Error al eliminar el archivo: '.$e->getMessage()]);
        }

        return back()->with('status', 'Archivo eliminado correctamente.');
    }
}
