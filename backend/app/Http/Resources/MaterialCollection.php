<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class MaterialCollection extends ResourceCollection
{
    public $collects = MaterialResource::class;

    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
