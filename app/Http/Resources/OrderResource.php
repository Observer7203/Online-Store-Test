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
            'id'          => $this->id,
            'user_id'       => $this->user_id,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'status'      => $this->status,
            'total_minor' => $this->total_minor,
            'items'       => $this->items->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'product_id'    => $item->product_id,
                    'name_snapshot' => $item->name_snapshot,
                    'price_snapshot'   => $item->price_snapshot,
                    'qty'           => $item->qty,
                ];
            }),
        ];
    }
}
