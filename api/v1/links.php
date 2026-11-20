<?php
// POST /links/request {fornecedor_id} | GET /links
$u = ApiKeyMiddleware::autenticar();
if ($method === 'POST' && ($parts[1] ?? '') === 'request') {
    if ($u['tipo'] !== 'vendedor') json_out(false, null, 'Apenas vendedores solicitam vínculo.', 403);
    $e = Vinculo::solicitar((int)($input['fornecedor_id'] ?? 0), (int)$u['id']);
    if ($e) json_out(false, null, $e, 422);
    json_out(true, null, 'Solicitação enviada ao fornecedor.', 201);
}
if ($method === 'GET' && !isset($parts[1])) {
    $rows = $u['tipo'] === 'fornecedor' ? Vinculo::doFornecedor((int)$u['id']) : Vinculo::doVendedor((int)$u['id']);
    json_out(true, array_map(fn($v) => ['id' => (int)$v['id'], 'fornecedor_id' => (int)$v['fornecedor_id'], 'vendedor_id' => (int)$v['vendedor_id'], 'status' => $v['status'], 'criado_em' => $v['criado_em']], $rows));
}
