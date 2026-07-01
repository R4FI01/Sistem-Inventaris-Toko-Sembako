<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->foreignId('product_unit_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_units')
                ->nullOnDelete();

            $table->string('unit_name', 50)
                ->nullable()
                ->after('qty');

            $table->decimal('conversion_factor', 12, 3)
                ->default(1)
                ->after('unit_name');

            $table->decimal('unit_price', 15, 2)
                ->default(0)
                ->after('conversion_factor');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->dropForeign(['product_unit_id']);
            $table->dropColumn([
                'product_unit_id',
                'unit_name',
                'conversion_factor',
                'unit_price'
            ]);
        });
    }
};