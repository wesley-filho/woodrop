<?php
class SubscriptionController {
    function planos() {
        $u = AuthMiddleware::exigir(['fornecedor', 'vendedor', 'admin'], false);
        render('subscriptions/plans', ['title' => 'Assinatura', 'planos' => PlanoAssinatura::todos(), 'ativa' => Assinatura::ativa((int)$u['id']), 'historico' => Assinatura::historico((int)$u['id'])]);
    }
    function contratar() {
        $u = AuthMiddleware::exigir(['fornecedor', 'vendedor'], false); csrf_check();
        $m = in_array($_POST['metodo'] ?? '', ['pix', 'cartao', 'boleto'], true) ? $_POST['metodo'] : 'pix';
        $e = SubscriptionService::contratar((int)$u['id'], (int)($_POST['plano_id'] ?? 0), $m);
        flash($e ? 'danger' : 'success', $e ?? 'Assinatura ativada (pagamento simulado).'); redirect($e ? 'assinatura' : 'dashboard');
    }
}
