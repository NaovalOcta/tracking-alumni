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
        'lokasi',
        'linkedin_url',
        'confidence_score',
        'ai_notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'verified_at' => 'datetime',
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
