<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create-leases');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'property_id' => 'required|exists:properties,id',
            'tenant_name' => 'required|string|max:255',
            'tenant_email' => 'required|email|max:255',
            'tenant_phone' => 'required|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'tenant_address' => 'required|string|max:500',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:0.01|max:999999.99',
            'security_deposit' => 'required|numeric|min:0|max:999999.99',
            'late_fee' => 'nullable|numeric|min:0|max:99999.99',
            'grace_period_days' => 'nullable|integer|min:0|max:30',
            'terms_conditions' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'property_id.required' => 'Property is required.',
            'property_id.exists' => 'Selected property does not exist.',
            'tenant_name.required' => 'Tenant name is required.',
            'tenant_name.max' => 'Tenant name cannot exceed 255 characters.',
            'tenant_email.required' => 'Tenant email is required.',
            'tenant_email.email' => 'Tenant email must be a valid email address.',
            'tenant_email.max' => 'Tenant email cannot exceed 255 characters.',
            'tenant_phone.required' => 'Tenant phone number is required.',
            'tenant_phone.regex' => 'Tenant phone number must be a valid phone number.',
            'tenant_phone.max' => 'Tenant phone number cannot exceed 20 characters.',
            'tenant_address.required' => 'Tenant address is required.',
            'tenant_address.max' => 'Tenant address cannot exceed 500 characters.',
            'start_date.required' => 'Lease start date is required.',
            'start_date.date' => 'Lease start date must be a valid date.',
            'start_date.after_or_equal' => 'Lease start date cannot be in the past.',
            'end_date.required' => 'Lease end date is required.',
            'end_date.date' => 'Lease end date must be a valid date.',
            'end_date.after' => 'Lease end date must be after start date.',
            'monthly_rent.required' => 'Monthly rent is required.',
            'monthly_rent.min' => 'Monthly rent must be at least ₦0.01.',
            'monthly_rent.max' => 'Monthly rent cannot exceed ₦999,999.99.',
            'security_deposit.required' => 'Security deposit is required.',
            'security_deposit.min' => 'Security deposit must be at least ₦0.',
            'security_deposit.max' => 'Security deposit cannot exceed ₦999,999.99.',
            'late_fee.min' => 'Late fee must be at least ₦0.',
            'late_fee.max' => 'Late fee cannot exceed ₦99,999.99.',
            'grace_period_days.min' => 'Grace period cannot be negative.',
            'grace_period_days.max' => 'Grace period cannot exceed 30 days.',
            'terms_conditions.max' => 'Terms and conditions cannot exceed 2000 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'property_id' => 'property',
            'tenant_name' => 'tenant name',
            'tenant_email' => 'tenant email',
            'tenant_phone' => 'tenant phone',
            'tenant_address' => 'tenant address',
            'start_date' => 'lease start date',
            'end_date' => 'lease end date',
            'monthly_rent' => 'monthly rent',
            'security_deposit' => 'security deposit',
            'late_fee' => 'late fee',
            'grace_period_days' => 'grace period',
            'terms_conditions' => 'terms and conditions',
        ];
    }
}
