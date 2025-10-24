<?php

namespace App\Http\Requests\Property;

use App\Rules\ValidNigerianPhone;
use App\Rules\ValidNigerianAmount;
use App\Rules\NoLeaseOverlap;
use App\Rules\ValidDateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaseRequest extends FormRequest
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
        $lease = $this->route('lease');
        
        return [
            'property_id' => [
                'required',
                'exists:properties,id',
                Rule::unique('leases')->ignore($lease->id)->where(function ($query) {
                    return $query->where('status', 'active');
                })
            ],
            'tenant_name' => 'required|string|max:255',
            'tenant_email' => 'required|email|max:255',
            'tenant_phone' => ['required', new ValidNigerianPhone],
            'tenant_address' => 'required|string|max:500',
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today',
                new ValidDateRange(now()->format('Y-m-d'), now()->addYears(10)->format('Y-m-d'))
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
                new ValidDateRange($this->start_date, now()->addYears(10)->format('Y-m-d')),
                // BUSINESS LOGIC FIX: Add lease overlap validation for updates
                new NoLeaseOverlap($this->input('property_id'), $lease->id)
            ],
            'monthly_rent' => ['required', new ValidNigerianAmount],
            'security_deposit' => ['required', new ValidNigerianAmount],
            'late_fee' => ['nullable', new ValidNigerianAmount],
            'grace_period_days' => 'nullable|integer|min:0|max:30',
            'status' => 'required|in:active,terminated,cancelled',
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
            'property_id.required' => 'Please select a property.',
            'property_id.exists' => 'The selected property does not exist.',
            'property_id.unique' => 'This property already has an active lease.',
            'tenant_name.required' => 'Tenant name is required.',
            'tenant_name.max' => 'Tenant name cannot exceed 255 characters.',
            'tenant_email.required' => 'Tenant email is required.',
            'tenant_email.email' => 'Please enter a valid email address.',
            'tenant_phone.required' => 'Tenant phone number is required.',
            'tenant_address.required' => 'Tenant address is required.',
            'start_date.required' => 'Lease start date is required.',
            'start_date.after_or_equal' => 'Lease start date cannot be in the past.',
            'end_date.required' => 'Lease end date is required.',
            'end_date.after' => 'Lease end date must be after start date.',
            'monthly_rent.required' => 'Monthly rent is required.',
            'security_deposit.required' => 'Security deposit is required.',
            'grace_period_days.max' => 'Grace period cannot exceed 30 days.',
            'status.required' => 'Lease status is required.',
            'status.in' => 'Invalid lease status selected.',
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
