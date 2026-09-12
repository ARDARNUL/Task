<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'organization_id',
        'external_id',
        'author_name',
        'rating',
        'text',
        'published_at',
        'raw',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'raw' => 'array',
        'published_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}