<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('step')->default('idle');         // idle, login, navigate, filter, cek_nik, selesai, error
            $table->string('step_label')->default('');       // Teks yang ditampilkan
            $table->string('pangkalan_name')->default('');
            $table->unsignedSmallInteger('pangkalan_index')->default(0);
            $table->unsignedSmallInteger('pangkalan_total')->default(0);
            $table->unsignedInteger('nik_current')->default(0);
            $table->unsignedInteger('nik_total')->default(0);
            $table->boolean('is_running')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_progress');
    }
};
