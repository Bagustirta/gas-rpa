<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaksi_gas', function (Blueprint $table) {
            $table->string('status_lapor')->default('belum_lapor'); // belum_lapor, sukses, gagal
            $table->text('catatan_lapor')->nullable();
        });

        // Sync data lama berdasarkan kolom dilaporkan_pertamina
        DB::table('transaksi_gas')
            ->where('dilaporkan_pertamina', true)
            ->update([
                'status_lapor' => 'sukses',
                'catatan_lapor' => 'Dikonfirmasi sebelumnya'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_gas', function (Blueprint $table) {
            $table->dropColumn(['status_lapor', 'catatan_lapor']);
        });
    }
};
