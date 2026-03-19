<?php

namespace App\Domain\UnitOfMeasure\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitOfMeasure extends Model
{
    use UsesUuid,SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];
}
