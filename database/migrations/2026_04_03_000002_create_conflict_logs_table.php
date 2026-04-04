<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conflict_logs', function (Blueprint $table) {
            $table->id();
            $table->string('alumni_nim', 15);
            $table->string('field_name', 100);
            $table->json('rejected_values');
            $table->text('resolution_reason');
            $table->timestamp('created_at')->nullable();

            $table->foreign('alumni_nim')->references('nim')->on('alumni')->cascadeOnDelete();
            $table->index('alumni_nim');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conflict_logs');
    }
};
