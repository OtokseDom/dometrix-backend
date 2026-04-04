<?php

namespace App\Domain\Currencies\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currencies extends Model
{
    use HasFactory, UsesUuid, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];
}
