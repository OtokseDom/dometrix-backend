<?php

namespace App\Domain\Manufacturing\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BomItem extends Model
{
    use UsesUuid, SoftDeletes;

    public $incrementing = false;
    protected $table = 'bom_items';
    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'bom_id',
        'material_id',
        'sub_product_id',
        'quantity',
        'unit_id',
        'wastage_percent',
        'line_no',
        'metadata'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'wastage_percent' => 'decimal:4',
        'metadata' => 'array'
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function subProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'sub_product_id');
    }
}
