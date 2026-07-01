<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stock_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->string('label', 100);
            $table->decimal('conversion_to_base', 15, 3);
            $table->boolean('is_primary_receipt_unit')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'label']);
            $table->index(['product_id', 'is_active']);
        });

        $now = now();

        DB::table('products')
            ->select('id', 'base_unit_id', 'base_unit')
            ->orderBy('id')
            ->eachById(function ($product) use ($now) {
                if (!$product->base_unit_id) {
                    return;
                }

                DB::table('product_stock_units')->insert([
                    'product_id' => $product->id,
                    'unit_id' => $product->base_unit_id,
                    'label' => $product->base_unit ?: 'pcs',
                    'conversion_to_base' => 1,
                    'is_primary_receipt_unit' => true,
                    'is_active' => true,
                    'display_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['stock_configuration_status' => 'configured']);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_units');
    }
};
