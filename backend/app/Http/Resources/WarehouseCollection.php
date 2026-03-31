<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class WarehouseCollection extends ResourceCollection
{
    public $collects = WarehouseResource::class;

    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
