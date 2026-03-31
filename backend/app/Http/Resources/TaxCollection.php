<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TaxCollection extends ResourceCollection
{
    public $collects = TaxResource::class;

    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
