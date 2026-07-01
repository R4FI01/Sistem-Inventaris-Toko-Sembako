<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'symbol',
        'type',
        'allows_decimal',
        'is_active',
    ];

    protected $casts = [
        'allows_decimal' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function baseProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'base_unit_id');
    }

    public function productStockUnits(): HasMany
    {
        return $this->hasMany(ProductStockUnit::class);
    }
}
