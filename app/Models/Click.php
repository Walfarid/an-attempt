<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * Analytics click/CTA event row.
 *
 * Prunable: the dashboard reads only the last 14 days, so a 90-day window
 * gives generous headroom before rows are garbage-collected by the daily
 * model:prune schedule.
 */
class Click extends Model
{
    use Prunable;

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
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('clicked_at', '<', now()->subDays(90));
    }
}
