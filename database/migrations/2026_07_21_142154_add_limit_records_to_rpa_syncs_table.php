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
        Schema::table('rpa_syncs', function (Blueprint $table) {
            $table->integer('limit_records')->default(0)->after('success_records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rpa_syncs', function (Blueprint $table) {
            $table->dropColumn('limit_records');
        });
    }
};
