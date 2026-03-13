<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_results', function (Blueprint $table) {
            $table->id();
            $table->string('alumni_nim', 15);
            $table->string('jabatan')->nullable();
            $table->string('instansi')->nullable();
            $table->string('bidang_pekerjaan', 100)->nullable();
            $table->string('lokasi')->nullable();
            $table->string('linkedin_url', 500)->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->text('ai_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('alumni_nim')->references('nim')->on('alumni')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_results');
    }
};
