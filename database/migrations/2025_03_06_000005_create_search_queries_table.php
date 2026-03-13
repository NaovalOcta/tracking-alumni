<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('alumni_nim', 15);
            $table->string('query_text', 500);
            $table->enum('search_tier', [
                'tier1_linkedin',
                'tier2_scholar_github',
                'tier3_news_web',
            ]);
            $table->integer('results_count')->default(0);
            $table->timestamp('searched_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('alumni_nim')->references('nim')->on('alumni')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
