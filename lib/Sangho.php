<?php

declare(strict_types=1);

namespace Sangho;

/**
 * Static facade — Sangho::setApiKey() + Sangho::client()->resource->method() style.
 *
 * Usage:
 *   Sangho::setApiKey('sk_test_xxx');
 *   $customers = Sangho::client()->customers->list(['status' => 'active']);
 */
class Sangho
{
    private static ?string $apiKey = null;
    private static string $baseUrl = 'https://api.sangho.ga/v1';
    private static int $timeout = 30;
    private static int $maxRetries = 3;

    private static ?SanghoClient $instance = null;

    public static function setApiKey(string $key): void
    {
        self::$apiKey = $key;
        self::$instance = null; // reset on key change
    }

    public static function setBaseUrl(string $url): void
    {
        self::$baseUrl = $url;
        self::$instance = null;
    }

    public static function setTimeout(int $seconds): void
    {
        self::$timeout = $seconds;
        self::$instance = null;
    }

    public static function setMaxRetries(int $retries): void
    {
        self::$maxRetries = $retries;
        self::$instance = null;
    }

    public static function client(): SanghoClient
    {
        if (self::$apiKey === null) {
            throw new \RuntimeException(
                'Sangho API key not set. Call Sangho::setApiKey("sk_…") first.'
            );
        }
        return self::$instance ??= new SanghoClient(self::$apiKey, self::$baseUrl, self::$timeout, self::$maxRetries);
    }

    /** Raccourci vers SanghoClient::constructEvent — pas besoin de clé API pour vérifier une signature. */
    public static function constructEvent(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = 300,
    ): array {
        return SanghoClient::constructEvent($payload, $signatureHeader, $secret, $tolerance);
    }
}
