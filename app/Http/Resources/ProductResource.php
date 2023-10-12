<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        if (is_null($this->resource)) {
            return [];
        }

        $price = $this->getPrice($request->user());

        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'label'           => $this->label,
            'thumbnail'       => $this->thumbnail,
            'description'     => $this->description,
            'stock'           => $this->stock,
            'value'           => $this->value,
            'formatted_value' => $this->formatted_value,
            'currency'        => $this->currency,
            'brand'           => ProductBrandResource::make($this->whenLoaded('brand')),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'price'           => $price->getValue(),
            'formatted_price' => $price->format(),
            'instruction'    => $this->instruction,
        ];
    }
}
