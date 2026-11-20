<?php
class ReportController {
    /** RF09: relatórios (admin: consolidado; fornecedor: plano Plus/Premium). */
    function index() {
        $u = AuthMiddleware::exigir(['fornecedor', 'admin'], false);
        if ($u['tipo'] === 'fornecedor') {
            AuthMiddleware::exigir(['fornecedor']);
            if (!SubscriptionService::recursoAvancado((int)$u['id'])) { flash('warning', 'Relatórios gerenciais fazem parte dos planos Plus e Premium.'); redirect('assinatura'); }
        }
        $w = $u['tipo'] === 'admin' ? 'p.status<>"cancelado"' : 'p.status<>"cancelado" AND p.fornecedor_id=?'; $pr = $u['tipo'] === 'admin' ? [] : [$u['id']];
        render('reports/index', ['title' => 'Relatórios',
            'mensal' => qa("SELECT DATE_FORMAT(p.criado_em,'%Y-%m') mes, SUM(p.preco_total) total, COUNT(*) qtd FROM pedidos p WHERE $w AND p.criado_em >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY mes ORDER BY mes", $pr),
            'top' => qa("SELECT pr.nome, SUM(p.quantidade) qtd, SUM(p.preco_total) total FROM pedidos p JOIN produtos pr ON pr.id=p.produto_id WHERE $w GROUP BY pr.id, pr.nome ORDER BY qtd DESC LIMIT 10", $pr),
            'vendedores' => qa("SELECT uv.nome, COUNT(*) pedidos, SUM(p.preco_total) total FROM pedidos p JOIN usuarios uv ON uv.id=p.vendedor_id WHERE $w GROUP BY uv.id, uv.nome ORDER BY total DESC LIMIT 10", $pr)]);
    }
}
