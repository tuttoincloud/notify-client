# tuttoincloud/notify-client

Client PHP per il Mail Gateway Notify di TuttoInCloud.

## Installazione

```bash
composer require tuttoincloud/notify-client
php artisan vendor:publish --tag=notify-client-config
php artisan migrate
```

## Configurazione

Aggiungi al `.env`:

```env
NOTIFY_BASE_URL=https://tuttoincloud.online
NOTIFY_TOKEN=tic_live_xxxxxxxxxxxxxxxx
```

Per usare il gateway come mailer Laravel predefinito:

```env
MAIL_MAILER=notify
```

## Utilizzo

### API diretta (Facade)

```php
use TuttoInCloud\NotifyClient\NotifyFacade as Notify;

// Invia un messaggio
$result = Notify::send([
    'type'    => 'transactional', // transactional, marketing, system
    'to'      => ['email' => 'user@example.com', 'name' => 'Mario Rossi'],
    'subject' => 'Conferma ordine #123',
    'html'    => '<h1>Grazie per il tuo ordine!</h1>',
    'text'    => 'Grazie per il tuo ordine!',
    // Opzionali:
    'from'            => ['email' => 'noreply@tuosaas.it', 'name' => 'Il Tuo SaaS'],
    'reply_to'        => 'support@tuosaas.it',
    'metadata'        => ['order_id' => '123'],
    'idempotency_key' => 'order-confirm-123',
]);
// $result = ['uuid' => 'abc-...', 'status' => 'queued']

// Controlla lo stato
$status = Notify::status('abc-123-uuid');
// $status = ['uuid' => '...', 'status' => 'delivered', ...]
```

### Via Dependency Injection

```php
use TuttoInCloud\NotifyClient\NotifyClient;

class OrderService
{
    public function __construct(private NotifyClient $notify) {}

    public function sendConfirmation(Order $order): void
    {
        $this->notify->send([
            'type'    => 'transactional',
            'to'      => ['email' => $order->email],
            'subject' => "Ordine #{$order->id} confermato",
            'html'    => view('emails.order-confirmation', compact('order'))->render(),
        ]);
    }
}
```

### Via Mail facade (trasparente)

Con `MAIL_MAILER=notify`, tutte le email inviate con il Mail facade passano automaticamente dal gateway:

```php
// Nessuna modifica ai Mailable esistenti
Mail::to('user@example.com')->send(new OrderConfirmation($order));
```

Il `NotifyTransport` salva il messaggio in una tabella outbox locale e lo invia in background. Se il gateway e temporaneamente irraggiungibile, il job ritenta automaticamente.

Per specificare il tipo di email o metadata:

```php
$mailable->withSymfonyMessage(function ($message) {
    $message->getHeaders()->addTextHeader('X-Notify-Type', 'marketing');
    $message->getHeaders()->addTextHeader('X-Notify-Metadata', json_encode(['campaign' => 'welcome']));
    $message->getHeaders()->addTextHeader('X-Notify-Idempotency-Key', 'welcome-user-42');
});
```

## Outbox

Il transport salva ogni email in `notify_outbox` prima di inviarla al gateway. Il job `ProcessNotifyOutbox` gestisce l'invio con retry automatico (backoff: 30s, 2m, 10m, 30m).

Per disabilitare l'outbox (invio diretto):

```env
NOTIFY_OUTBOX=false
```

## Gestione errori

```php
use TuttoInCloud\NotifyClient\Exceptions\NotifyException;
use TuttoInCloud\NotifyClient\Exceptions\NotifyValidationException;

try {
    Notify::send($payload);
} catch (NotifyValidationException $e) {
    // Errore 4xx (payload non valido)
    $e->errors(); // ['to.email' => ['required']]
} catch (NotifyException $e) {
    // Errore 5xx, timeout, gateway irraggiungibile
}
```
