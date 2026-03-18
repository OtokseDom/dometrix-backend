<?php

namespace App\Domain\Role\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use UsesUuid, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
    ];
}
