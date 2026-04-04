<?php

namespace App\Domain\Manufacturing\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, UsesUuid, SoftDeletes;

    public $incrementing = false;
    protected $table = 'materials';
    protected $keyType = 'string';

    protected $fillable = ['organization_id', 'code', 'name', 'category_id', 'unit_id', 'metadata'];
    protected $casts = ['metadata' => 'array'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Organization\Models\Organization::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(MaterialPrice::class, 'material_id');
    }

    public function currentPrice(): ?MaterialPrice
    {
        return $this->prices()
            ->where('effective_date', '<=', now()->toDateString())
            ->orderByDesc('effective_date')
            ->first();
    }

    public function priceAtDate(string $date): ?MaterialPrice
    {
        return $this->prices()
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->first();
    }
}
