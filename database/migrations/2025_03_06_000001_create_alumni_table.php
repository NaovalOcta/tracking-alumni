<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            $table->string('nim', 15)->primary();
            $table->string('nama_lengkap');
            $table->json('nama_variasi')->nullable();
            $table->string('email')->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->string('prodi', 100);
            $table->string('fakultas', 100)->nullable();
            $table->year('tahun_masuk')->nullable();
            $table->year('tahun_lulus');
            $table->string('foto')->nullable();
            $table->enum('tracking_status', [
                'belum_dilacak',
                'sedang_dilacak',
                'auto_verified',
                'needs_audit',
                'not_found',
                'insufficient_data',
            ])->default('belum_dilacak');
            $table->timestamp('last_tracked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
