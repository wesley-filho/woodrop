<?php
/**
 * CRON (a cada 5 min). Mercado Livre:
 *  1) processa a fila de notificações (webhooks) recebidas;
 *  2) importa vendas pagas recentes de cada conta conectada (rede de segurança para notificações perdidas);
 *  3) reenvia ao ML os anúncios cuja última sincronização falhou.
 * crontab: 0-59/5 * * * * php /caminho/woodrop/scripts/sync_mercadolivre.php
 */
if (PHP_SAPI !== 'cli') exit('CLI apenas.');
require __DIR__ . '/../app/bootstrap.php';
if (!MercadoLivreClient::configurado()) { echo "sync_mercadolivre: integração não configurada (ML_APP_ID/ML_CLIENT_SECRET/ML_REDIRECT_URI).\n"; exit(0); }

$fila = MercadoLivreService::processarFila(200);
$importados = 0; $falhas = 0;
foreach (qa('SELECT * FROM integracoes WHERE canal=? AND status="ativa"', [MercadoLivreClient::CANAL]) as $integ) {
    try { $importados += MercadoLivreService::importarRecentes($integ, 3); }
    catch (Throwable $e) { $falhas++; log_action('ml_erro', "importar conta={$integ['conta_id']}: " . $e->getMessage(), (int)$integ['usuario_id']); }
}
$reenviados = 0;
foreach (qa('SELECT anuncio_id FROM anuncios_externos WHERE canal=? AND item_id IS NOT NULL AND erro IS NOT NULL LIMIT 100', [MercadoLivreClient::CANAL]) as $x)
    if (MercadoLivreService::sincronizarAnuncio((int)$x['anuncio_id']) === null) $reenviados++;

log_action('cron_mercadolivre', "fila=$fila importados=$importados reenviados=$reenviados falhas=$falhas", null);
echo date('c') . " sync_mercadolivre: $fila notificação(ões), $importados pedido(s) importado(s), $reenviados anúncio(s) reenviado(s), $falhas falha(s).\n";
