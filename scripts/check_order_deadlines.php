<?php
/** CRON (a cada hora). RN03: fornecedor tem 24 horas úteis para confirmar o pedido; senão, notificação + penalidade.
 *  crontab: 0 * * * * php /caminho/woodrop/scripts/check_order_deadlines.php */
if (PHP_SAPI !== 'cli') exit('CLI apenas.');
require __DIR__ . '/../app/bootstrap.php';
$n = 0;
foreach (qa('SELECT id, fornecedor_id, vendedor_id, criado_em FROM pedidos WHERE status="recebido" AND penalizado=0') as $p) {
    if (horas_uteis($p['criado_em'], date('Y-m-d H:i:s')) < PRAZO_CONFIRMACAO_H) continue;
    q('UPDATE pedidos SET penalizado=1 WHERE id=?', [$p['id']]);
    q('UPDATE usuarios SET penalidades = penalidades + 1 WHERE id=?', [$p['fornecedor_id']]);
    Notificacao::criar((int)$p['fornecedor_id'], "Prazo vencido – pedido #{$p['id']}", 'O prazo de ' . PRAZO_CONFIRMACAO_H . 'h úteis para confirmação expirou (RN03). Uma penalidade foi registrada.');
    Notificacao::criar((int)$p['vendedor_id'], "Pedido #{$p['id']} sem confirmação", 'O fornecedor não confirmou o pedido no prazo.');
    log_action('penalidade_rn03', "pedido={$p['id']}", (int)$p['fornecedor_id']); $n++;
}
echo date('c') . " check_order_deadlines: $n penalidades.\n";
