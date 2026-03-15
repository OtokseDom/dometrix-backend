<?php

namespace App\Domain\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organization extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'organizations';
    public $incrementing = false; // because we use UUID
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'code',
        'timezone',
        'currency',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(\App\Domain\User\Models\User::class);
    }

    // public function products()
    // {
    //     return $this->hasMany(\App\Domain\Product\Models\Product::class);
    // }

    // public function materials()
    // {
    //     return $this->hasMany(\App\Domain\Material\Models\Material::class);
    // }
}
