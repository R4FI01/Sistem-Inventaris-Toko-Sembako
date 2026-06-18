<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_number', 30)
                ->nullable()
                ->unique()
                ->after('id');
        });

        DB::table('invoices')
            ->whereNull('invoice_number')
            ->orderBy('id')
            ->chunkById(100, function ($invoices) {
                foreach ($invoices as $invoice) {
                    $date = $invoice->created_at
                        ? date('Ymd', strtotime($invoice->created_at))
                        : date('Ymd');

                    DB::table('invoices')
                        ->where('id', $invoice->id)
                        ->update([
                            'invoice_number' => 'INV-' . $date . '-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT)
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn('invoice_number');
        });
    }
};