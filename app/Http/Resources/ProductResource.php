<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CategoryResource;

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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price_minor,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'attributes' => $this->attributes->map(function($attr) {
                return [
                    'code' => $attr->code,
                    'name' => $attr->name,
                    'value' => $attr->pivot->value_string
                        ?? $attr->pivot->value_int
                        ?? $attr->pivot->value_decimal
                        ?? $attr->pivot->value_boolean,
                ];
            }),
        ];
    }
}
