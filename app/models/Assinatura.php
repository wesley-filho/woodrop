<?php
class Assinatura {
    static function ativa(int $uid): ?array {
        return q1('SELECT a.*, p.nome plano_nome, p.periodo FROM assinaturas a JOIN planos_assinatura p ON p.id=a.plano_id WHERE a.usuario_id=? AND a.ativo=1 AND a.fim >= CURDATE() ORDER BY a.fim DESC LIMIT 1', [$uid]);
    }
    static function historico(int $uid): array {
        return qa('SELECT a.*, p.nome plano_nome, p.periodo FROM assinaturas a JOIN planos_assinatura p ON p.id=a.plano_id WHERE a.usuario_id=? ORDER BY a.id DESC', [$uid]);
    }
}
