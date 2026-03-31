<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaxResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'code' => $this->code,
            'name' => $this->name,
            'rate' => $this->rate,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
