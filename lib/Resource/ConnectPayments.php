<?php

declare(strict_types=1);

namespace Sangho\Resource;

use Sangho\Exception\SanghoValidationException;

/**
 * `$client->connect->payments` — instructions sur un paiement avec répartition (`cpay_…`).
 *
 * La plateforme DÉCIDE, Sangho EXÉCUTE : libérer, rembourser, geler. Création : `checkoutSessions->create([... 'connect' => [...]])`.
 * Montants renvoyés en chaînes décimales. Clé secrète uniquement.
 */
class ConnectPayments extends AbstractResource
{
    protected string $path = '/connect/payments/';

    /** Les écritures d'argent exigent une clé d'idempotence STABLE (ex. `release-<commande>`) : un rejeu ne doit jamais dupliquer l'opération. */
    public static function requireKey(string $method, ?string $key): string
    {
        if ($key === null || $key === '') {
            throw new SanghoValidationException(['message' => "'{$method}' exige une clé d'idempotence stable, ex : 'release-<order_id>'."]);
        }
        return $key;
    }

    /**
     * Lit le paiement : mode (`escrow` / `instant`), montants, commission, état.
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('connect.payments.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }

    /**
     * Libère les fonds bloqués (ou gelés) au vendeur, commission retenue. Idempotent ; événement `funds.released`.
     *
     * @return array<string, mixed>
     */
    public function release(string $id, string $idempotencyKey): array
    {
        $this->http->assertSecretKey('connect.payments.release');
        $key = self::requireKey('connect.payments.release', $idempotencyKey);
        return $this->http->post("{$this->path}{$id}/release/", [], $key) ?? [];
    }

    /**
     * Rembourse le client. `$scope` : `product` (livraison conservée), `full` (produit + livraison, reprise des frais déjà
     * versés) ou `amount` (montant libre, `$amount` requis).
     *
     * @param int|float|string|null $amount
     * @return array<string, mixed>
     */
    public function refund(string $id, string $scope, string $idempotencyKey, $amount = null, ?string $reason = null): array
    {
        $this->http->assertSecretKey('connect.payments.refund');
        $key = self::requireKey('connect.payments.refund', $idempotencyKey);
        if (!in_array($scope, ['product', 'full', 'amount'], true)) {
            throw new SanghoValidationException(['message' => 'scope doit valoir "product", "full" ou "amount".']);
        }
        $body = ['scope' => $scope];
        if ($amount !== null) {
            $body['amount'] = $amount;
        }
        if ($reason !== null && $reason !== '') {
            $body['reason'] = $reason;
        }
        return $this->http->post("{$this->path}{$id}/refund/", $body, $key) ?? [];
    }

    /**
     * Bloqué → gelé (litige) ; événement `funds.frozen`.
     *
     * @return array<string, mixed>
     */
    public function freeze(string $id, ?string $idempotencyKey = null): array
    {
        $this->http->assertSecretKey('connect.payments.freeze');
        return $this->http->post("{$this->path}{$id}/freeze/", [], $idempotencyKey) ?? [];
    }

    /**
     * Gelé → bloqué ; événement `funds.unfrozen`.
     *
     * @return array<string, mixed>
     */
    public function unfreeze(string $id, ?string $idempotencyKey = null): array
    {
        $this->http->assertSecretKey('connect.payments.unfreeze');
        return $this->http->post("{$this->path}{$id}/unfreeze/", [], $idempotencyKey) ?? [];
    }

    /**
     * Sandbox uniquement : simule l'encaissement du paiement.
     *
     * @return array<string, mixed>
     */
    public function simulatePayment(string $id): array
    {
        $this->http->assertSecretKey('connect.payments.simulatePayment');
        return $this->http->post("{$this->path}{$id}/simulate-payment/") ?? [];
    }
}
