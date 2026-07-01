<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('base_unit_id')
                ->nullable()
                ->after('base_unit')
                ->constrained('units')
                ->nullOnDelete();

            $table->string('stock_configuration_status', 30)
                ->default('needs_configuration')
                ->after('stock_base');
        });

        $now = now();

        DB::table('products')->orderBy('id')->eachById(function ($product) use ($now) {
            $legacyUnit = trim((string) ($product->base_unit ?: 'pcs'));
            $lookup = mb_strtolower($legacyUnit);

            $unit = DB::table('units')
                ->whereRaw('LOWER(symbol) = ?', [$lookup])
                ->orWhereRaw('LOWER(name) = ?', [$lookup])
                ->first();

            if (!$unit) {
                $symbol = Str::limit(Str::slug($legacyUnit, '_'), 20, '');

                if ($symbol === '') {
                    $symbol = 'unit_' . $product->id;
                }

                $baseSymbol = $symbol;
                $counter = 1;

                while (DB::table('units')->where('symbol', $symbol)->exists()) {
                    $suffix = '_' . $counter;
                    $symbol = Str::limit($baseSymbol, 20 - strlen($suffix), '') . $suffix;
                    $counter++;
                }

                $unitId = DB::table('units')->insertGetId([
                    'name' => Str::limit($legacyUnit ?: 'Satuan Produk', 50, ''),
                    'symbol' => $symbol,
                    'type' => 'count',
                    'allows_decimal' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $unitId = $unit->id;
            }

            DB::table('products')
                ->where('id', $product->id)
                ->update(['base_unit_id' => $unitId]);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['base_unit_id']);
            $table->dropColumn(['base_unit_id', 'stock_configuration_status']);
        });
    }
};
