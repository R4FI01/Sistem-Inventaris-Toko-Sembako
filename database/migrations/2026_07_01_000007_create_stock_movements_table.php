<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_stock_unit_id')
                ->nullable()
                ->constrained('product_stock_units')
                ->nullOnDelete();
            $table->foreignId('product_unit_id')
                ->nullable()
                ->constrained('product_units')
                ->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('movement_type', 30);
            $table->string('unit_label', 100);
            $table->decimal('quantity_input', 15, 3);
            $table->decimal('conversion_to_base', 15, 3);
            $table->decimal('quantity_base', 15, 3);
            $table->decimal('stock_before', 15, 3);
            $table->decimal('stock_after', 15, 3);
            $table->string('reference_number', 100)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['user_id', 'movement_type']);
        });

        $now = now();

        DB::table('products')
            ->select('id', 'user_id', 'stock_base', 'base_unit')
            ->orderBy('id')
            ->eachById(function ($product) use ($now) {
                $stockBase = round((float) $product->stock_base, 3);

                if ($stockBase <= 0) {
                    return;
                }

                $baseStockUnit = DB::table('product_stock_units')
                    ->where('product_id', $product->id)
                    ->orderBy('display_order')
                    ->first();

                if (!$baseStockUnit) {
                    return;
                }

                DB::table('stock_movements')->insert([
                    'product_id' => $product->id,
                    'product_stock_unit_id' => $baseStockUnit->id,
                    'product_unit_id' => null,
                    'user_id' => $product->user_id,
                    'movement_type' => 'migration_opening',
                    'unit_label' => $product->base_unit ?: $baseStockUnit->label,
                    'quantity_input' => $stockBase,
                    'conversion_to_base' => 1,
                    'quantity_base' => $stockBase,
                    'stock_before' => 0,
                    'stock_after' => $stockBase,
                    'reference_number' => null,
                    'note' => 'Saldo awal yang dibuat saat migrasi inventori multi satuan.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
