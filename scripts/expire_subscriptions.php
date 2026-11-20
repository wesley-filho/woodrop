<?php
/** CRON (diário). RN06: desativa assinaturas vencidas e avisa o usuário.  crontab: 5 0 * * * php /caminho/woodrop/scripts/expire_subscriptions.php */
if (PHP_SAPI !== 'cli') exit('CLI apenas.');
require __DIR__ . '/../app/bootstrap.php';
$n = 0;
foreach (qa('SELECT id, usuario_id FROM assinaturas WHERE ativo=1 AND fim < CURDATE()') as $a) {
    q('UPDATE assinaturas SET ativo=0 WHERE id=?', [$a['id']]);
    Notificacao::criar((int)$a['usuario_id'], 'Assinatura vencida', 'Renove seu plano para continuar acessando a plataforma (RN06).'); $n++;
}
log_action('cron_assinaturas', "expiradas=$n", null);
echo date('c') . " expire_subscriptions: $n assinaturas expiradas.\n";
