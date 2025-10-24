<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
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
            'name' => $this->name,
            'property_code' => $this->property_code,
            'description' => $this->description,
            'property_type' => [
                'id' => $this->property_type_id,
                'name' => $this->whenLoaded('propertyType', function () {
                    return $this->propertyType->name;
                }),
            ],
            'address' => [
                'street' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code,
                'full_address' => $this->full_address,
            ],
            'financial' => [
                'rent_amount' => $this->rent_amount,
                'deposit_amount' => $this->deposit_amount,
                'formatted_rent_amount' => $this->formatted_rent_amount,
                'formatted_deposit_amount' => $this->formatted_deposit_amount,
            ],
            'specifications' => [
                'bedrooms' => $this->bedrooms,
                'bathrooms' => $this->bathrooms,
                'area_sqft' => $this->area_sqft,
                'amenities' => $this->amenities,
            ],
            'status' => [
                'current' => $this->status,
                'color' => $this->status_color,
                'is_active' => $this->is_active,
                'is_available' => $this->isAvailable(),
                'is_occupied' => $this->isOccupied(),
            ],
            'images' => $this->images,
            'current_lease' => $this->when($this->relationLoaded('currentLease'), function () {
                return $this->currentLease ? new LeaseResource($this->currentLease) : null;
            }),
            'timestamps' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
        ];
    }
}
