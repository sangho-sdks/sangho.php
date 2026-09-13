# Changelog

Tous les changements notables sont documentés ici.

Format basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).
Ce projet respecte le [Semantic Versioning](https://semver.org/lang/fr/).

---

## [Unreleased]

### Added

### Changed

### Deprecated

### Removed

### Fixed

### Security

---

## [1.1.0] - 2026-09-02

Mise à jour de parité avec le SDK JS (`sangho-sdk-js`).

### Added
- Nouvelles ressources : `account` (`retrieve`), `addresses` (CRUD complet),
  `terminal` (`readers`/`sessions`/`offline`), `sandbox` (`reset`).
- `customers->listPaymentMethods($id)`.
- `checkoutSessions->delete($id)`.
- `invoices->getPdfUrl($id)`.
- `paymentLinks->archive($id)` / `paymentLinks->restore($id)` / `paymentLinks->delete($id)`.
- `transactions->update($id, $p)` / `transactions->cancel($id)`.
- `apps->keys($id)`.
- `security->addAllowedIps($ips)` / `security->removeAllowedIps($ips)`.
- `webhooks->disable($id)` / `webhooks->enable($id)` / `webhooks->retrieveDelivery($id, $deliveryId)`.
- `HttpClient(..., maxRetries: 3)` configurable (au lieu d'un nombre de retries fixe).
- `SanghoClient::constructEvent(...)` et `Sangho::constructEvent(...)` exposés
  directement, en plus de `Webhooks::constructEvent(...)`.
- Exceptions réseau distinctes : `SanghoNetworkException`, `SanghoTimeoutException`
  (les exceptions Guzzle brutes ne remontent plus telles quelles).
- Chaque exception expose désormais un `type` (catégorie large), à l'image du SDK JS.
- Headers `X-Sangho-SDK` et `X-Sangho-Environment` sur chaque requête.
- Validation HTTPS du `baseUrl` (refuse l'envoi de la clé API en clair, sauf
  `localhost`/`127.0.0.1`) et validation stricte du format de clé API.
- `.github/workflows/ci.yml` : tests sur la matrice PHP 8.2/8.3/8.4/8.5.
- `.github/workflows/release.yml` : automatisation de release (bump de
  version, tag git, build, notification Packagist), adapté du workflow du SDK JS.

### Changed
- `baseUrl` par défaut : `https://api.sangho.ga/v1` (au lieu de `https://api.sangho.com/v1` — mauvais domaine).
- Préfixes de clé API valides : `pk_prod_` / `sk_prod_` / `pk_test_` / `sk_test_`
  (au lieu de `pk_live_` / `sk_live_` qui n'existent pas côté backend).
- `security->retrieve()` / `security->update()` utilisent désormais les bonnes
  routes (`/security/me/`, `/security/update_me/`) au lieu de `/security/`.
- Le retry sur 429 respecte désormais `retry_after` (délai serveur) en
  priorité sur le backoff exponentiel.
- `composer.json` : `php` → `^8.2 || ^8.3 || ^8.4 || ^8.5` (dernière stable +
  3 précédentes), auteur et URLs (`homepage`, `support.source`/`issues`)
  alignés sur le `package.json` du SDK JS (`github.com/sangho-sdks/sangho.php`).
- README : quickstart corrigé — utilisait `new Sangho('sk_live_...')` (la
  classe `Sangho` est une façade statique sans constructeur prenant une clé)
  et `$intent->id` (les ressources renvoient des tableaux, pas des objets).
- Docblock de `Sangho.php` corrigé — décrivait une API statique
  `Resource\Customers::all()` qui n'a jamais existé dans le code.
- Makefile : cible `publish` corrigée — `composer publish` n'existe pas ;
  remplacée par une notification à l'API Packagist (le webhook GitHub
  resynchronise déjà automatiquement à chaque tag).

### Removed
- `paymentLinks->deactivate($id)` — remplacé par `archive`/`restore`/`delete`
  (le endpoint `/deactivate/` ne correspond à aucune route backend confirmée).

### Fixed
- `SanghoRateLimitException` lit désormais `retry_after` (et non `retry_later`,
  qui n'existe pas côté backend — le délai de retry n'était jamais respecté).
- Comparaison de `code === 'public_key_not_allowed'` désormais insensible à la
  casse (le backend renvoie parfois `PUBLIC_KEY_NOT_ALLOWED`).
- `checkoutSessions->retrieve()` n'exige plus de clé secrète — le backend
  autorise explicitement la clé publique sur cette route (page de
  confirmation côté navigateur).
- `composer.json` référençait `phpcs`/`phpstan` dans le Makefile sans les
  déclarer en `require-dev` — ajoutés.
- Suite de tests mise à jour en conséquence (domaine `.ga`, préfixes
  `_prod_`) ; nouveau `HttpClientTest.php` couvrant les fixes ci-dessus.

---

## [1.0.0] - 2026-04-01

### Added

- Version initiale du SDK
- Support de toutes les ressources : apps, customers, products, payment_intents,
  checkout_sessions, invoices, transactions, refunds, subscriptions,
  payment_methods, webhooks, payment_links, addresses, partners
- Gestion complète des erreurs (auth, validation, rate limit, réseau)
- Pagination via ListResponse
