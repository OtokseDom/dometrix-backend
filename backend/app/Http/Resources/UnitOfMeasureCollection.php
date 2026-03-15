<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UnitOfMeasureCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'total' => $this->collection->count(),
            'data' => UnitOfMeasureResource::collection($this->collection),
        ];
    }
}
