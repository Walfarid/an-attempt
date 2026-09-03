<?php

namespace App\Jobs;

use App\Models\PageView;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RecordPageView implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{path: string, ip: string|null, user_agent: string|null, referrer: string|null, user_id: int|null, viewed_at?: CarbonInterface}  $data
     */
    public function __construct(public array $data)
    {
        $this->onQueue('analytics');
    }

    /**
     * Record the page view from the queued payload.
     */
    public function handle(): void
    {
        try {
            PageView::create([
                'path' => $this->data['path'],
                'title' => null,
                'ip' => $this->data['ip'],
                'user_agent' => $this->data['user_agent'],
                'referrer' => $this->data['referrer'],
                'user_id' => $this->data['user_id'],
                'viewed_at' => $this->data['viewed_at'] ?? now(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('Page view tracking failed', ['error' => $e->getMessage()]);
        }
    }
}
