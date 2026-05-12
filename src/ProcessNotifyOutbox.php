<?php

namespace TuttoInCloud\NotifyClient;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use TuttoInCloud\NotifyClient\Exceptions\NotifyException;

class ProcessNotifyOutbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected static array $backoff = [30, 120, 600, 1800];

    public function __construct(
        public int $outboxId,
    ) {
        $this->queue = config('notify-client.outbox.queue', 'default');
    }

    public function handle(NotifyClient $client): void
    {
        $outbox = NotifyOutbox::find($this->outboxId);

        if (! $outbox || ! $outbox->isPending()) {
            return;
        }

        $maxAttempts = config('notify-client.outbox.max_attempts', 5);

        try {
            $result = $client->send($outbox->payload);
            $outbox->markSent($result['uuid'] ?? '');
        } catch (\Throwable $e) {
            if ($client->isTemporaryError($e) && $outbox->attempts < $maxAttempts - 1) {
                $delay = self::$backoff[$outbox->attempts] ?? end(self::$backoff);
                $outbox->scheduleRetry($delay);

                self::dispatch($this->outboxId)
                    ->delay(now()->addSeconds($delay));
            } else {
                $outbox->markFailed($e->getMessage());
                Log::error('Notify outbox failed permanently', [
                    'outbox_id' => $this->outboxId,
                    'error'     => $e->getMessage(),
                ]);
            }
        }
    }
}
