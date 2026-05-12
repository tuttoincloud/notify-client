<?php

namespace TuttoInCloud\NotifyClient;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class NotifyTransport extends AbstractTransport
{
    public function __construct(
        protected NotifyClient $client,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $payload = $this->buildPayload($email);

        if (config('notify-client.outbox.enabled', true)) {
            $outbox = NotifyOutbox::create([
                'status'  => NotifyOutbox::STATUS_PENDING,
                'payload' => $payload,
            ]);

            ProcessNotifyOutbox::dispatch($outbox->id);
        } else {
            $this->client->send($payload);
        }
    }

    protected function buildPayload(Email $email): array
    {
        $to = $email->getTo()[0] ?? null;
        $from = $email->getFrom()[0] ?? null;
        $replyTo = $email->getReplyTo()[0] ?? null;

        $payload = [
            'type'    => $this->getHeader($email, 'X-Notify-Type') ?? 'transactional',
            'to'      => array_filter([
                'email' => $to?->getAddress(),
                'name'  => $to?->getName(),
            ]),
            'subject' => $email->getSubject() ?? '',
        ];

        if ($from) {
            $payload['from'] = array_filter([
                'email' => $from->getAddress(),
                'name'  => $from->getName(),
            ]);
        }

        if ($replyTo) {
            $payload['reply_to'] = $replyTo->getAddress();
        }

        if ($html = $email->getHtmlBody()) {
            $payload['html'] = $html;
        }

        if ($text = $email->getTextBody()) {
            $payload['text'] = $text;
        }

        if ($metadata = $this->getHeader($email, 'X-Notify-Metadata')) {
            $decoded = json_decode($metadata, true);
            if (is_array($decoded)) {
                $payload['metadata'] = $decoded;
            }
        }

        if ($idempotencyKey = $this->getHeader($email, 'X-Notify-Idempotency-Key')) {
            $payload['idempotency_key'] = $idempotencyKey;
        }

        return $payload;
    }

    protected function getHeader(Email $email, string $name): ?string
    {
        $header = $email->getHeaders()->get($name);

        return $header?->getBodyAsString();
    }

    public function __toString(): string
    {
        return 'notify';
    }
}
