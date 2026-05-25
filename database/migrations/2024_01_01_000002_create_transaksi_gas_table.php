<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_gas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->constrained('pelanggan')->onDelete('cascade');
            $table->integer('jumlah_tabung');
            $table->timestamp('tanggal_transaksi')->useCurrent();
            $table->boolean('dilaporkan_pertamina')->default(false)->comment('Sudah dikonfirmasi ke MAP Pertamina');
            $table->timestamp('waktu_dilaporkan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_gas');
    }
};
