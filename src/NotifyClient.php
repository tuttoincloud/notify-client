<?php

namespace TuttoInCloud\NotifyClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use TuttoInCloud\NotifyClient\Exceptions\NotifyException;
use TuttoInCloud\NotifyClient\Exceptions\NotifyValidationException;

class NotifyClient
{
    protected Client $http;

    public function __construct(
        protected string $baseUrl,
        protected string $token,
        int $timeout = 10,
    ) {
        $this->http = new Client([
            'base_uri' => rtrim($baseUrl, '/'),
            'timeout'  => $timeout,
            'headers'  => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * Invia un messaggio tramite il gateway.
     *
     * @return array{uuid: string, status: string}
     */
    public function send(array $payload): array
    {
        return $this->request('POST', '/api/v1/messages/send', $payload);
    }

    /**
     * Recupera lo stato di un messaggio.
     */
    public function status(string $uuid): array
    {
        return $this->request('GET', '/api/v1/messages/' . urlencode($uuid));
    }

    protected function request(string $method, string $uri, ?array $body = null): array
    {
        try {
            $options = [];
            if ($body !== null) {
                $options['json'] = $body;
            }

            $response = $this->http->request($method, $uri, $options);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (ClientException $e) {
            $status = $e->getResponse()->getStatusCode();
            $body = json_decode($e->getResponse()->getBody()->getContents(), true) ?? [];

            if ($status >= 400 && $status < 500) {
                throw new NotifyValidationException(
                    $body['message'] ?? 'Validation error',
                    $body['errors'] ?? [],
                    $status,
                );
            }

            throw new NotifyException(
                $body['message'] ?? 'API error',
                $status,
                $e,
            );
        } catch (GuzzleException $e) {
            throw new NotifyException(
                'Notify gateway unreachable: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    public function isTemporaryError(\Throwable $e): bool
    {
        if ($e instanceof NotifyValidationException) {
            return false;
        }

        if ($e instanceof NotifyException && $e->getCode() >= 400 && $e->getCode() < 500) {
            return false;
        }

        return $e instanceof NotifyException;
    }
}
