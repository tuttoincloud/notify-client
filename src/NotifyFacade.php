<?php

namespace TuttoInCloud\NotifyClient;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array send(array $payload)
 * @method static array status(string $uuid)
 * @method static bool isTemporaryError(\Throwable $e)
 *
 * @see \TuttoInCloud\NotifyClient\NotifyClient
 */
class NotifyFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NotifyClient::class;
    }
}
