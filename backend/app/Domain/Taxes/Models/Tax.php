<?php

namespace App\Domain\Taxes\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasFactory, UsesUuid, SoftDeletes;

    public $incrementing = false;
    protected $table = 'taxes';
    protected $keyType = 'string';

    protected $fillable = ['organization_id', 'code', 'name', 'rate', 'is_active', 'metadata'];
    protected $casts = ['rate' => 'decimal:2', 'is_active' => 'boolean', 'metadata' => 'array'];
}
