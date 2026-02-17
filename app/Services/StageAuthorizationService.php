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
        // Non-admin users can only act on stages linked to their role via role_stages
        $hasRoleAccess = $user->role->stages()
            ->where('stages.id', $stageId)
            ->exists();

        if (!$hasRoleAccess) {
            return false;
        }

        // The order must require that stage
        $targetOrderStage = $order->orderStages()
            ->where('stage_id', $stageId)
            ->first();

        if (!$targetOrderStage) {
            return false;
        }

        // All previous required stages must be completed
        // "Stages are linear per order" - using order_stages.id to determine sequence
        $unfinishedPreviousStages = $order->orderStages()
            ->where('sequence', '<', $targetOrderStage->sequence)
            ->whereNull('completed_at')
            ->exists();

        return !$unfinishedPreviousStages;
    }
}
