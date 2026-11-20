<?php
class UserController {
    function dashboard() {
        $u = AuthMiddleware::exigir([], false); $id = (int)$u['id'];
        if ($u['tipo'] === 'admin') {
            $m = ['usuarios' => qa('SELECT tipo, COUNT(*) c FROM usuarios GROUP BY tipo'),
                  'pedidos' => (int)q1('SELECT COUNT(*) c FROM pedidos')['c'],
                  'assinaturas' => (int)q1('SELECT COUNT(*) c FROM assinaturas WHERE ativo=1 AND fim>=CURDATE()')['c'],
                  'receita' => (float)(q1('SELECT SUM(valor) s FROM pagamentos WHERE status="aprovado"')['s'] ?? 0)];
            return render('dashboard/admin', ['title' => 'Painel administrativo', 'm' => $m]);
        }
        AuthMiddleware::exigir([], true);
        if ($u['tipo'] === 'fornecedor') {
            $m = ['produtos' => (int)q1('SELECT COUNT(*) c FROM produtos WHERE fornecedor_id=? AND ativo=1', [$id])['c'],
                  'pendentes' => (int)q1('SELECT COUNT(*) c FROM pedidos WHERE fornecedor_id=? AND status="recebido"', [$id])['c'],
                  'vinculos' => (int)q1('SELECT COUNT(*) c FROM vinculos WHERE fornecedor_id=? AND status="pendente"', [$id])['c'],
                  'alertas' => qa('SELECT id,nome,estoque_atual,estoque_minimo FROM produtos WHERE fornecedor_id=? AND ativo=1 AND estoque_atual<=estoque_minimo', [$id]),
                  'verificar' => StockService::pendentesVerificacao($id)];
            return render('dashboard/fornecedor', ['title' => 'Painel do fornecedor', 'm' => $m]);
        }
        $m = ['anuncios' => (int)q1('SELECT COUNT(*) c FROM anuncios WHERE vendedor_id=? AND ativo=1', [$id])['c'],
              'pedidos' => (int)q1('SELECT COUNT(*) c FROM pedidos WHERE vendedor_id=?', [$id])['c'],
              'vinculos' => (int)q1('SELECT COUNT(*) c FROM vinculos WHERE vendedor_id=? AND status="aprovado"', [$id])['c'],
              'mercadolivre' => MercadoLivreService::integracao($id) !== null];
        render('dashboard/vendedor', ['title' => 'Painel do vendedor', 'm' => $m]);
    }
    // --- Administração
    function adminUsuarios() {
        AuthMiddleware::exigir(['admin'], false);
        render('admin/usuarios', ['title' => 'Usuários e planos', 'usuarios' => Usuario::todos(), 'planos' => PlanoAssinatura::todos(false)]);
    }
    function adminToggleUsuario($id) { AuthMiddleware::exigir(['admin'], false); csrf_check(); Usuario::alternar((int)$id); log_action('usuario_toggle', "id=$id"); redirect('admin/usuarios'); }
    function adminTogglePlano($id) { AuthMiddleware::exigir(['admin'], false); csrf_check(); PlanoAssinatura::alternar((int)$id); log_action('plano_toggle', "id=$id"); redirect('admin/usuarios'); }
    // --- API Keys (vendedor e fornecedor)
    function apiKeys() {
        $u = AuthMiddleware::exigir(['vendedor', 'fornecedor']);
        render('account/apikeys', ['title' => 'API Keys', 'keys' => ApiKey::doUsuario((int)$u['id']), 'nova' => $_SESSION['nova_key'] ?? null]);
        unset($_SESSION['nova_key']);
    }
    function gerarKey() { $u = AuthMiddleware::exigir(['vendedor', 'fornecedor']); csrf_check(); $_SESSION['nova_key'] = ApiKey::gerar((int)$u['id'], trim($_POST['descricao'] ?? 'Integração')); redirect('apikeys'); }
    function revogarKey($id) { $u = AuthMiddleware::exigir(['vendedor', 'fornecedor']); csrf_check(); ApiKey::revogar((int)$id, (int)$u['id']); flash('success', 'Chave revogada.'); redirect('apikeys'); }
}
