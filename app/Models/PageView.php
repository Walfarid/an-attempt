<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'path',
        'title',
        'ip',
        'user_agent',
        'referrer',
        'user_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder  $query
     */
    public function scopeLastDays($query, int $days): void
    {
        $query->where('viewed_at', '>=', now()->subDays($days));
    }
}
