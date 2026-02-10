<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('stock_movements.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $user->can('stock_movements.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('stock.in') || $user->can('stock.out') || $user->can('stock.transfer') || $user->can('stock.adjustment');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StockMovement $stockMovement): bool
    {
        // Check if user has the global stock_movements.edit permission
        if ($user->can('stock_movements.edit')) {
            return true;
        }
        
        // Check permission based on movement type
        $permission = match ($stockMovement->type) {
            'out' => 'stock.out',
            'transfer' => 'stock.transfer',
            'adjustment' => 'stock.adjustment',
            default => 'stock.in',
        };

        return $user->can($permission);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StockMovement $stockMovement): bool
    {
        // Check if user has the global stock_movements.delete permission
        if ($user->can('stock_movements.delete')) {
            return true;
        }
        
        // Check permission based on movement type
        $permission = match ($stockMovement->type) {
            'out' => 'stock.out',
            'transfer' => 'stock.transfer',
            'adjustment' => 'stock.adjustment',
            default => 'stock.in',
        };

        return $user->can($permission);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StockMovement $stockMovement): bool
    {
        return $user->can('stock_movements.view');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StockMovement $stockMovement): bool
    {
        return false;
    }
}
