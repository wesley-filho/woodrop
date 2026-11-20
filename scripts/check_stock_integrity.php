<?php
/** CRON (diário). RN07: dispara questionários de verificação de estoque; RN08: alerta de estoque baixo.
 *  crontab: 0 8 * * * php /caminho/woodrop/scripts/check_stock_integrity.php */
if (PHP_SAPI !== 'cli') exit('CLI apenas.');
require __DIR__ . '/../app/bootstrap.php';
$jaAvisado = fn(int $uid, string $titulo) => (bool)q1('SELECT id FROM notificacoes WHERE usuario_id=? AND titulo=? AND criado_em > (NOW() - INTERVAL 1 DAY)', [$uid, $titulo]);
$n = 0;
foreach (qa('SELECT id FROM usuarios WHERE tipo="fornecedor" AND ativo=1') as $f) {
    $fid = (int)$f['id'];
    $pend = StockService::pendentesVerificacao($fid);                         // RN07
    if ($pend && !$jaAvisado($fid, 'Verificação de estoque necessária')) {
        Notificacao::criar($fid, 'Verificação de estoque necessária', count($pend) . ' produto(s) precisam de confirmação de quantidade no seu painel.'); $n++;
    }
    foreach (qa('SELECT * FROM produtos WHERE fornecedor_id=? AND ativo=1 AND estoque_atual<=estoque_minimo', [$fid]) as $p) { // alerta de estoque baixo
        if (!$jaAvisado($fid, 'Alerta de estoque baixo')) { NotificationService::estoqueBaixo($fid, $p); $n++; }
    }
}
log_action('cron_estoque', "notificacoes=$n", null);
echo date('c') . " check_stock_integrity: $n notificações.\n";
