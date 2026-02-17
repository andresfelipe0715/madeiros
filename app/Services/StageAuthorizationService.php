<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;

class StageAuthorizationService
{
    /**
     * Determine if the user can act on a given stage for a given order.
     */
    public function canActOnStage(User $user, Order $order, int $stageId): bool
    {
        // 1. Non-admin users can only act on stages linked to their role via role_stages
        $hasRoleAccess = $user->role->stages()
            ->where('stages.id', $stageId)
            ->exists();

        if (! $hasRoleAccess) {
            return false;
        }

        // 2. The order must require that stage
        $targetOrderStage = $order->orderStages()
            ->where('stage_id', $stageId)
            ->first();

        if (! $targetOrderStage) {
            return false;
        }

        // 3. Admin Override: Users with role_order_permissions.can_edit = true can bypass the queue
        $canOverride = $user->role->orderPermission?->can_edit ?? false;
        if ($canOverride) {
            return true;
        }

        // 4. Internal Sequence: All previous required stages in this order must be completed
        $unfinishedPreviousStages = $order->orderStages()
            ->where('sequence', '<', $targetOrderStage->sequence)
            ->whereNull('completed_at')
            ->exists();

        if ($unfinishedPreviousStages) {
            return false;
        }

        // 5. Queue Logic: Order must be the next pending order in this stage
        return $this->isNextInQueue($order, $stageId);
    }

    /**
     * Determine if an order is the next pending order in a specific stage.
     */
    public function isNextInQueue(Order $order, int $stageId): bool
    {
        // Find other orders that have this stage as "pending" or "in progress"
        // and are "ready" for it (their own internal sequence is complete)
        // and have a lower ID (original creation order)
        // and ARE NOT marked as Pendiente (is_pending = false)
        return ! \App\Models\OrderStage::where('stage_id', $stageId)
            ->where('order_id', '<', $order->id)
            ->whereNull('completed_at')
            ->where('is_pending', false) // Skip pending orders in queue
            ->whereNotExists(function ($query) {
                // The blocking order must also be "ready" for this stage
                $query->select('id')
                    ->from('order_stages as os2')
                    ->whereColumn('os2.order_id', 'order_stages.order_id')
                    ->whereColumn('os2.sequence', '<', 'order_stages.sequence')
                    ->whereNull('os2.completed_at');
            })
            ->exists();
    }
}
