<?php

namespace App\Domain\Units\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Units extends Model
{
    use UsesUuid, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'type', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];
}
