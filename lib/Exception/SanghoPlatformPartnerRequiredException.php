<?php

declare(strict_types=1);

namespace Sangho\Exception;

/**
 * 403 — App valide (clé secrète correcte) mais sans le statut **Partenaire
 * Plateforme** requis pour appeler `connect.*`. Ce statut est accordé
 * manuellement par Sangho (revue back-office) après une demande faite depuis
 * le dashboard — ce n'est pas une histoire de clé ou de plan tarifaire, donc
 * distinct de `SanghoPublicKeyException`. Sous-classe de
 * `SanghoPermissionException` : un `catch (SanghoPermissionException)`
 * générique continue de l'attraper.
 */
class SanghoPlatformPartnerRequiredException extends SanghoPermissionException
{
}
