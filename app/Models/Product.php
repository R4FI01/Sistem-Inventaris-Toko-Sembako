<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'price',
        'unit',
        'base_unit',
        'base_unit_id',
        'stock_base',
        'stock_configuration_status',
        'img_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_base' => 'decimal:3',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi sengaja tidak dinamai baseUnit agar tidak berbenturan dengan
     * atribut kolom products.base_unit saat model diubah menjadi JSON.
     */
    public function inventoryUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class)
            ->orderByDesc('is_default')
            ->orderBy('conversion_factor');
    }

    public function stockUnits(): HasMany
    {
        return $this->hasMany(ProductStockUnit::class)
            ->orderByDesc('is_primary_receipt_unit')
            ->orderBy('display_order')
            ->orderBy('id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
