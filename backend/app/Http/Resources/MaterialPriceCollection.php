<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class MaterialPriceCollection extends ResourceCollection
{
    public $collects = MaterialPriceResource::class;

    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
