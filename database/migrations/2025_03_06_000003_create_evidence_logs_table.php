<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_logs', function (Blueprint $table) {
            $table->id();
            $table->string('alumni_nim', 15);
            $table->foreignId('tracking_result_id')->nullable()->constrained('tracking_results')->nullOnDelete();
            $table->enum('source_type', [
                'linkedin',
                'google_scholar',
                'github',
                'news',
                'website',
                'other',
            ]);
            $table->string('source_url', 1000);
            $table->text('raw_snippet');
            $table->json('extracted_json')->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->timestamp('searched_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('alumni_nim')->references('nim')->on('alumni')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_logs');
    }
};
