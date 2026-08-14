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
            $table->string('provinsi')->nullable()->after('status');
            $table->string('kabupaten_kota')->nullable()->after('provinsi');
            $table->string('kecamatan')->nullable()->after('kabupaten_kota');
            $table->string('jenis_kelamin')->nullable()->after('kecamatan');
            $table->date('tanggal_lahir')->nullable()->after('jenis_kelamin');
            $table->string('id_unik')->nullable()->after('tanggal_lahir');
            $table->boolean('is_synced')->default(false)->after('id_unik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->dropColumn([
                'provinsi', 
                'kabupaten_kota', 
                'kecamatan', 
                'jenis_kelamin', 
                'tanggal_lahir', 
                'id_unik',
                'is_synced'
            ]);
        });
    }
};
