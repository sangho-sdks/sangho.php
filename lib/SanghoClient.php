<?php

declare(strict_types=1);

namespace Sangho;

use Sangho\Resource\{
    Account,
    Addresses,
    Apps,
    Customers,
    Products,
    PaymentIntents,
    PaymentLinks,
    CheckoutSessions,
    Invoices,
    Transactions,
    Refunds,
    Subscriptions,
    PaymentMethods,
    Receipts,
    Webhooks,
    Security,
    Partners,
    Terminal,
    Sandbox
};

class SanghoClient
{
    public readonly Account $account;
    public readonly Addresses $addresses;
    public readonly Apps $apps;
    public readonly Customers $customers;
    public readonly Products $products;
    public readonly PaymentIntents $paymentIntents;
    public readonly PaymentLinks $paymentLinks;
    public readonly CheckoutSessions $checkoutSessions;
    public readonly Invoices $invoices;
    public readonly Transactions $transactions;
    public readonly Refunds $refunds;
    public readonly Subscriptions $subscriptions;
    public readonly PaymentMethods $paymentMethods;
    public readonly Receipts $receipts;
    public readonly Webhooks $webhooks;
    public readonly Security $security;
    public readonly Partners $partners;
    public readonly Terminal $terminal;
    public readonly Sandbox $sandbox;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.sangho.ga/v1',
        int $timeout = 30,
        int $maxRetries = 3,
    ) {
        $http = new HttpClient($apiKey, $baseUrl, $timeout, $maxRetries);

        $this->account = new Account($http);
        $this->addresses = new Addresses($http);
        $this->apps = new Apps($http);
        $this->customers = new Customers($http);
        $this->products = new Products($http);
        $this->paymentIntents = new PaymentIntents($http);
        $this->paymentLinks = new PaymentLinks($http);
        $this->checkoutSessions = new CheckoutSessions($http);
        $this->invoices = new Invoices($http);
        $this->transactions = new Transactions($http);
        $this->refunds = new Refunds($http);
        $this->subscriptions = new Subscriptions($http);
        $this->paymentMethods = new PaymentMethods($http);
        $this->receipts = new Receipts($http);
        $this->webhooks = new Webhooks($http);
        $this->security = new Security($http);
        $this->partners = new Partners($http);
        $this->terminal = new Terminal($http);
        $this->sandbox = new Sandbox($http);
    }

    /**
     * Vérifie et parse un événement webhook entrant (signature HMAC-SHA256 +
     * protection anti-replay). Délègue à Webhooks::constructEvent — exposé
     * ici aussi pour un accès direct sans instancier de ressource.
     */
    public static function constructEvent(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = 300,
    ): array {
        return Webhooks::constructEvent($payload, $signatureHeader, $secret, $tolerance);
    }
}
