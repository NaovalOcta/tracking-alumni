<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fetch_cache', function (Blueprint $table) {
            $table->id();
            $table->string('url_hash', 64)->unique();
            $table->string('original_url', 1000);
            $table->json('content_blocks')->nullable();
            $table->timestamp('expires_at');
            
            $table->index('url_hash');
            $table->index('expires_at');
        });

        Schema::create('query_hashes', function (Blueprint $table) {
            $table->id();
            $table->string('query_hash', 64);
            $table->string('original_query', 500);
            $table->integer('result_count')->default(0);
            $table->timestamp('executed_at');
            
            $table->index(['query_hash', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('query_hashes');
        Schema::dropIfExists('fetch_cache');
    }
};
