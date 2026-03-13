<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_histories', function (Blueprint $table) {
            $table->id();
            $table->string('alumni_nim', 15);
            $table->json('snapshot_data');
            $table->string('changed_reason')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('alumni_nim')->references('nim')->on('alumni')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_histories');
    }
};
