<?php

namespace TuttoInCloud\NotifyClient;

use Illuminate\Database\Eloquent\Model;

class NotifyOutbox extends Model
{
    protected $table = 'notify_outbox';

    protected $fillable = [
        'status',
        'payload',
        'uuid',
        'error',
        'attempts',
        'next_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'       => 'array',
            'attempts'      => 'integer',
            'next_retry_at' => 'datetime',
        ];
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markSent(string $uuid): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'uuid'   => $uuid,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error'  => $error,
        ]);
    }

    public function scheduleRetry(int $delaySeconds): void
    {
        $this->update([
            'attempts'      => $this->attempts + 1,
            'next_retry_at' => now()->addSeconds($delaySeconds),
        ]);
    }
}
