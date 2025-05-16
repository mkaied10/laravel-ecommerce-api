<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'user_id' => $this->user_id,
            'delivery_address' => $this->delivery_address,
            'order_status' => $this->getTranslation('order_status', app()->getLocale()),
            'payment_method' => $this->payment_method,
            'payment_status' => $this->getTranslation('payment_status', app()->getLocale()),
            'total_amount' => $this->total_amount,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}