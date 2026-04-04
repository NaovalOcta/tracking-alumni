<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackingResult extends Model
{
    protected $fillable = [
        'alumni_nim',
        'jabatan',
        'instansi',
        'bidang_pekerjaan',
        'kategori_pekerjaan',
        'tipe_posisi',
        'posisi_sejak',
        'lokasi',
        'linkedin_url',
        'ig_url',
        'fb_url',
        'tiktok_url',
        'email',
        'no_hp',
        'sosmed_instansi_linkedin',
        'sosmed_instansi_ig',
        'sosmed_instansi_fb',
        'sosmed_instansi_tiktok',
        'is_umm_verified',
        'umm_evidence',
        'confidence_score',
        'ai_notes',
        'verified_by',
        'verified_at',
        'identity_confidence',
        'coverage_tier',
        'coverage_details',
        'manual_confidence',
        'decision_trace',
        'conflict_trace',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'identity_confidence' => 'float',
        'is_umm_verified' => 'boolean',
        'verified_at' => 'datetime',
        'coverage_details' => 'array',
        'decision_trace' => 'array',
        'conflict_trace' => 'array',
    ];

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class, 'alumni_nim', 'nim');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function evidenceLogs(): HasMany
    {
        return $this->hasMany(EvidenceLog::class, 'tracking_result_id');
    }
}
