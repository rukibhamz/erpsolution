<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lease_reference' => $this->lease_reference,
            'property' => [
                'id' => $this->property_id,
                'name' => $this->whenLoaded('property', function () {
                    return $this->property->name;
                }),
                'property_code' => $this->whenLoaded('property', function () {
                    return $this->property->property_code;
                }),
            ],
            'tenant' => [
                'name' => $this->tenant_name,
                'email' => $this->tenant_email,
                'phone' => $this->tenant_phone,
                'address' => $this->tenant_address,
            ],
            'lease_period' => [
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'duration_days' => $this->start_date->diffInDays($this->end_date),
            ],
            'financial' => [
                'monthly_rent' => $this->monthly_rent,
                'security_deposit' => $this->security_deposit,
                'late_fee' => $this->late_fee,
                'grace_period_days' => $this->grace_period_days,
            ],
            'status' => [
                'current' => $this->status,
                'is_active' => $this->status === 'active',
                'is_expired' => $this->end_date < now(),
            ],
            'terms' => [
                'terms_conditions' => $this->terms_conditions,
                'notes' => $this->notes,
            ],
            'payments' => $this->when($this->relationLoaded('payments'), function () {
                return PaymentResource::collection($this->payments);
            }),
            'timestamps' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
        ];
    }
}
