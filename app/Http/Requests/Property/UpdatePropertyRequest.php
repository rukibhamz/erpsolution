<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit-properties');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $propertyId = $this->route('property')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('properties', 'name')->ignore($propertyId)
            ],
            'description' => 'nullable|string|max:2000',
            'property_type_id' => 'required|exists:property_types,id',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'rent_amount' => 'required|numeric|min:0|max:999999.99',
            'deposit_amount' => 'nullable|numeric|min:0|max:999999.99',
            'bedrooms' => 'nullable|integer|min:0|max:50',
            'bathrooms' => 'nullable|integer|min:0|max:20',
            'area_sqft' => 'nullable|numeric|min:0|max:999999.99',
            'amenities' => 'nullable|array|max:20',
            'amenities.*' => 'string|max:100',
            'status' => ['required', Rule::in(['available', 'occupied', 'maintenance', 'unavailable'])],
            'is_active' => 'boolean',
            // SECURITY FIX: Strengthened file upload validation
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048|dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Property name is required.',
            'name.unique' => 'A property with this name already exists.',
            'property_type_id.required' => 'Property type is required.',
            'property_type_id.exists' => 'Selected property type does not exist.',
            'address.required' => 'Property address is required.',
            'city.required' => 'City is required.',
            'state.required' => 'State is required.',
            'rent_amount.required' => 'Rent amount is required.',
            'rent_amount.min' => 'Rent amount must be at least ₦0.',
            'rent_amount.max' => 'Rent amount cannot exceed ₦999,999.99.',
            'deposit_amount.min' => 'Deposit amount must be at least ₦0.',
            'deposit_amount.max' => 'Deposit amount cannot exceed ₦999,999.99.',
            'bedrooms.min' => 'Number of bedrooms cannot be negative.',
            'bedrooms.max' => 'Number of bedrooms cannot exceed 50.',
            'bathrooms.min' => 'Number of bathrooms cannot be negative.',
            'bathrooms.max' => 'Number of bathrooms cannot exceed 20.',
            'area_sqft.min' => 'Area cannot be negative.',
            'area_sqft.max' => 'Area cannot exceed 999,999.99 sq ft.',
            'amenities.max' => 'Maximum 20 amenities allowed.',
            'amenities.*.max' => 'Each amenity must not exceed 100 characters.',
            'status.required' => 'Property status is required.',
            'status.in' => 'Invalid property status selected.',
            'images.*.image' => 'Uploaded file must be an image.',
            'images.*.mimes' => 'Image must be a JPEG, PNG, or JPG file.',
            'images.*.max' => 'Image size cannot exceed 2MB.',
            'images.*.dimensions' => 'Image dimensions must be between 100x100 and 2000x2000 pixels.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'property_type_id' => 'property type',
            'rent_amount' => 'rent amount',
            'deposit_amount' => 'deposit amount',
            'area_sqft' => 'area',
        ];
    }
}
