<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * Public page-view analytics row.
 *
 * Prunable: the dashboard reads only the last 14 days, so a 90-day window
 * gives generous headroom before rows are garbage-collected by the daily
 * model:prune schedule.
 */
class PageView extends Model
{
    use Prunable;

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
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('viewed_at', '<', now()->subDays(90));
    }
}
