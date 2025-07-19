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
     *
     * @OA\Schema(
     *     schema="OrderResource",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=12),
     *     @OA\Property(property="status", type="string", example="processing"),
     *     @OA\Property(property="total", type="number", format="float", example=1250.50),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-01T12:00:00Z"),
     *     @OA\Property(property="products", type="array", @OA\Items(type="object"))
     * )
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
