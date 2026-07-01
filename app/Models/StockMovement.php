<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_stock_unit_id',
        'product_unit_id',
        'user_id',
        'movement_type',
        'unit_label',
        'quantity_input',
        'conversion_to_base',
        'quantity_base',
        'stock_before',
        'stock_after',
        'reference_number',
        'note',
    ];

    protected $casts = [
        'quantity_input' => 'decimal:3',
        'conversion_to_base' => 'decimal:3',
        'quantity_base' => 'decimal:3',
        'stock_before' => 'decimal:3',
        'stock_after' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productStockUnit(): BelongsTo
    {
        return $this->belongsTo(ProductStockUnit::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
