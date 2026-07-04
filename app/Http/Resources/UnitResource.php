<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
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
            'unit_number' => $this->unit_number,
            'main_image_url' => $this->relationLoaded('primaryImage')
                ? ($this->primaryImage ? $this->primaryImage->url : null)
                : $this->whenLoaded('images', function () {
                    $primaryImage = $this->images->where('is_primary', true)->first() ?? $this->images->first();
                    return $primaryImage ? $primaryImage->url : null;
                }),
            'rent_price' => $this->rent_price,
            'status' => $this->status,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'sqft' => $this->sqft,
            'is_featured' => $this->is_featured,
            'status_color' => config('units.status_colors')[$this->status] ?? 'bg-gray-500',
            'property' => new PropertyResource($this->whenLoaded('property')),
            'features' => $this->whenLoaded('features'),
            'images' => ImageResource::collection($this->whenLoaded('images')),
        ];
    }
}