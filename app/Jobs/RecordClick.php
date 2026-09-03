<?php

namespace App\Jobs;

use App\Models\Click;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RecordClick implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{path: string, element: string|null, label: string|null, ip: string|null, user_agent: string|null, user_id: int|null}  $data
     */
    public function __construct(public array $data)
    {
        $this->onQueue('analytics');
    }

    /**
     * Record the click from the queued payload.
     */
    public function handle(): void
    {
        try {
            Click::create([
                'path' => $this->data['path'],
                'element' => $this->data['element'],
                'label' => $this->data['label'],
                'ip' => $this->data['ip'],
                'user_agent' => $this->data['user_agent'],
                'user_id' => $this->data['user_id'],
                'clicked_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('Click tracking failed', ['error' => $e->getMessage()]);
        }
    }
}
