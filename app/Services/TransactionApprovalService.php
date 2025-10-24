<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionApprovalService
{
    /**
     * Approve transaction with comprehensive validation
     */
    public function approveTransaction(Transaction $transaction, User $approver): array
    {
        $errors = [];
        $warnings = [];

        // Validate approver permissions
        if (!$approver->can('approve-transactions')) {
            $errors[] = 'User does not have permission to approve transactions.';
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Validate transaction state
        if ($transaction->isApproved()) {
            $errors[] = 'Transaction is already approved.';
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        if ($transaction->status === 'cancelled') {
            $errors[] = 'Cannot approve cancelled transaction.';
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        if ($transaction->status === 'rejected') {
            $errors[] = 'Cannot approve rejected transaction.';
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Validate account
        if (!$transaction->account) {
            $errors[] = 'Transaction account not found.';
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        if (!$transaction->account->isActive()) {
            $errors[] = 'Transaction account is not active.';
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Validate transaction amount
        if ($transaction->amount <= 0) {
            $errors[] = 'Transaction amount must be greater than zero.';
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Check for duplicate transactions
        $duplicateTransaction = Transaction::where('id', '!=', $transaction->getKey())
            ->where('account_id', $transaction->account_id)
            ->where('amount', $transaction->amount)
            ->where('transaction_date', $transaction->transaction_date)
            ->where('description', $transaction->description)
            ->where('status', 'approved')
            ->first();

        if ($duplicateTransaction) {
            $warnings[] = 'Similar transaction already exists and is approved.';
        }

        // If there are errors, return them
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Approve transaction with rollback capability
        try {
            DB::transaction(function () use ($transaction, $approver) {
                // Update transaction status
                $transaction->update([
                    'status' => 'approved',
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                ]);

                // Update account balance
                $this->updateAccountBalance($transaction->account);

                Log::info("Transaction {$transaction->getKey()} approved by user {$approver->getKey()}");
            });

            return ['success' => true, 'errors' => [], 'warnings' => $warnings];

        } catch (\Exception $e) {
            Log::error("Failed to approve transaction {$transaction->getKey()}: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to approve transaction: ' . $e->getMessage()], 'warnings' => $warnings];
        }
    }

    /**
     * Reject transaction
     */
    public function rejectTransaction(Transaction $transaction, User $rejector, string $reason = null): array
    {
        // Validate rejector permissions
        if (!$rejector->can('reject-transactions')) {
            return ['success' => false, 'errors' => ['User does not have permission to reject transactions.']];
        }

        // Validate transaction state
        if ($transaction->isApproved()) {
            return ['success' => false, 'errors' => ['Cannot reject already approved transaction.']];
        }

        if ($transaction->status === 'cancelled') {
            return ['success' => false, 'errors' => ['Cannot reject cancelled transaction.']];
        }

        try {
            $transaction->update([
                'status' => 'rejected',
                'notes' => $transaction->notes . ($reason ? "\nRejection reason: " . $reason : ''),
            ]);

            Log::info("Transaction {$transaction->getKey()} rejected by user {$rejector->getKey()}");
            return ['success' => true, 'errors' => []];

        } catch (\Exception $e) {
            Log::error("Failed to reject transaction {$transaction->getKey()}: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to reject transaction: ' . $e->getMessage()]];
        }
    }

    /**
     * Cancel transaction
     */
    public function cancelTransaction(Transaction $transaction, User $canceller, string $reason = null): array
    {
        // Validate canceller permissions
        if (!$canceller->can('delete-transactions')) {
            return ['success' => false, 'errors' => ['User does not have permission to cancel transactions.']];
        }

        // Validate transaction state
        if ($transaction->isApproved()) {
            return ['success' => false, 'errors' => ['Cannot cancel approved transaction.']];
        }

        try {
            $transaction->update([
                'status' => 'cancelled',
                'notes' => $transaction->notes . ($reason ? "\nCancellation reason: " . $reason : ''),
            ]);

            Log::info("Transaction {$transaction->getKey()} cancelled by user {$canceller->getKey()}");
            return ['success' => true, 'errors' => []];

        } catch (\Exception $e) {
            Log::error("Failed to cancel transaction {$transaction->getKey()}: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to cancel transaction: ' . $e->getMessage()]];
        }
    }

    /**
     * Update account balance based on approved transactions
     */
    public function updateAccountBalance(Account $account): void
    {
        $balance = Transaction::where('account_id', $account->getKey())
            ->where('status', 'approved')
            ->sum(DB::raw('CASE 
                WHEN transaction_type = "income" THEN amount 
                WHEN transaction_type = "expense" THEN -amount 
                WHEN transaction_type = "transfer" THEN 
                    CASE WHEN account_id = ' . $account->getKey() . ' THEN amount ELSE -amount END
                ELSE 0 
            END'));

        $account->update(['balance' => $balance]);
    }

    /**
     * Validate transaction before approval
     */
    public function validateTransaction(Transaction $transaction): array
    {
        $errors = [];
        $warnings = [];

        // Check account exists and is active
        if (!$transaction->account) {
            $errors[] = 'Account not found.';
        } elseif (!$transaction->account->isActive()) {
            $errors[] = 'Account is not active.';
        }

        // Check amount is positive
        if ($transaction->amount <= 0) {
            $errors[] = 'Transaction amount must be greater than zero.';
        }

        // Check transaction date is not in the future
        if ($transaction->transaction_date > now()) {
            $warnings[] = 'Transaction date is in the future.';
        }

        // Check for duplicate transactions
        $duplicateCount = Transaction::where('id', '!=', $transaction->id)
            ->where('account_id', $transaction->account_id)
            ->where('amount', $transaction->amount)
            ->where('transaction_date', $transaction->transaction_date)
            ->where('description', $transaction->description)
            ->count();

        if ($duplicateCount > 0) {
            $warnings[] = 'Similar transactions exist.';
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Get transaction approval summary
     */
    public function getApprovalSummary(Transaction $transaction): array
    {
        $validation = $this->validateTransaction($transaction);
        
        return [
            'transaction' => $transaction,
            'account' => $transaction->account,
            'can_approve' => empty($validation['errors']),
            'errors' => $validation['errors'],
            'warnings' => $validation['warnings'],
            'approval_requirements' => [
                'account_active' => $transaction->account ? $transaction->account->isActive() : false,
                'amount_positive' => $transaction->amount > 0,
                'not_approved' => !$transaction->isApproved(),
                'not_cancelled' => $transaction->status !== 'cancelled',
            ],
        ];
    }
}
