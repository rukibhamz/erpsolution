<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Auth\Access\Response;

class TransactionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-transactions');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->can('view-transactions');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create-transactions');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        // Only allow editing of pending transactions
        if ($transaction->status !== 'pending') {
            return false;
        }
        
        return $user->can('edit-transactions');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        // Only allow deletion of pending transactions
        if ($transaction->status !== 'pending') {
            return false;
        }
        
        return $user->can('delete-transactions');
    }

    /**
     * Determine whether the user can approve the transaction.
     */
    public function approve(User $user, Transaction $transaction): bool
    {
        return $user->can('approve-transactions');
    }

    /**
     * Determine whether the user can reject the transaction.
     */
    public function reject(User $user, Transaction $transaction): bool
    {
        return $user->can('reject-transactions');
    }
}
