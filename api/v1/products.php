<?php
// GET /products e GET /products/{id}
$u = ApiKeyMiddleware::autenticar();
if ($method !== 'GET') json_out(false, null, 'Método não permitido.', 405);
$campos = fn(array $p) => ['id' => (int)$p['id'], 'fornecedor_id' => (int)$p['fornecedor_id'], 'nome' => $p['nome'], 'descricao' => $p['descricao'], 'sku' => $p['sku'],
    'preco_base' => (float)$p['preco_base'], 'preco_sugerido' => $p['preco_sugerido'] !== null ? (float)$p['preco_sugerido'] : null,
    'estoque_atual' => (int)$p['estoque_atual'], 'imagem_url' => $p['imagem_url'] ? APP_URL . '/' . $p['imagem_url'] : null, 'ativo' => (bool)$p['ativo']];
if (isset($parts[1])) {
    $p = Produto::achar((int)$parts[1]);
    if (!$p || !Produto::visivel($u, $p)) json_out(false, null, 'Produto não encontrado.', 404);
    json_out(true, $campos($p));
}
if ($u['tipo'] === 'vendedor') $rows = Produto::catalogo((int)$u['id']);           // RN01: só vínculos aprovados
elseif ($u['tipo'] === 'fornecedor') $rows = Produto::doFornecedor((int)$u['id']);
else $rows = qa('SELECT * FROM produtos WHERE ativo=1');
json_out(true, array_map($campos, $rows));
