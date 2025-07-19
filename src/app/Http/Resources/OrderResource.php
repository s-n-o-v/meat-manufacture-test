<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'total_price' => $this?->total_price,
            'status' => $this->status?->name,
            'comment' => $this->comment,
            'created_at' => $this->created_at->toDateTimeString(),
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
