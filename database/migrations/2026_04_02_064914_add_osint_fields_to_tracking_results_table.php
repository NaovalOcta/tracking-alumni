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
            $table->string('ig_url', 500)->nullable()->after('linkedin_url');
            $table->string('fb_url', 500)->nullable()->after('ig_url');
            $table->string('tiktok_url', 500)->nullable()->after('fb_url');
            $table->string('email')->nullable()->after('tiktok_url');
            $table->string('no_hp')->nullable()->after('email');
            $table->string('kategori_pekerjaan')->comment('PNS/Swasta/Wirausaha')->nullable()->after('bidang_pekerjaan');
            $table->string('sosmed_instansi_linkedin', 500)->nullable()->after('kategori_pekerjaan');
            $table->string('sosmed_instansi_ig', 500)->nullable()->after('sosmed_instansi_linkedin');
            $table->string('sosmed_instansi_fb', 500)->nullable()->after('sosmed_instansi_ig');
            $table->string('sosmed_instansi_tiktok', 500)->nullable()->after('sosmed_instansi_fb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracking_results', function (Blueprint $table) {
            $table->dropColumn([
                'ig_url',
                'fb_url',
                'tiktok_url',
                'email',
                'no_hp',
                'kategori_pekerjaan',
                'sosmed_instansi_linkedin',
                'sosmed_instansi_ig',
                'sosmed_instansi_fb',
                'sosmed_instansi_tiktok'
            ]);
        });
    }
};
