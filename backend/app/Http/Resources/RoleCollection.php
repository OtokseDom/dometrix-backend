<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class RoleCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'total' => $this->collection->count(),
            'data' => RoleResource::collection($this->collection),
        ];
    }
}
