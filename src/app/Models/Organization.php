<?php

namespace App\Models;

use App\Enums\ParseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'user_id',
        'yandex_url',
        'yandex_id',
        'name',
        'address',
        'rating',
        'ratings_count',
        'reviews_count',
        'parse_status',
        'parse_error',
        'last_parsed_at',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'ratings_count' => 'integer',
        'reviews_count' => 'integer',
        'parse_status' => ParseStatus::class,
        'last_parsed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function parseRuns(): HasMany
    {
        return $this->hasMany(ParseRun::class);
    }
}