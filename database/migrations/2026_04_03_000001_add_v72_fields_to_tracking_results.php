<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracking_results', function (Blueprint $table) {
            $table->float('identity_confidence')->nullable()->after('confidence_score');
            $table->enum('coverage_tier', ['high', 'medium', 'low'])->nullable()->after('identity_confidence');
            $table->json('coverage_details')->nullable()->after('coverage_tier');
            $table->enum('manual_confidence', ['high', 'medium', 'low'])->nullable()->after('coverage_details');
            $table->json('decision_trace')->nullable()->after('ai_notes');
            $table->json('conflict_trace')->nullable()->after('decision_trace');
        });
    }

    public function down(): void
    {
        Schema::table('tracking_results', function (Blueprint $table) {
            $table->dropColumn([
                'identity_confidence',
                'coverage_tier',
                'coverage_details',
                'manual_confidence',
                'decision_trace',
                'conflict_trace',
            ]);
        });
    }
};
