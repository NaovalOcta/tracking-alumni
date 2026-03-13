<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alumni extends Model
{
    protected $table = 'alumni';

    protected $primaryKey = 'nim';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nim',
        'nama_lengkap',
        'nama_variasi',
        'email',
        'no_telepon',
        'prodi',
        'fakultas',
        'tahun_masuk',
        'tahun_lulus',
        'foto',
        'tracking_status',
        'last_tracked_at',
    ];

    protected $casts = [
        'nama_variasi' => 'array',
        'last_tracked_at' => 'datetime',
    ];

    public function trackingResults(): HasMany
    {
        return $this->hasMany(TrackingResult::class, 'alumni_nim', 'nim');
    }

    public function evidenceLogs(): HasMany
    {
        return $this->hasMany(EvidenceLog::class, 'alumni_nim', 'nim');
    }

    public function trackingHistories(): HasMany
    {
        return $this->hasMany(TrackingHistory::class, 'alumni_nim', 'nim');
    }

    public function searchQueries(): HasMany
    {
        return $this->hasMany(SearchQuery::class, 'alumni_nim', 'nim');
    }

    /**
     * Get the latest tracking result.
     */
    public function latestTrackingResult()
    {
        return $this->hasOne(TrackingResult::class, 'alumni_nim', 'nim')->latestOfMany();
    }

    /**
     * Scope: filter by tracking status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('tracking_status', $status);
    }

    /**
     * Scope: search by name or NIM.
     */
    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                    ->orWhere('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('prodi', 'like', "%{$search}%");
            });
        }
        return $query;
    }
}
