<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchQuery extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'alumni_nim',
        'query_text',
        'search_tier',
        'results_count',
        'searched_at',
        'created_at',
    ];

    protected $casts = [
        'searched_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class, 'alumni_nim', 'nim');
    }
}
