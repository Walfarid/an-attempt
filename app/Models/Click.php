<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;

class Click extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'path',
        'element',
        'label',
        'ip',
        'user_agent',
        'user_id',
        'clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder  $query
     */
    public function scopeForPath($query, string $path): void
    {
        $query->where('path', $path);
    }

    /**
     * @param  Builder  $query
     */
    public function scopeLastDays($query, int $days): void
    {
        $query->where('clicked_at', '>=', now()->subDays($days));
    }
}
