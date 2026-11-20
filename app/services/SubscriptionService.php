<?php
class SubscriptionService {
    /** RF10/RN06: contrata plano (pagamento simulado) e ativa a assinatura. */
    static function contratar(int $uid, int $planoId, string $metodo = 'pix'): ?string {
        $pl = PlanoAssinatura::achar($planoId);
        if (!$pl || !$pl['ativo']) return 'Plano inválido.';
        $meses = PlanoAssinatura::MESES[$pl['periodo']];
        $pdo = db(); $pdo->beginTransaction();
        q('UPDATE assinaturas SET ativo=0 WHERE usuario_id=?', [$uid]);
        q('INSERT INTO assinaturas (usuario_id,plano_id,inicio,fim) VALUES (?,?,CURDATE(),DATE_ADD(CURDATE(), INTERVAL ? MONTH))', [$uid, $planoId, $meses]);
        $aid = (int)$pdo->lastInsertId();
        Pagamento::registrar($aid, PlanoAssinatura::precoFinal($pl), 'aprovado', $metodo); // gateway real: trabalho futuro
        $pdo->commit();
        log_action('assinatura_contratada', "plano={$pl['nome']}", $uid);
        return null;
    }
    /** Plus/Premium (não mensal) liberam relatórios e SKUs ilimitados. */
    static function recursoAvancado(int $uid): bool { $a = Assinatura::ativa($uid); return $a && $a['periodo'] !== 'mensal'; }
    static function podeCriarSku(int $uid): bool { return self::recursoAvancado($uid) || Produto::contar($uid) < LIMITE_SKU_BASICO; }
    /** Limite de requisições/min da API: Básico 60, Plus 300, Premium (API dedicada) 1000. */
    static function limiteApi(int $uid): int { $a = Assinatura::ativa($uid); return !$a ? 60 : ['mensal' => 60, 'semestral' => 300, 'anual' => 1000][$a['periodo']]; }
}
