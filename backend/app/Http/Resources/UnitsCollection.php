<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UnitsCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'total' => $this->collection->count(),
            'data' => UnitsResource::collection($this->collection),
        ];
    }
}
