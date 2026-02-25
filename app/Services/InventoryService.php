<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Reserve materials for a new order.
     */
    public function reserve(Order $order, array $materials): void
    {
        DB::transaction(function () use ($order, $materials) {
            foreach ($materials as $data) {
                $materialId = $data['material_id'];
                $quantity = $data['estimated_quantity'];

                $material = Material::lockForUpdate()->findOrFail($materialId);

                if ($material->availableQuantity() < $quantity) {
                    throw new Exception("Stock insuficiente para: {$material->name}");
                }

                $order->orderMaterials()->create([
                    'material_id' => $material->id,
                    'estimated_quantity' => $quantity,
                    'notes' => $data['notes'] ?? null,
                ]);

                $material->increment('reserved_quantity', $quantity);

                \App\Models\OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'action' => "inventory|reserve|material:{$material->name}|qty:{$quantity}",
                ]);
            }
        });
    }

    /**
     * Sync/Adjust materials for an existing order.
     * Can handle updates, new additions, and cancellations.
     */
    public function adjust(Order $order, array $materials): void
    {
        if ($order->delivered_at) {
            throw new Exception('No se pueden modificar materiales de una orden ya entregada.');
        }

        DB::transaction(function () use ($order, $materials) {
            // Load ALL materials for this order (active AND cancelled) to handle restoration/duplication prevention
            $existingMaterials = $order->orderMaterials()->get()->keyBy('id');
            $processedIds = [];

            foreach ($materials as $data) {
                $id = $data['id'] ?? null;
                $materialId = $data['material_id'];
                $newEstimated = $data['estimated_quantity'];
                $actual = $data['actual_quantity'] ?? null;
                $cancelled = filter_var($data['cancelled'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($id && $existingMaterials->has($id)) {
                    $om = $existingMaterials->get($id);
                    $material = $om->material()->lockForUpdate()->first();

                    // Logic Transitions:

                    // 1. WAS ACTIVE -> NOW CANCELLED
                    if (! $om->cancelled_at && $cancelled) {
                        $material->decrement('reserved_quantity', $om->estimated_quantity);
                        $om->update(['cancelled_at' => now(), 'notes' => $data['notes'] ?? $om->notes]);

                        \App\Models\OrderLog::create([
                            'order_id' => $order->id,
                            'user_id' => \Illuminate\Support\Facades\Auth::id(),
                            'action' => "inventory|cancel|material:{$material->name}|qty:{$om->estimated_quantity}",
                        ]);
                    }
                    // 2. WAS CANCELLED -> NOW ACTIVE (Restoration)
                    elseif ($om->cancelled_at && ! $cancelled) {
                        if ($material->availableQuantity() < $newEstimated) {
                            throw new Exception("Stock insuficiente para restaurar: {$material->name}");
                        }
                        $material->increment('reserved_quantity', $newEstimated);
                        $om->update([
                            'cancelled_at' => null,
                            'material_id' => $materialId, // Allow switching during restoration
                            'estimated_quantity' => $newEstimated,
                            'actual_quantity' => $actual,
                            'notes' => $data['notes'] ?? null,
                        ]);

                        \App\Models\OrderLog::create([
                            'order_id' => $order->id,
                            'user_id' => \Illuminate\Support\Facades\Auth::id(),
                            'action' => "inventory|restore|material:{$material->name}|qty:{$newEstimated}",
                        ]);
                    }
                    // 3. REMAINED CANCELLED (Update metadata only if needed, usually no-op for stock)
                    elseif ($om->cancelled_at && $cancelled) {
                        $om->update(['notes' => $data['notes'] ?? $om->notes]);
                    }
                    // 4. REMAINED ACTIVE (Update quantities/material)
                    else {
                        if ($om->material_id != $materialId) {
                            // Material Switch
                            $material->decrement('reserved_quantity', $om->estimated_quantity);
                            $newMaterial = Material::lockForUpdate()->findOrFail($materialId);
                            if ($newMaterial->availableQuantity() < $newEstimated) {
                                throw new Exception("Stock insuficiente para cambiar a: {$newMaterial->name}");
                            }
                            $newMaterial->increment('reserved_quantity', $newEstimated);
                            $om->update([
                                'material_id' => $materialId,
                                'estimated_quantity' => $newEstimated,
                                'actual_quantity' => $actual,
                                'notes' => $data['notes'] ?? null,
                            ]);
                            \App\Models\OrderLog::create([
                                'order_id' => $order->id,
                                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                                'action' => "inventory|switch|from:{$material->name}|to:{$newMaterial->name}|qty:{$newEstimated}",
                            ]);
                        } else {
                            // Standard adjustment
                            $diff = $newEstimated - $om->estimated_quantity;
                            if ($diff > 0 && $material->availableQuantity() < $diff) {
                                throw new Exception("Stock insuficiente para ajustar: {$material->name}");
                            }
                            $om->update([
                                'estimated_quantity' => $newEstimated,
                                'actual_quantity' => $actual,
                                'notes' => $data['notes'] ?? null,
                            ]);
                            if ($diff != 0) {
                                $material->increment('reserved_quantity', $diff);
                            }
                            \App\Models\OrderLog::create([
                                'order_id' => $order->id,
                                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                                'action' => "inventory|adjust|material:{$material->name}|qty_old:{$om->getOriginal('estimated_quantity')}|qty_new:{$newEstimated}",
                            ]);
                        }
                    }

                    $processedIds[] = $id;
                } else {
                    // New reservation (ignore if payload says cancelled, although button shouldn't allow it)
                    if ($cancelled) {
                        continue;
                    }

                    $material = Material::lockForUpdate()->findOrFail($materialId);
                    if ($material->availableQuantity() < $newEstimated) {
                        throw new Exception("Stock insuficiente para nuevo material: {$material->name}");
                    }

                    $newOm = $order->orderMaterials()->create([
                        'material_id' => $materialId,
                        'estimated_quantity' => $newEstimated,
                        'actual_quantity' => $actual,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    $material->increment('reserved_quantity', $newEstimated);

                    \App\Models\OrderLog::create([
                        'order_id' => $order->id,
                        'user_id' => \Illuminate\Support\Facades\Auth::id(),
                        'action' => "inventory|add|material:{$material->name}|qty:{$newEstimated}",
                    ]);

                    $processedIds[] = $newOm->id;
                }
            }
        });
    }

    public function consume(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $activeMaterials = $order->orderMaterials()->active()->get();

            foreach ($activeMaterials as $om) {
                $material = $om->material()->first();

                // Step 1 - Ensure actual usage exists
                if ($om->actual_quantity === null) {
                    $om->update(['actual_quantity' => $om->estimated_quantity]);
                }

                $finalActual = $om->fresh()->actual_quantity;

                // Step 2 & 3 - Release reservation & Deduct stock
                // materials.reserved_quantity -= estimated_quantity
                // materials.stock_quantity -= actual_quantity

                if ($material->stock_quantity < $finalActual) {
                    throw new Exception("Stock insuficiente para: {$material->name}. No se puede finalizar la entrega.");
                }

                $material->decrement('reserved_quantity', $om->estimated_quantity);
                $material->decrement('stock_quantity', $finalActual);

                \App\Models\OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'action' => "inventory|consume|material:{$material->name}|qty_estimated:{$om->estimated_quantity}|qty_actual:{$finalActual}",
                ]);
            }
        });
    }

    /**
     * Correct actual consumption after delivery.
     * Difference = new - old. Adjust stock based on difference.
     */
    public function correctActual(\App\Models\OrderMaterial $om, float $newActual): void
    {
        DB::transaction(function () use ($om, $newActual) {
            $material = $om->material()->first();
            $oldActual = (float) $om->actual_quantity;
            $diff = $newActual - $oldActual;

            if ($diff > 0 && $material->stock_quantity < $diff) {
                throw new Exception("Stock insuficiente para corregir consumo: {$material->name}");
            }

            $material->decrement('stock_quantity', $diff);
            $om->update(['actual_quantity' => $newActual]);

            \App\Models\OrderLog::create([
                'order_id' => $om->order_id,
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'action' => "inventory|correction|material:{$material->name}|old:{$oldActual}|new:{$newActual}|diff:{$diff}",
            ]);
        });
    }

    /**
     * Undo consumption and restore reservations when an order is remitted/reopened.
     */
    public function reverseConsumption(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $activeMaterials = $order->orderMaterials()->active()->get();

            foreach ($activeMaterials as $om) {
                $material = $om->material()->first();

                $actualUsed = (float) $om->actual_quantity;
                $estimated = (float) $om->estimated_quantity;

                // Restore stock and re-reserve
                $material->increment('stock_quantity', $actualUsed);
                $material->increment('reserved_quantity', $estimated);

                // Reset actual_quantity for the reopened order
                $om->update(['actual_quantity' => null]);

                \App\Models\OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'action' => "inventory|reverse_consumption|material:{$material->name}|restored_stock:{$actualUsed}|re_reserved:{$estimated}",
                ]);
            }
        });
    }

    /**
     * Release all reservations (e.g., order cancelled).
     */
    public function release(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $activeMaterials = $order->orderMaterials()->active()->get();

            foreach ($activeMaterials as $om) {
                $material = $om->material()->lockForUpdate()->first();

                if ($material->reserved_quantity < $om->estimated_quantity) {
                    $material->update(['reserved_quantity' => 0]);
                } else {
                    $material->decrement('reserved_quantity', $om->estimated_quantity);
                }

                $om->update(['cancelled_at' => now()]);

                \App\Models\OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'action' => "inventory|release|material:{$material->name}|qty:{$om->estimated_quantity}",
                ]);
            }
        });
    }
}
