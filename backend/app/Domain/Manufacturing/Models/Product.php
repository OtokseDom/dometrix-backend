<?php

namespace App\Domain\Manufacturing\Models;

use App\Traits\UsesUuid;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, UsesUuid, SoftDeletes, BelongsToOrganization;

    public $incrementing = false;
    protected $table = 'products';
    protected $keyType = 'string';

    protected $fillable = ['organization_id', 'code', 'name', 'description', 'unit_id', 'metadata'];
    protected $casts = ['metadata' => 'array'];

    public function boms(): HasMany
    {
        return $this->hasMany(Bom::class, 'product_id');
    }

    public function activeBom(): ?Bom
    {
        return $this->boms()->where('is_active', true)->first();
    }
}
