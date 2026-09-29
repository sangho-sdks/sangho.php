<?php

declare(strict_types=1);

namespace Sangho\Resource;

class ConnectAccounts extends AbstractResource
{
    protected string $path = '/connect/accounts/';

    /**
     * Crée (ou retrouve) le compte d'un vendeur — IDEMPOTENT par `external_id` ; la réponse a la même forme que le
     * compte existe déjà ou non (anti-énumération).
     *
     * `claim_token` n'est renvoyé QU'À la création : transmettez-le au vendeur par e-mail, sans le stocker ni le
     * journaliser (`reissueClaimToken` en émet un nouveau).
     *
     * @param array{external_id: string, email: string, business_name?: string, phone?: string} $p
     * @return array<string, mixed>
     */
    public function create(array $p, ?string $idempotencyKey = null): array
    {
        $this->http->assertSecretKey('connect.accounts.create');
        return $this->http->post($this->path, $p, $idempotencyKey) ?? [];
    }

    /**
     * Lit un compte : statut, capacités et niveau KYC (resynchronisation, mode dégradé).
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('connect.accounts.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }

    /**
     * Liste les comptes de la plateforme (`['object' => 'list', 'data' => [...]]`, non paginée).
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        $this->http->assertSecretKey('connect.accounts.list');
        return $this->http->get($this->path);
    }

    /**
     * Réémet le jeton de réclamation d'un compte encore `pending_claim` ; l'ancien est invalidé.
     *
     * @return array{id: string, claim_token: string}
     */
    public function reissueClaimToken(string $id): array
    {
        $this->http->assertSecretKey('connect.accounts.reissueClaimToken');
        /** @var array{id: string, claim_token: string} */
        return $this->http->post("{$this->path}{$id}/claim-token/") ?? [];
    }

    /**
     * Lance le KYC hébergé par Sangho ; redirigez le vendeur vers `$session['url']` (valable environ une heure).
     *
     * Le compte doit avoir été réclamé (sinon `SanghoConflictException`, code `account_not_claimed`) ; les adresses
     * doivent être en https. Le résultat revient par les événements `kyc.updated` / `account.updated`.
     *
     * @return array{object: string, url: string, expires_at: int, account: string}
     */
    public function createKycSession(
        string $id,
        string $returnUrl,
        ?string $refreshUrl = null,
        ?string $idempotencyKey = null,
    ): array {
        $this->http->assertSecretKey('connect.accounts.createKycSession');
        $body = ['return_url' => $returnUrl];
        if ($refreshUrl !== null && $refreshUrl !== '') {
            $body['refresh_url'] = $refreshUrl;
        }
        /** @var array{object: string, url: string, expires_at: int, account: string} */
        return $this->http->post("{$this->path}{$id}/kyc-session/", $body, $idempotencyKey) ?? [];
    }

    /**
     * Soldes du compte : `available`, `held`, `frozen`, `reserve`, `negative`, `paid_out` (chaînes décimales) —
     * source de vérité pour l'affichage de la plateforme.
     *
     * @return array<string, mixed>
     */
    public function balance(string $id): array
    {
        $this->http->assertSecretKey('connect.accounts.balance');
        return $this->http->get("{$this->path}{$id}/balance/");
    }

    /**
     * Retrait vers Mobile Money / banque : limité au disponible POSITIF (refus `negative_balance`,
     * `insufficient_available` ou KYC incomplet). La clé d'idempotence est OBLIGATOIRE.
     *
     * @param int|float|string $amount
     * @return array<string, mixed>
     */
    public function createPayout(string $id, $amount, string $destination, string $idempotencyKey): array
    {
        $this->http->assertSecretKey('connect.accounts.createPayout');
        $key = ConnectPayments::requireKey('connect.accounts.createPayout', $idempotencyKey);
        return $this->http->post("{$this->path}{$id}/payouts/", ['amount' => $amount, 'destination' => $destination], $key) ?? [];
    }

    /**
     * Retraits du compte (`['object' => 'list', 'data' => [...]]`).
     *
     * @return array<string, mixed>
     */
    public function listPayouts(string $id): array
    {
        $this->http->assertSecretKey('connect.accounts.listPayouts');
        return $this->http->get("{$this->path}{$id}/payouts/");
    }
}
