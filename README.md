# Sangho PHP SDK

SDK officiel PHP pour l'API [Sangho](https://sangho.ga) — paiements XAF pour l'Afrique.

[![Packagist](https://img.shields.io/packagist/v/sangho/sdk.svg)](https://packagist.org/packages/sangho/sdk)
[![CI](https://github.com/sangho-sdks/sangho.php/actions/workflows/ci.yml/badge.svg)](https://github.com/sangho-sdks/sangho.php/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

---

## Installation

```bash
composer require sangho/sdk
```

## Quickstart

```php
use Sangho\SanghoClient;

$client = new SanghoClient('sk_prod_...');

// Créer un payment intent
$intent = $client->paymentIntents->create([
    'amount'   => 5000,
    'currency' => 'XAF',      // exigé par le backend
    'customer_email' => 'ada@example.com',
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
    $intent = $client->paymentIntents->create(['amount' => 5000, 'currency' => 'XAF']);
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

## Marketplace / Connect

Une plateforme (ex. une place de marché) crée un compte Sangho par vendeur ; Sangho reste seul responsable du
paiement et du **KYC** (la plateforme ne collecte ni ne stocke aucune pièce d'identité). Clé secrète requise
**et** l'App appelante doit avoir le statut **Partenaire Plateforme** — accordé manuellement par Sangho (revue
back-office) après une demande faite depuis le dashboard, pas une simple histoire de clé ou de plan tarifaire.
Une App marchande ordinaire (B2C, sans ce statut) reçoit un 403 `SanghoPlatformPartnerRequiredException` :

```php
use Sangho\Exception\SanghoPlatformPartnerRequiredException;

try {
    $client->connect->accounts->list();
} catch (SanghoPlatformPartnerRequiredException $e) {
    // Cette App n'a pas (encore) le statut Partenaire Plateforme.
}
```

```php
use Sangho\Exception\SanghoConflictException;
use Sangho\SanghoClient;

$client = new SanghoClient('sk_prod_...');

// 1. Créer le compte du vendeur — idempotent par external_id (même réponse qu'il existe déjà ou non)
$account = $client->connect->accounts->create(
    ['external_id' => 'seller-42', 'email' => 'ada@example.com', 'business_name' => 'Boutique Ada'],
    'connect-account-seller-42', // clé d'idempotence
);
// $account['status'] === 'pending_claim'. `claim_token` n'est renvoyé QU'À la création : envoyez-le au
// vendeur par e-mail (lien de réclamation Sangho), sans le stocker ni le journaliser.
// Perdu ou expiré : $client->connect->accounts->reissueClaimToken($account['id']) (l'ancien est invalidé)

// 2. Une fois le compte réclamé par le vendeur, lancer le KYC hébergé par Sangho
try {
    $session = $client->connect->accounts->createKycSession(
        $account['id'],
        'https://maplateforme.com/wallet/', // return_url, https obligatoire
        'https://maplateforme.com/wallet/', // refresh_url
    );
    header('Location: ' . $session['url']); // lien valable environ une heure
} catch (SanghoConflictException $e) {
    if ($e->errorCode === 'account_not_claimed') {
        // le vendeur n'a pas encore réclamé son compte
    }
}

// 3. Le résultat arrive par webhook : `kyc.updated` et `account.updated` (payload = compte à jour)
$event = SanghoClient::constructEvent($rawBody, $_SERVER['HTTP_SANGHO_SIGNATURE'], $webhookSecret);
if ($event['type'] === 'kyc.updated') {
    $account = $event['data']['object'];
    if ($account['charges_enabled']) {
        // n'exposez les produits du vendeur que si les encaissements sont activés
    }
}

// Relecture (resynchronisation périodique, mode dégradé)
$current = $client->connect->accounts->retrieve($account['id']);
```

Statuts : `pending_claim` → `linked` (réclamé) → `active` (KYC validé) ; `restricted` (capacités limitées) et
`disabled` (désactivé par Sangho) coupent les encaissements.

### Paiement sécurisé à la livraison (séquestre)

```php
$session = $client->checkoutSessions->create([
    'line_items' => [['name' => 'Robe', 'unit_amount' => 25000, 'quantity' => 1]],
    'success_url' => 'https://boutique.example/checkout/return/42',
    'shipping_amount' => 2000,
    'connect' => ['account' => $account['id'], 'mode' => 'escrow', 'commission' => 1250, 'external_reference' => 'ORDER-42'],
]);
$paymentId = $session['connect_payment']; // cpay_…

// Livraison confirmée : libérer les fonds (clé d'idempotence OBLIGATOIRE)
$client->connect->payments->release($paymentId, 'release-ORDER-42');

// Litige : geler, puis libérer ou rembourser
$client->connect->payments->freeze($paymentId);
$client->connect->payments->refund($paymentId, 'product', 'refund-ORDER-42', reason: 'non livré');

$balance = $client->connect->accounts->balance($account['id']); // available, held, frozen, reserve, negative, paid_out
$client->connect->accounts->createPayout($account['id'], '5000', 'mobile:077000000', 'payout-42-1');
```

## Webhooks

`SanghoClient::constructEvent($corpsBrut, $enTete, $secret)` (ou `Sangho::constructEvent`) vérifie
`Sangho-Signature: t=<ts>,v1=<hex>` (HMAC-SHA256 de `"<ts>.<corps brut>"`, tolérance 5 min par défaut, comparaison à
temps constant). Passez le corps **brut** (`file_get_contents('php://input')`), jamais un JSON re-sérialisé. Pendant une
rotation de secret, passez un tableau (`[$nouveau, $ancien]`) ; plusieurs `v1` dans l'en-tête sont acceptés. En cas de
refus : `SanghoWebhookSignatureException` (sous-classe de `SanghoException`) avec `$e->reason` ∈ `malformed` / `expired` /
`mismatch`. Pour tester votre endpoint : `SanghoClient::generateTestHeader($corps, $secret)`.

## Idempotence

Toutes les méthodes `create` acceptent une clé d'idempotence en dernier argument (`$client->paymentIntents->create($p,
'order-42')`, ou `'idempotency_key'` dans le tableau) : rejouer un appel avec la **même** clé et le même corps renvoie
la même réponse ; la même clé avec un corps différent lève `SanghoIdempotencyException` (409). Sans clé, le SDK en
génère une nouvelle à chaque appel et **ne rejoue pas** un POST après un timeout ou une erreur réseau (le serveur a pu
le traiter) ; avec une clé fournie, ce rejeu est sûr et activé.

## Documentation

La documentation complète est disponible sur [docs.sangho.ga/api/sdks/php](https://docs.sangho.ga/api/sdks/php/).

## Ressources disponibles

`account` · `addresses` · `apps` · `customers` · `products` · `paymentIntents` ·
`checkoutSessions` · `invoices` · `transactions` · `refunds` · `subscriptions` ·
`paymentMethods` · `receipts` · `webhooks` · `paymentLinks` · `security` ·
`partners` · `terminal` · `sandbox` · `connect`

## Contribuer

Voir [CONTRIBUTING.md](CONTRIBUTING.md).

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md).

## Licence

MIT
