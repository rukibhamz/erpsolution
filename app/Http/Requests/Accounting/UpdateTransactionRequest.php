<?php

namespace App\Http\Requests\Accounting;

use App\Rules\ValidNigerianAmount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|exists:accounts,id',
            'transaction_type' => 'required|in:income,expense,transfer',
            'amount' => ['required', new ValidNigerianAmount],
            'description' => 'required|string|max:500',
            'transaction_date' => 'required|date|before_or_equal:today',
            'category' => 'required|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'payment_method' => 'required|in:cash,bank_transfer,check,card,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'status' => 'required|in:pending,approved,rejected,cancelled',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'account_id.required' => 'Please select an account.',
            'account_id.exists' => 'The selected account does not exist.',
            'transaction_type.required' => 'Transaction type is required.',
            'transaction_type.in' => 'Invalid transaction type selected.',
            'amount.required' => 'Transaction amount is required.',
            'description.required' => 'Transaction description is required.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future.',
            'category.required' => 'Transaction category is required.',
            'category.max' => 'Category cannot exceed 100 characters.',
            'subcategory.max' => 'Subcategory cannot exceed 100 characters.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method selected.',
            'reference_number.max' => 'Reference number cannot exceed 100 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'status.required' => 'Transaction status is required.',
            'status.in' => 'Invalid transaction status selected.',
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
            'amount' => 'amount',
            'description' => 'description',
            'transaction_date' => 'transaction date',
            'category' => 'category',
            'subcategory' => 'subcategory',
            'payment_method' => 'payment method',
            'reference_number' => 'reference number',
            'notes' => 'notes',
            'status' => 'status',
        ];
    }
}
