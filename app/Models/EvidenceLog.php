<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'alumni_nim',
        'tracking_result_id',
        'source_type',
        'source_url',
        'raw_snippet',
        'extracted_json',
        'confidence_score',
        'searched_at',
        'created_at',
    ];

    protected $casts = [
        'extracted_json' => 'array',
        'confidence_score' => 'decimal:2',
        'searched_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class, 'alumni_nim', 'nim');
    }

    public function trackingResult(): BelongsTo
    {
        return $this->belongsTo(TrackingResult::class, 'tracking_result_id');
    }
}
