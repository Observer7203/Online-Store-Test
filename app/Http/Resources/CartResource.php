<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'guest_token' => $this->guest_token,
            'total_minor' => $this->items->sum(fn($i) => $i->qty * $i->price_snapshot),
            'items'       => $this->items->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'product_id'  => $item->product_id,
                    'qty'         => $item->qty,
                    'price_minor' => $item->price_snapshot,
                    'product'     => [
                        'id'   => $item->product->id,
                        'name' => $item->product->name,
                        'slug' => $item->product->slug,
                    ],
                ];
            }),
        ];
    }
}
