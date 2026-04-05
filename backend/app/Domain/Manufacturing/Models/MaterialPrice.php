<?php

namespace App\Domain\Manufacturing\Models;

use App\Traits\UsesUuid;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialPrice extends Model
{
    use HasFactory, UsesUuid, SoftDeletes, BelongsToOrganization;

    public $incrementing = false;
    protected $table = 'material_prices';
    protected $keyType = 'string';

    protected $fillable = ['organization_id', 'material_id', 'price', 'effective_date', 'created_by'];
    protected $casts = ['price' => 'decimal:4', 'effective_date' => 'date'];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
