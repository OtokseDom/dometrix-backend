<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BomCollection extends ResourceCollection
{
    public $collects = BomResource::class;

    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
