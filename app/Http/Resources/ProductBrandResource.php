<?php

namespace App\Http\Resources;

use App\Models\ProductBrand;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductBrand
 */
class ProductBrandResource extends JsonResource
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

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            'products_count' => $this->whenNotNull($this->products_count),
        ];
    }
}
