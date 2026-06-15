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
            // Index for multi-tenant user scoping
            $table->index('user_id');
            $table->index(['user_id', 'status']);
        });

        Schema::table('transaksi_gas', function (Blueprint $table) {
            // Indexes for foreign keys
            $table->index('pelanggan_id');
            $table->index('akun_pangkalan_id');
            
            // Indexes for monthly and yearly date filtering
            $table->index('tanggal_transaksi');
            
            // Index for status and reporting filters
            $table->index('status_lapor');
            $table->index('dilaporkan_pertamina');
        });

        Schema::table('akun_pangkalans', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('transaksi_gas', function (Blueprint $table) {
            $table->dropIndex(['pelanggan_id']);
            $table->dropIndex(['akun_pangkalan_id']);
            $table->dropIndex(['tanggal_transaksi']);
            $table->dropIndex(['status_lapor']);
            $table->dropIndex(['dilaporkan_pertamina']);
        });

        Schema::table('akun_pangkalans', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
