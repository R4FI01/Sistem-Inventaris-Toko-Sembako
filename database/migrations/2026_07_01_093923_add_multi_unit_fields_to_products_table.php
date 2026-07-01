<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('base_unit', 50)
                ->default('pcs')
                ->after('unit');

            $table->decimal('stock_base', 15, 3)
                ->default(0)
                ->after('base_unit');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['base_unit', 'stock_base']);
        });
    }
};