<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE search_queries MODIFY COLUMN search_tier ENUM(
            'tier1_linkedin',
            'tier2_scholar_github',
            'tier3_news_web',
            'tier2_ig_tiktok',
            'tier3_facebook',
            'tier4_web'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE search_queries MODIFY COLUMN search_tier ENUM(
            'tier1_linkedin',
            'tier2_scholar_github',
            'tier3_news_web'
        ) NOT NULL");
    }
};
