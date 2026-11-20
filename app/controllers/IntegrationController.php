<?php
/** Integrações do vendedor com marketplaces (Mercado Livre). */
class IntegrationController {
    function index() {
        $u = AuthMiddleware::exigir(['vendedor']);
        render('integrations/index', ['title' => 'Integrações', 'configurado' => MercadoLivreClient::configurado(),
            'ml' => MercadoLivreService::integracao((int)$u['id']), 'anuncios' => MercadoLivreService::anunciosDoVendedor((int)$u['id'])]);
    }

    function mlConectar() {
        AuthMiddleware::exigir(['vendedor']);
        if (!MercadoLivreClient::configurado()) { flash('warning', 'Integração não configurada: defina ML_APP_ID, ML_CLIENT_SECRET e ML_REDIRECT_URI no .env.'); redirect('integracoes'); }
        header('Location: ' . MercadoLivreService::urlAutorizacao()); exit;
    }

    /**
     * Retorno do OAuth (redirect_uri). O cookie de sessão é SameSite=Strict e não acompanha navegações iniciadas
     * pelo site do Mercado Livre; esta página só repassa code/state para /concluir com uma navegação do próprio WooDROP.
     */
    function mlRetorno() {
        $dest = url('integracoes/mercadolivre/concluir') . '?' . http_build_query(['code' => (string)($_GET['code'] ?? ''), 'state' => (string)($_GET['state'] ?? ''), 'error' => (string)($_GET['error'] ?? '')]);
        header('Cache-Control: no-store');
        echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=' . e($dest) . '"><title>Conectando…</title></head>'
           . '<body><p>Conectando ao Mercado Livre… <a href="' . e($dest) . '">Continuar</a></p></body></html>';
    }

    function mlConcluir() {
        $u = AuthMiddleware::exigir(['vendedor']);
        if (($_GET['error'] ?? '') !== '' || ($_GET['code'] ?? '') === '') { unset($_SESSION['ml_oauth']); flash('warning', 'Conexão com o Mercado Livre cancelada.'); redirect('integracoes'); }
        $err = MercadoLivreService::concluirConexao((int)$u['id'], (string)$_GET['code'], (string)($_GET['state'] ?? ''));
        flash($err ? 'danger' : 'success', $err ?? 'Conta do Mercado Livre conectada!'); redirect('integracoes');
    }

    function mlDesconectar() {
        $u = AuthMiddleware::exigir(['vendedor']); csrf_check();
        MercadoLivreService::desconectar((int)$u['id']);
        flash('success', 'Conta desconectada. Os anúncios já publicados continuam no Mercado Livre até você pausá-los ou encerrá-los lá.'); redirect('integracoes');
    }

    /** Importa vendas pagas dos últimos 7 dias (útil quando as notificações não chegam, ex.: ambiente local). */
    function mlImportar() {
        $u = AuthMiddleware::exigir(['vendedor']); csrf_check();
        $integ = MercadoLivreService::integracao((int)$u['id']);
        if (!$integ) { flash('warning', 'Conecte sua conta do Mercado Livre primeiro.'); redirect('integracoes'); }
        try { $n = MercadoLivreService::importarRecentes($integ, 7); flash('success', $n ? "$n venda(s) importada(s) como pedido." : 'Nenhuma venda nova para importar.'); }
        catch (Throwable $e) { flash('danger', 'Falha ao importar vendas: ' . $e->getMessage()); }
        redirect('integracoes');
    }

    function mlPublicar($id) {
        $u = AuthMiddleware::exigir(['vendedor']); csrf_check();
        $cat = strtoupper(trim((string)($_POST['categoria_id'] ?? ''))) ?: null;
        $err = MercadoLivreService::publicar((int)$u['id'], (int)$id, $cat);
        flash($err ? 'danger' : 'success', $err ?? 'Anúncio publicado no Mercado Livre.'); redirect('integracoes');
    }

    function mlSincronizar($id) {
        $u = AuthMiddleware::exigir(['vendedor']); csrf_check();
        if (!MercadoLivreService::anuncioDoVendedor((int)$u['id'], (int)$id)) { http_response_code(404); exit('Anúncio não encontrado.'); }
        $err = MercadoLivreService::sincronizarAnuncio((int)$id);
        flash($err ? 'danger' : 'success', $err ?? 'Preço, estoque e status enviados ao Mercado Livre.'); redirect('integracoes');
    }

    /** Webhook do Mercado Livre (sem sessão/CSRF): responde 200 na hora e processa depois (fila). */
    function mlNotificacao() {
        $n = json_decode((string)file_get_contents('php://input'), true);
        $ok = is_array($n) && MercadoLivreService::enfileirar($n);
        http_response_code(200); header('Content-Type: application/json'); echo '{"ok":true}';
        if (!$ok) return;
        // Em PHP-FPM/LiteSpeed dá para encerrar a resposta e processar em seguida; nos demais casos o cron processa a fila.
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request(); elseif (function_exists('litespeed_finish_request')) litespeed_finish_request(); else return;
        MercadoLivreService::processarFila(10);
    }
}
