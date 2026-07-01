<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('symbol', 20)->unique();
            $table->string('type', 20);
            $table->boolean('allows_decimal')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('units')->insert([
            ['name' => 'Kilogram', 'symbol' => 'kg', 'type' => 'weight', 'allows_decimal' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gram', 'symbol' => 'g', 'type' => 'weight', 'allows_decimal' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ons', 'symbol' => 'ons', 'type' => 'weight', 'allows_decimal' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Liter', 'symbol' => 'l', 'type' => 'volume', 'allows_decimal' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mililiter', 'symbol' => 'ml', 'type' => 'volume', 'allows_decimal' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pcs', 'symbol' => 'pcs', 'type' => 'count', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Butir', 'symbol' => 'butir', 'type' => 'count', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bungkus', 'symbol' => 'bungkus', 'type' => 'count', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sachet', 'symbol' => 'sachet', 'type' => 'count', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Botol', 'symbol' => 'botol', 'type' => 'count', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kaleng', 'symbol' => 'kaleng', 'type' => 'count', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pak', 'symbol' => 'pak', 'type' => 'package', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dus', 'symbol' => 'dus', 'type' => 'package', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Karton', 'symbol' => 'karton', 'type' => 'package', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Karung', 'symbol' => 'karung', 'type' => 'package', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rak', 'symbol' => 'rak', 'type' => 'package', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bal', 'symbol' => 'bal', 'type' => 'package', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Slop', 'symbol' => 'slop', 'type' => 'package', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Galon', 'symbol' => 'galon', 'type' => 'count', 'allows_decimal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
