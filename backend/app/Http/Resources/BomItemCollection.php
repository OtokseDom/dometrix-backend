<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BomItemCollection extends ResourceCollection
{
    public $collects = BomItemResource::class;

    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
