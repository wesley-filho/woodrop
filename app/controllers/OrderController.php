<?php
class OrderController {
    function lista() {
        $u = AuthMiddleware::exigir(['fornecedor', 'vendedor', 'admin'], false);
        $st = in_array($_GET['status'] ?? '', array_keys(StockService::TRANSICOES), true) ? $_GET['status'] : null;
        [$sql, $p] = Pedido::sqlLista($u, $st); [$rows, $pg, $pages] = paginar($sql, $p, 10);
        render('orders/list', ['title' => 'Pedidos', 'pedidos' => $rows, 'pg' => $pg, 'pages' => $pages, 'filtro' => $st]);
    }
    function detalhe($id) {
        $u = AuthMiddleware::exigir(['fornecedor', 'vendedor', 'admin'], false);
        $p = Pedido::achar((int)$id, $u); if (!$p) { http_response_code(404); exit('Pedido não encontrado.'); }
        render('orders/detail', ['title' => 'Pedido #' . $p['id'], 'p' => $p, 'proximos' => StockService::TRANSICOES[$p['status']]]);
    }
    /** RF08: fornecedor atualiza o status. */
    function status($id) {
        $u = AuthMiddleware::exigir(['fornecedor']); csrf_check();
        $r = StockService::atualizarStatus((int)$id, (int)$u['id'], $_POST['status'] ?? '', trim($_POST['codigo_rastreio'] ?? ''), !empty($_POST['segunda_etapa']));
        flash($r['ok'] ? 'success' : 'warning', $r['msg']);
        if ($r['segunda_etapa']) $_SESSION['pedir_2etapa'] = (int)$id;
        redirect("pedidos/$id");
    }
}
