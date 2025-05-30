<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
            return [
            'name' => $this->name,
            'email' => $this->email,
            'image' => $this->image,
            'is_admin' => $this->is_admin,
            'email_verified_at' => $this->email_verified_at,
        ];
    }
}
