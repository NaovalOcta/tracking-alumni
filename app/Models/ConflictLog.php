<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConflictLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'alumni_nim',
        'field_name',
        'rejected_values',
        'resolution_reason',
        'created_at',
    ];

    protected $casts = [
        'rejected_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class, 'alumni_nim', 'nim');
    }
}
