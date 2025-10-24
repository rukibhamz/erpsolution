<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create-transactions');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|exists:accounts,id',
            'transaction_type' => ['required', Rule::in(['income', 'expense', 'transfer', 'adjustment'])],
            'amount' => 'required|numeric|min:0.01|max:99999999.99',
            'description' => 'required|string|max:500',
            'transaction_date' => 'required|date|before_or_equal:today',
            'category' => 'nullable|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'account_id.required' => 'Account is required.',
            'account_id.exists' => 'Selected account does not exist.',
            'transaction_type.required' => 'Transaction type is required.',
            'transaction_type.in' => 'Invalid transaction type selected.',
            'amount.required' => 'Transaction amount is required.',
            'amount.min' => 'Transaction amount must be at least ₦0.01.',
            'amount.max' => 'Transaction amount cannot exceed ₦99,999,999.99.',
            'description.required' => 'Transaction description is required.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.date' => 'Transaction date must be a valid date.',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future.',
            'category.max' => 'Category cannot exceed 100 characters.',
            'subcategory.max' => 'Subcategory cannot exceed 100 characters.',
            'payment_method.max' => 'Payment method cannot exceed 50 characters.',
            'reference_number.max' => 'Reference number cannot exceed 100 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'account_id' => 'account',
            'transaction_type' => 'transaction type',
            'transaction_date' => 'transaction date',
            'reference_number' => 'reference number',
        ];
    }
}
