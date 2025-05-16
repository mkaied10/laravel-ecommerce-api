<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
         return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product ? $this->product->getTranslation('name', app()->getLocale()) : null,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
        ];
    }
}