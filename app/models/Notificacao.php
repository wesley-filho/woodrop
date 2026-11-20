<?php
class Notificacao {
    static function criar(int $uid, string $titulo, string $msg = ''): void { q('INSERT INTO notificacoes (usuario_id,titulo,mensagem) VALUES (?,?,?)', [$uid, $titulo, $msg]); }
    static function recentes(int $uid, int $n = 8): array { return qa('SELECT * FROM notificacoes WHERE usuario_id=? ORDER BY id DESC LIMIT ' . (int)$n, [$uid]); }
    static function naoLidas(int $uid): int { return (int)q1('SELECT COUNT(*) c FROM notificacoes WHERE usuario_id=? AND lida=0', [$uid])['c']; }
    static function marcarLidas(int $uid): void { q('UPDATE notificacoes SET lida=1 WHERE usuario_id=?', [$uid]); }
}
