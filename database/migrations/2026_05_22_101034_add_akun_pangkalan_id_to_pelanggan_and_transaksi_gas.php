<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->foreignId('akun_pangkalan_id')->nullable()->constrained('akun_pangkalans')->onDelete('set null');
        });

        Schema::table('transaksi_gas', function (Blueprint $table) {
            $table->foreignId('akun_pangkalan_id')->nullable()->constrained('akun_pangkalans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->dropForeign(['akun_pangkalan_id']);
            $table->dropColumn('akun_pangkalan_id');
        });

        Schema::table('transaksi_gas', function (Blueprint $table) {
            $table->dropForeign(['akun_pangkalan_id']);
            $table->dropColumn('akun_pangkalan_id');
        });
    }
};
