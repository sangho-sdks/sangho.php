# Sangho PHP SDK

SDK officiel PHP pour l'API [Sangho](https://sangho.ga) — paiements XAF pour l'Afrique.

[![Packagist](https://img.shields.io/packagist/v/sangho/sangho.svg)](https://packagist.org/packages/sangho/sangho)
[![CI](https://github.com/sangho-sdks/sangho.php/actions/workflows/ci.yml/badge.svg)](https://github.com/sangho-sdks/sangho.php/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

---

## Installation

```bash
composer require sangho/sangho
```

## Quickstart

```php
use Sangho\SanghoClient;

$client = new SanghoClient('sk_prod_...');

// Créer un payment intent
$intent = $client->paymentIntents->create([
    'amount'   => 5000,
    'currency' => 'XAF',
    'customer' => 'cust_xxx',
]);

echo $intent['id'];
```

## Gestion des erreurs

```php
use Sangho\Exception\{
    SanghoAuthException,
    SanghoRateLimitException,
    SanghoValidationException,
    SanghoException,
};

try {
    $intent = $client->paymentIntents->create(['amount' => 5000, 'customer' => 'cust_xxx']);
} catch (SanghoAuthException $e) {
    echo "Clé API invalide";
} catch (SanghoRateLimitException $e) {
    echo "Trop de requêtes, retenter après {$e->retryAfter}s";
} catch (SanghoValidationException $e) {
    echo $e->getParam() . ': ' . $e->getMessage();
} catch (SanghoException $e) {
    echo "{$e->errorCode} — {$e->getMessage()} ({$e->statusCode})";
}
```

## Documentation

La documentation complète est disponible sur [docs.sangho.ga/api/sdks/php](https://docs.sangho.ga/api/sdks/php/).

## Ressources disponibles

`account` · `addresses` · `apps` · `customers` · `products` · `paymentIntents` ·
`checkoutSessions` · `invoices` · `transactions` · `refunds` · `subscriptions` ·
`paymentMethods` · `receipts` · `webhooks` · `paymentLinks` · `security` ·
`partners` · `terminal` · `sandbox`

## Contribuer

Voir [CONTRIBUTING.md](CONTRIBUTING.md).

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md).

## Licence

MIT
