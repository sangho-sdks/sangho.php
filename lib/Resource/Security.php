<?php

declare(strict_types=1);

namespace Sangho\Resource;

class Security extends AbstractResource
{
    protected string $path = '/security/';
    public function retrieve(): array
    {
        $this->http->assertSecretKey('security.retrieve');
        return $this->http->get("{$this->path}me/");
    }
    public function update(array $p): array
    {
        $this->http->assertSecretKey('security.update');
        return $this->http->patch("{$this->path}update_me/", $p);
    }
    public function addAllowedIps(array $ips): array
    {
        // Pas d'action dédiée côté backend pour ajouter/retirer des IP : on
        // relit le profil, on recompose la liste complète, puis on la renvoie
        // via update().
        $this->http->assertSecretKey('security.addAllowedIps');
        $profile = $this->retrieve();
        $merged = array_values(array_unique([...($profile['allowed_ips'] ?? []), ...$ips]));
        return $this->update(['allowed_ips' => $merged]);
    }
    public function removeAllowedIps(array $ips): array
    {
        $this->http->assertSecretKey('security.removeAllowedIps');
        $profile = $this->retrieve();
        $remaining = array_values(array_diff($profile['allowed_ips'] ?? [], $ips));
        return $this->update(['allowed_ips' => $remaining]);
    }
    public function rollSecretKey(): array
    {
        $this->http->assertSecretKey('security.rollSecretKey');
        return $this->http->post("{$this->path}roll-secret/");
    }
    public function listSessions(array $c = []): array
    {
        $this->http->assertSecretKey('security.listSessions');
        return $this->http->get("{$this->path}sessions/", $c);
    }
    public function revokeSession(string $sessionId): void
    {
        $this->http->assertSecretKey('security.revokeSession');
        $this->http->delete("{$this->path}sessions/{$sessionId}/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
