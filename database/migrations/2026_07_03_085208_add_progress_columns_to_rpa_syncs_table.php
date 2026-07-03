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
            $table->integer('success_records')->default(0)->after('total_records');
            $table->integer('failed_records')->default(0)->after('success_records');
            $table->string('current_pangkalan')->nullable()->after('message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rpa_syncs', function (Blueprint $table) {
            $table->dropColumn(['success_records', 'failed_records', 'current_pangkalan']);
        });
    }
};
