<?php

namespace App\Http\Requests\Booking;

use App\Rules\ValidNigerianAmount;
use App\Rules\ValidNigerianState;
use App\Rules\ValidNigerianCity;
use App\Rules\ValidDateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today',
                new ValidDateRange(now()->format('Y-m-d'), now()->addYears(2)->format('Y-m-d'))
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
                new ValidDateRange($this->start_date, now()->addYears(2)->format('Y-m-d'))
            ],
            'venue' => 'required|string|max:255',
            'city' => ['required', new ValidNigerianCity],
            'state' => ['required', new ValidNigerianState],
            'capacity' => 'required|integer|min:1|max:10000',
            'price' => ['required', new ValidNigerianAmount],
            'status' => 'required|in:draft,published,cancelled,completed',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
            'title.required' => 'Event title is required.',
            'title.max' => 'Event title cannot exceed 255 characters.',
            'description.required' => 'Event description is required.',
            'description.max' => 'Event description cannot exceed 2000 characters.',
            'start_date.required' => 'Event start date is required.',
            'start_date.after_or_equal' => 'Event start date cannot be in the past.',
            'end_date.required' => 'Event end date is required.',
            'end_date.after' => 'Event end date must be after start date.',
            'venue.required' => 'Event venue is required.',
            'venue.max' => 'Venue name cannot exceed 255 characters.',
            'city.required' => 'Event city is required.',
            'state.required' => 'Event state is required.',
            'capacity.required' => 'Event capacity is required.',
            'capacity.min' => 'Event capacity must be at least 1.',
            'capacity.max' => 'Event capacity cannot exceed 10,000.',
            'price.required' => 'Event price is required.',
            'status.required' => 'Event status is required.',
            'status.in' => 'Invalid event status selected.',
            'images.*.image' => 'Uploaded files must be images.',
            'images.*.mimes' => 'Images must be in JPEG, PNG, JPG, or GIF format.',
            'images.*.max' => 'Each image must not exceed 2MB.',
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
            'title' => 'event title',
            'description' => 'event description',
            'start_date' => 'event start date',
            'end_date' => 'event end date',
            'venue' => 'event venue',
            'city' => 'event city',
            'state' => 'event state',
            'capacity' => 'event capacity',
            'price' => 'event price',
            'status' => 'event status',
            'images' => 'event images',
            'terms_conditions' => 'terms and conditions',
            'notes' => 'notes',
        ];
    }
}
