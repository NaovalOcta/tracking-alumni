<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'alumni_nim',
        'snapshot_data',
        'changed_reason',
        'created_at',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class, 'alumni_nim', 'nim');
    }
}
