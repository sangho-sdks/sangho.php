<?php

declare(strict_types=1);

namespace Sangho\Resource;

use Sangho\HttpClient;

/**
 * Marketplace / Connect : comptes des vendeurs d'une plateforme et KYC hébergé par Sangho. Clé secrète uniquement.
 *
 * Réservé aux Apps ayant le statut **Partenaire Plateforme** (approuvé manuellement par Sangho depuis le
 * back-office, après une demande faite sur le dashboard) : une clé secrète valide ne suffit pas, une App
 * marchande ordinaire (B2C) reçoit un 403 `SanghoPlatformPartnerRequiredException` sur n'importe quel appel.
 *
 * Un compte : ['id' => 'acct_…', 'object' => 'account', 'external_id', 'email', 'business_name', 'status',
 * 'charges_enabled', 'payouts_enabled', 'kyc_level', 'livemode', 'created'] avec `status` ∈ pending_claim → linked
 * (réclamé) → active (KYC validé) ; restricted et disabled coupent les encaissements. Les capacités sont posées par
 * Sangho, jamais par la plateforme.
 */
class Connect extends AbstractResource
{
    public readonly ConnectAccounts $accounts;
    public readonly ConnectPayments $payments;

    public function __construct(HttpClient $http)
    {
        parent::__construct($http);
        $this->accounts = new ConnectAccounts($http);
        $this->payments = new ConnectPayments($http);
    }
}
