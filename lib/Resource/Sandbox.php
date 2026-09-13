<?php

declare(strict_types=1);

namespace Sangho\Resource;

class Sandbox extends AbstractResource
{
    /**
     * Supprime toutes les données sandbox de l'app courante (customers,
     * products, payment_intents, transactions, refunds, invoices,
     * checkout_sessions, subscriptions, payment_methods, receipts).
     *
     * L'app elle-même, ses clés API et ses paramètres sont conservés.
     * Bloqué côté backend si la clé utilisée est une clé de production
     * (sk_prod_*).
     */
    public function reset(): ?array
    {
        $this->http->assertSecretKey('sandbox.reset');
        return $this->http->post('/reset/');
    }
}
