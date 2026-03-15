<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class OrganizationCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'total' => $this->collection->count(),
            'data' => OrganizationResource::collection($this->collection),
        ];
    }
}
