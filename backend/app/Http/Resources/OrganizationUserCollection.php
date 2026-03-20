<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class OrganizationUserCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'total' => $this->collection->count(),
            'data' => OrganizationUserResource::collection($this->collection),
        ];
    }
}
