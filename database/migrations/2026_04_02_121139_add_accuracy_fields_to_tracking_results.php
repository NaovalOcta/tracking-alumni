<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tracking_results', function (Blueprint $table) {
            $table->string('tipe_posisi')->nullable()->after('kategori_pekerjaan');
            $table->string('posisi_sejak')->nullable()->after('tipe_posisi');
            $table->boolean('is_umm_verified')->default(false)->after('posisi_sejak');
            $table->text('umm_evidence')->nullable()->after('is_umm_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracking_results', function (Blueprint $table) {
            $table->dropColumn(['tipe_posisi', 'posisi_sejak', 'is_umm_verified', 'umm_evidence']);
        });
    }
};
