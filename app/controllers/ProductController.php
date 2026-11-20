<?php
class ProductController {
    function lista() {
        $u = AuthMiddleware::exigir(['fornecedor']);
        [$sql, $p] = Produto::sqlDoFornecedor((int)$u['id']); [$rows, $pg, $pages] = paginar($sql, $p, 10);
        render('products/list', ['title' => 'Meus produtos', 'produtos' => $rows, 'pg' => $pg, 'pages' => $pages]);
    }
    function form($id = null) {
        $u = AuthMiddleware::exigir(['fornecedor']); $prod = null;
        if ($id) { $prod = Produto::achar((int)$id); if (!$prod || (int)$prod['fornecedor_id'] !== (int)$u['id']) { http_response_code(404); exit('Produto não encontrado.'); } }
        render('products/form', ['title' => $id ? 'Editar produto' : 'Novo produto', 'p' => $prod, 'categorias' => Categoria::todas()]);
    }
    /** RF03: cria/edita produto, valida dados e faz upload seguro da imagem. */
    function salvar($id = null) {
        $u = AuthMiddleware::exigir(['fornecedor']); csrf_check(); $fid = (int)$u['id'];
        $atual = $id ? Produto::achar((int)$id) : null;
        if ($id && (!$atual || (int)$atual['fornecedor_id'] !== $fid)) { http_response_code(404); exit('Produto não encontrado.'); }
        if (!$id && !SubscriptionService::podeCriarSku($fid)) { flash('warning', 'Plano Básico limitado a ' . LIMITE_SKU_BASICO . ' SKUs. Faça upgrade.'); redirect('produtos'); }
        $d = ['categoria_id' => (int)($_POST['categoria_id'] ?? 0), 'nome' => trim($_POST['nome'] ?? ''), 'descricao' => trim($_POST['descricao'] ?? ''),
              'sku' => trim($_POST['sku'] ?? '') ?: 'WD-' . strtoupper(bin2hex(random_bytes(4))),
              'preco_base' => (float)str_replace(',', '.', $_POST['preco_base'] ?? 0), 'preco_sugerido' => (float)str_replace(',', '.', $_POST['preco_sugerido'] ?? 0),
              'estoque_atual' => max(0, (int)($_POST['estoque_atual'] ?? 0)), 'estoque_minimo' => max(0, (int)($_POST['estoque_minimo'] ?? ESTOQUE_MIN_PADRAO)),
              'peso_kg' => (float)str_replace(',', '.', $_POST['peso_kg'] ?? 0), 'ativo' => isset($_POST['ativo']) ? 1 : 0,
              'imagem_url' => $atual['imagem_url'] ?? null];
        $err = [];
        if (mb_strlen($d['nome']) < 2) $err[] = 'Informe o nome.';
        if ($d['preco_base'] <= 0) $err[] = 'Preço-base deve ser maior que zero.';
        $dup = q1('SELECT id FROM produtos WHERE sku=? AND id<>?', [$d['sku'], (int)$id]);
        if ($dup) $err[] = 'SKU já utilizado.';
        if (!empty($_FILES['imagem']['name'])) { $img = $this->upload($_FILES['imagem']); if (is_array($img)) $err[] = $img[0]; else $d['imagem_url'] = $img; }
        if ($err) { flash('danger', implode(' ', $err)); redirect($id ? "produtos/$id/editar" : 'produtos/novo'); }
        $antes = $atual['estoque_atual'] ?? null;
        $pid = Produto::salvar($fid, $d, $id ? (int)$id : null);
        if ($antes !== null && (int)$antes !== $d['estoque_atual']) log_action('estoque_alterado', "produto=$pid de=$antes para={$d['estoque_atual']}", $fid);
        flash('success', 'Produto salvo.'); redirect('produtos');
    }
    /** Upload: valida tamanho (2 MB), MIME real e extensão; nome aleatório. Retorna caminho ou [erro]. */
    private function upload(array $f) {
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) return ['Imagem inválida (máx. 2 MB).'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
        if (!$ext) return ['Formato de imagem não permitido (use JPG, PNG ou WEBP).'];
        $nome = bin2hex(random_bytes(12)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], ROOT . '/public/uploads/' . $nome)) return ['Falha ao salvar imagem.'];
        return 'uploads/' . $nome;
    }
    function toggle($id) { $u = AuthMiddleware::exigir(['fornecedor']); csrf_check(); Produto::alternar((int)$id, (int)$u['id']); redirect('produtos'); }
    function excluir($id) {
        $u = AuthMiddleware::exigir(['fornecedor']); csrf_check();
        flash('success', Produto::excluir((int)$id, (int)$u['id']) === 'excluido' ? 'Produto excluído.' : 'Produto possui pedidos: foi apenas desativado.'); redirect('produtos');
    }
    /** RN07: resposta ao questionário de verificação de estoque. */
    function verificar($id) {
        $u = AuthMiddleware::exigir(['fornecedor']); csrf_check();
        StockService::verificar((int)$id, (int)$u['id'], (int)($_POST['quantidade'] ?? 0)); flash('success', 'Estoque confirmado. Obrigado!'); redirect('dashboard');
    }
    // --- Vendedor
    function catalogo() { $u = AuthMiddleware::exigir(['vendedor']); render('products/catalog', ['title' => 'Catálogo de fornecedores', 'produtos' => Produto::catalogo((int)$u['id'])]); }
    /** Estoque em tempo (quase) real do catálogo — consumido por assets/js/stock.js. */
    function estoqueAjax() { $u = AuthMiddleware::exigir(['vendedor']); $o = []; foreach (Produto::catalogo((int)$u['id']) as $p) $o[$p['id']] = (int)$p['estoque_atual']; json_out(true, $o); }
    function anuncios() { $u = AuthMiddleware::exigir(['vendedor']); render('products/anuncios', ['title' => 'Meus anúncios', 'anuncios' => Anuncio::doVendedor((int)$u['id'])]); }
    /** RF05: anunciar produto (RN01 e RN04 validadas em Anuncio::salvar). */
    function anunciar() {
        $u = AuthMiddleware::exigir(['vendedor']); csrf_check();
        $err = Anuncio::salvar((int)$u['id'], (int)($_POST['produto_id'] ?? 0), (float)str_replace(',', '.', $_POST['preco_venda'] ?? 0));
        flash($err ? 'danger' : 'success', $err ?? 'Anúncio salvo.'); redirect($_POST['voltar'] ?? 'catalogo');
    }
    function toggleAnuncio($id) { $u = AuthMiddleware::exigir(['vendedor']); csrf_check(); Anuncio::alternar((int)$id, (int)$u['id']); redirect('anuncios'); }
}
