<?php
/**
 * Cliente HTTP mínimo da API do Mercado Livre (https://developers.mercadolivre.com.br).
 * URLs configuráveis por ambiente para permitir testes com servidor simulado (tests/mock_mercadolivre.php).
 */
class MercadoLivreClient {
    const CANAL = 'mercadolivre';
    const SITE = 'MLB'; // Brasil

    static function apiUrl(): string { return rtrim((string)env('ML_API_URL', 'https://api.mercadolibre.com'), '/'); }
    static function authUrl(): string { return rtrim((string)env('ML_AUTH_URL', 'https://auth.mercadolivre.com.br'), '/'); }
    static function appId(): string { return (string)env('ML_APP_ID', ''); }
    static function segredo(): string { return (string)env('ML_CLIENT_SECRET', ''); }
    static function redirectUri(): string { return (string)env('ML_REDIRECT_URI', ''); }
    static function configurado(): bool { return self::appId() !== '' && self::segredo() !== '' && self::redirectUri() !== ''; }

    /**
     * Executa uma chamada. $json vira corpo JSON; $form vira x-www-form-urlencoded (usado em /oauth/token).
     * @return array [int status HTTP (0 = falha de rede), array corpo decodificado]
     */
    static function req(string $metodo, string $path, ?array $json = null, ?string $token = null, ?array $form = null, array $headers = []): array {
        $h = array_merge(['Accept: application/json'], $headers);
        if ($token) $h[] = 'Authorization: Bearer ' . $token;
        $opt = [CURLOPT_CUSTOMREQUEST => $metodo, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20];
        if ($form !== null) { $opt[CURLOPT_POSTFIELDS] = http_build_query($form); $h[] = 'Content-Type: application/x-www-form-urlencoded'; }
        elseif ($json !== null) { $opt[CURLOPT_POSTFIELDS] = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); $h[] = 'Content-Type: application/json'; }
        $opt[CURLOPT_HTTPHEADER] = $h;
        $c = curl_init(self::apiUrl() . $path);
        curl_setopt_array($c, $opt);
        $r = curl_exec($c);
        if ($r === false) return [0, ['message' => 'Falha de conexão com o Mercado Livre: ' . curl_error($c)]];
        $d = json_decode((string)$r, true);
        return [(int)curl_getinfo($c, CURLINFO_HTTP_CODE), is_array($d) ? $d : []];
    }

    /** Mensagem legível a partir de uma resposta de erro (message + causas). */
    static function erro(array $b, int $status = 0): string {
        $m = $b['message'] ?? $b['error_description'] ?? $b['error'] ?? ($status ? "HTTP $status" : 'erro desconhecido');
        foreach (($b['cause'] ?? []) as $c) if (is_array($c) && !empty($c['message'])) $m .= ' | ' . $c['message'];
        return mb_substr((string)$m, 0, 1000);
    }

    /** PKCE (RFC 7636): verificador aleatório e desafio S256, ambos em base64url. */
    static function pkce(): array {
        $b64 = fn(string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $verifier = $b64(random_bytes(48));
        return [$verifier, $b64(hash('sha256', $verifier, true))];
    }
}
