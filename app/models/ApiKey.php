<?php
/** RN02: integração autenticada por API Key. Armazena apenas o hash SHA-256; a chave em claro aparece uma única vez. */
class ApiKey {
    static function gerar(int $uid, string $desc = ''): string {
        $plain = bin2hex(random_bytes(32));
        q('INSERT INTO api_keys (usuario_id,chave,descricao) VALUES (?,?,?)', [$uid, hash('sha256', $plain), mb_substr($desc, 0, 100)]);
        log_action('api_key_gerada', '', $uid);
        return $plain;
    }
    static function validar(string $plain): ?array {
        $r = q1('SELECT u.*, k.id key_id FROM api_keys k JOIN usuarios u ON u.id=k.usuario_id WHERE k.chave=? AND k.ativa=1 AND u.ativo=1', [hash('sha256', $plain)]);
        if ($r) q('UPDATE api_keys SET ultimo_uso=NOW() WHERE id=?', [$r['key_id']]);
        return $r;
    }
    static function doUsuario(int $uid): array { return qa('SELECT id,descricao,ativa,criado_em,ultimo_uso FROM api_keys WHERE usuario_id=? ORDER BY id DESC', [$uid]); }
    static function revogar(int $id, int $uid): void { q('UPDATE api_keys SET ativa=0 WHERE id=? AND usuario_id=?', [$id, $uid]); log_action('api_key_revogada', "id=$id", $uid); }
}
