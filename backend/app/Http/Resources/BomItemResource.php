<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BomItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'bom_id' => $this->bom_id,
            'material_id' => $this->material_id,
            'sub_product_id' => $this->sub_product_id,
            'quantity' => $this->quantity,
            'unit_id' => $this->unit_id,
            'wastage_percent' => $this->wastage_percent,
            'line_no' => $this->line_no,
            'metadata' => $this->metadata,
            'material' => new MaterialResource($this->whenLoaded('material')),
            'sub_product' => new ProductResource($this->whenLoaded('subProduct')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
