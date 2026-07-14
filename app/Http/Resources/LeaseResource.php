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
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'rent_amount' => (float) $this->rent_amount,
            'deposit_amount' => (float) $this->deposit_amount,
            'payment_frequency' => $this->payment_frequency,
            'payment_day' => $this->payment_day,
            'status' => $this->status,
            'notes' => $this->notes,
            'special_terms' => $this->special_terms,
            'is_active' => $this->is_active,
            'is_expired' => $this->is_expired,
            'days_remaining' => $this->days_remaining,
            'total_paid' => $this->total_paid,
            'outstanding_balance' => $this->outstanding_balance,
            
            // Return Documents with full URLs
            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'title' => $document->title,
                        'file_name' => $document->file_name,
                        'file_url' => $document->file_url, // Document model append returns full URL via asset()
                        'file_type' => $document->file_type,
                        'document_type' => $document->document_type,
                    ];
                });
            }),

            'unit' => $this->whenLoaded('unit', function () {
                return [
                    'id' => $this->unit->id,
                    'unit_number' => $this->unit->unit_number,
                    'property_id' => $this->unit->property_id,
                    'property_name' => $this->unit->property?->name,
                ];
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
