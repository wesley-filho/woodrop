<?php
class ApiAuthService {
    static function extrairChave(): ?string {
        $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!$h && function_exists('getallheaders')) { foreach (getallheaders() as $k => $v) if (strtolower($k) === 'authorization') $h = $v; }
        if (preg_match('/^Bearer\s+([A-Fa-f0-9]{64})$/', trim($h), $m)) return $m[1];
        $x = $_SERVER['HTTP_X_API_KEY'] ?? '';
        return preg_match('/^[A-Fa-f0-9]{64}$/', $x) ? $x : null;
    }
    static function autenticar(): array {
        $k = self::extrairChave();
        if (!$k) json_out(false, null, 'API Key ausente ou inválida.', 401);
        $u = ApiKey::validar($k);
        if (!$u) { log_action('api_auth_falha', '', null); json_out(false, null, 'API Key inválida ou revogada.', 401); }
        if (!rate_limit('api:' . $u['key_id'], SubscriptionService::limiteApi((int)$u['id']), 60)) json_out(false, null, 'Limite de requisições excedido.', 429);
        if ($u['tipo'] !== 'admin' && !Assinatura::ativa((int)$u['id'])) json_out(false, null, 'RN06: assinatura ativa necessária.', 402);
        return $u;
    }
}
