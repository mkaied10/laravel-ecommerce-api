<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
        'name' => $this->getTranslation('name', app()->getLocale()),
        'description' => $this->getTranslation('description', app()->getLocale()),
        'images' => $this->images ? array_map(fn($image) => asset('storage/' . $image), json_decode($this->images, true)) : null,
        'price' => $this->price,
        'discounted_price' => $this->discounted_price,
        'quantity' => $this->quantity,
        'status' => $this->getTranslation('status', app()->getLocale()),
        'categories' => CategoryResource::collection($this->whenLoaded('categories')),
    ];
}
}
