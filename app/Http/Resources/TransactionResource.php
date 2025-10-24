<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'transaction_reference' => $this->transaction_reference,
            'account' => [
                'id' => $this->account_id,
                'name' => $this->whenLoaded('account', function () {
                    return $this->account->account_name;
                }),
                'type' => $this->whenLoaded('account', function () {
                    return $this->account->account_type;
                }),
            ],
            'transaction_details' => [
                'type' => $this->transaction_type,
                'amount' => $this->amount,
                'formatted_amount' => $this->formatted_amount,
                'description' => $this->description,
                'transaction_date' => $this->transaction_date,
            ],
            'categorization' => [
                'category' => $this->category,
                'subcategory' => $this->subcategory,
                'payment_method' => $this->payment_method,
                'reference_number' => $this->reference_number,
            ],
            'status' => [
                'current' => $this->status,
                'color' => $this->status_color,
                'is_approved' => $this->isApproved(),
                'is_pending' => $this->isPending(),
                'is_rejected' => $this->isRejected(),
            ],
            'approval' => [
                'approved_by' => $this->whenLoaded('approvedBy', function () {
                    return [
                        'id' => $this->approvedBy->id,
                        'name' => $this->approvedBy->name,
                        'email' => $this->approvedBy->email,
                    ];
                }),
                'approved_at' => $this->approved_at,
            ],
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            'notes' => $this->notes,
            'timestamps' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
        ];
    }
}
