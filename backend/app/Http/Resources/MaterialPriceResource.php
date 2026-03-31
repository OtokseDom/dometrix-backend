<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MaterialPriceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'material_id' => $this->material_id,
            'price' => $this->price,
            'effective_date' => $this->effective_date,
            'created_by' => $this->created_by,
            'material' => new MaterialResource($this->whenLoaded('material')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
