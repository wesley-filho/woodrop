<?php
class LinkController {
    function index() {
        $u = AuthMiddleware::exigir(['fornecedor', 'vendedor']);
        if ($u['tipo'] === 'fornecedor') return render('links/index', ['title' => 'Vínculos', 'vinculos' => Vinculo::doFornecedor((int)$u['id'])]);
        render('links/index', ['title' => 'Fornecedores', 'fornecedores' => Vinculo::fornecedoresParaVendedor((int)$u['id'])]);
    }
    function solicitar() {
        $u = AuthMiddleware::exigir(['vendedor']); csrf_check();
        $e = Vinculo::solicitar((int)($_POST['fornecedor_id'] ?? 0), (int)$u['id']); flash($e ? 'warning' : 'success', $e ?? 'Solicitação enviada.'); redirect('vinculos');
    }
    /** RF04: fornecedor aprova/recusa. */
    function decidir($id) { $u = AuthMiddleware::exigir(['fornecedor']); csrf_check(); Vinculo::decidir((int)$id, (int)$u['id'], ($_POST['acao'] ?? '') === 'aprovar'); flash('success', 'Solicitação atualizada.'); redirect('vinculos'); }
}
