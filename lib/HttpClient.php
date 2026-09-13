<?php

declare(strict_types=1);

namespace Sangho;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Sangho\Exception;

class HttpClient
{
    private Client $guzzle;
    public readonly string $keyType; // 'public' | 'secret'
    public readonly bool $sandbox;

    public const SDK_VERSION = '1.1.0';

    private const RETRY_STATUS_CODES = [429, 500, 502, 503, 504];

    // Le backend distingue les clés de production ("prod") des clés de test
    // ("test") — il n'existe pas de préfixe "live" côté API Sangho.
    private const VALID_PREFIXES = ['pk_prod_', 'sk_prod_', 'pk_test_', 'sk_test_'];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://api.sangho.ga/v1',
        private readonly int $timeout = 30,
        private readonly int $maxRetries = 3,
    ) {
        self::validateApiKey($apiKey);
        self::validateBaseUrl($baseUrl);

        $this->keyType = str_starts_with($apiKey, 'pk_') ? 'public' : 'secret';
        $this->sandbox = str_starts_with($apiKey, 'pk_test_') || str_starts_with($apiKey, 'sk_test_');

        $this->guzzle = new Client([
            'base_uri' => rtrim($this->baseUrl, '/') . '/',
            'timeout' => $timeout,
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'sangho-php/' . self::SDK_VERSION,
                'X-Sangho-SDK' => 'php/' . self::SDK_VERSION,
                'X-Sangho-Environment' => $this->sandbox ? 'sandbox' : 'live',
            ],
            'http_errors' => false,
        ]);
    }

    private static function validateApiKey(string $apiKey): void
    {
        $matches = array_filter(self::VALID_PREFIXES, fn(string $p) => str_starts_with($apiKey, $p));
        if (empty($matches)) {
            throw new \InvalidArgumentException(
                'Invalid API key format. Expected prefix: ' . implode(', ', self::VALID_PREFIXES) . '.'
            );
        }
        if (strlen($apiKey) < 20) {
            throw new \InvalidArgumentException('API key is too short.');
        }
    }

    private static function validateBaseUrl(string $baseUrl): void
    {
        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;
        if ($scheme === null || $host === null) {
            throw new \InvalidArgumentException("Invalid base_url: \"{$baseUrl}\".");
        }
        $isLocal = in_array($host, ['localhost', '127.0.0.1'], true);
        if ($scheme !== 'https' && !$isLocal) {
            throw new \InvalidArgumentException(
                "Refusing to send API keys over a non-HTTPS base_url: \"{$baseUrl}\". " .
                'Use an https:// URL (localhost/127.0.0.1 are exempt for local development).'
            );
        }
    }

    public function assertSecretKey(string $method): void
    {
        if ($this->keyType === 'public') {
            throw new Exception\SanghoPublicKeyException(
                "Method `{$method}` requires a secret key (sk_…). You provided a public key (pk_…).",
                'public_key_not_allowed',
                403,
                [],
                type: 'PERMISSION_ERROR',
            );
        }
    }

    public function get(string $path, array $params = []): array
    {
        return $this->request('GET', $path, ['query' => array_filter($params, fn($v) => $v !== null)]);
    }

    public function post(string $path, array $body = []): ?array
    {
        $idempotencyKey = \Ramsey\Uuid\Uuid::uuid4()->toString();
        return $this->request('POST', $path, [
            'json' => $body,
            'headers' => ['Idempotency-Key' => $idempotencyKey],
        ]);
    }

    public function put(string $path, array $body = []): ?array
    {
        return $this->request('PUT', $path, ['json' => $body]);
    }

    public function patch(string $path, array $body): array
    {
        return $this->request('PATCH', $path, ['json' => $body]);
    }

    public function delete(string $path): void
    {
        $this->request('DELETE', $path);
    }

    public function options(string $path): array
    {
        return $this->request('OPTIONS', $path);
    }

    private function request(string $method, string $path, array $opts = []): mixed
    {
        $path = ltrim($path, '/');
        $attempt = 0;

        while (true) {
            try {
                $resp = $this->guzzle->request($method, $path, $opts);
            } catch (ConnectException $e) {
                // Jamais de réponse du serveur (DNS, connexion refusée, timeout...) — transitoire, on retry.
                if ($attempt < $this->maxRetries) {
                    usleep((int) ($this->backoff($attempt) * 1_000_000));
                    $attempt++;
                    continue;
                }
                $lowerMessage = strtolower($e->getMessage());
                if (str_contains($lowerMessage, 'timed out') || str_contains($lowerMessage, 'timeout')) {
                    throw new Exception\SanghoTimeoutException((float) $this->timeout);
                }
                throw new Exception\SanghoNetworkException($e->getMessage());
            } catch (RequestException $e) {
                if ($e->hasResponse()) {
                    $r = $e->getResponse();
                    $data = json_decode((string) $r->getBody(), true) ?? [];
                    $this->raiseForStatus($r->getStatusCode(), $data, $r);
                }
                if ($attempt < $this->maxRetries) {
                    usleep((int) ($this->backoff($attempt) * 1_000_000));
                    $attempt++;
                    continue;
                }
                throw new Exception\SanghoNetworkException($e->getMessage());
            }

            $statusCode = $resp->getStatusCode();

            if ($statusCode === 204) {
                return null;
            }

            $data = json_decode((string) $resp->getBody(), true) ?? [];

            if (in_array($statusCode, self::RETRY_STATUS_CODES, true) && $attempt < $this->maxRetries) {
                $delay = $statusCode === 429 ? $this->parseRetryAfter($data, $resp) : $this->backoff($attempt);
                usleep((int) ($delay * 1_000_000));
                $attempt++;
                continue;
            }

            if ($statusCode >= 200 && $statusCode < 300) {
                return $data;
            }

            $this->raiseForStatus($statusCode, $data, $resp);
        }
    }

    private function backoff(int $attempt): float
    {
        return (2 ** $attempt) * 0.5;
    }

    private function parseRetryAfter(array $data, ResponseInterface $response): float
    {
        $retryAfter = $data['retry_after'] ?? null;
        if (is_numeric($retryAfter)) {
            return (float) $retryAfter;
        }
        $header = $response->getHeaderLine('Retry-After');
        return is_numeric($header) ? (float) $header : 60.0;
    }

    private function raiseForStatus(int $status, array $data, mixed $response): never
    {
        $message = $data['message'] ?? $data['detail'] ?? 'API error';
        if (is_array($message)) {
            $message = implode(' | ', $message);
        }

        // Insensible à la casse : le backend envoie tantôt "PUBLIC_KEY_NOT_ALLOWED",
        // tantôt "public_key_not_allowed" selon le chemin qui a rejeté la requête.
        $rawCode = $data['code'] ?? null;
        $code = is_string($rawCode) ? strtolower($rawCode) : null;

        match (true) {
            $status === 401 => throw new Exception\SanghoAuthException(
                $message,
                'authentication_error',
                401,
                $data,
                type: 'AUTHENTICATION_ERROR',
            ),
            $status === 403 && $code === 'public_key_not_allowed' => throw new Exception\SanghoPublicKeyException(
                $message,
                $rawCode,
                403,
                $data,
                type: 'PERMISSION_ERROR',
            ),
            $status === 403 => throw new Exception\SanghoPermissionException(
                $message,
                'permission_denied',
                403,
                $data,
                type: 'PERMISSION_ERROR',
            ),
            $status === 404 => throw new Exception\SanghoNotFoundException(
                $message,
                'not_found',
                404,
                $data,
                type: 'NOT_FOUND_ERROR',
            ),
            $status === 409 => throw new Exception\SanghoIdempotencyException(
                'Idempotency key reused with different request parameters.',
                'idempotency_conflict',
                409,
                $data,
                type: 'CONFLICT_ERROR',
            ),
            $status === 422 => throw new Exception\SanghoValidationException($data),
            $status === 429 => throw new Exception\SanghoRateLimitException(
                (int) $this->parseRetryAfter($data, $response),
                $data,
            ),
            default => throw new Exception\SanghoException($message, 'api_error', $status, $data),
        };
    }
}
