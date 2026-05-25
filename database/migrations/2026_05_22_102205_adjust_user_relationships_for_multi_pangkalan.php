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
            $table->dropForeign(['akun_pangkalan_id']);
            $table->dropColumn('akun_pangkalan_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
        });

        Schema::table('akun_pangkalans', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->foreignId('akun_pangkalan_id')->nullable()->constrained('akun_pangkalans')->onDelete('set null');
        });

        Schema::table('akun_pangkalans', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
