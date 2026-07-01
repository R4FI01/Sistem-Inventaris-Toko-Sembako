<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'stock_base',
        'img_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_base' => 'decimal:3',
    ];

    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class)
            ->orderByDesc('is_default')
            ->orderBy('conversion_factor');
    }
}
