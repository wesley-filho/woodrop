<?php
// POST /orders | GET /orders | GET /orders/{id} | PATCH /orders/{id}/status
$u = ApiKeyMiddleware::autenticar();
$id = isset($parts[1]) ? (int)$parts[1] : null;
$fmt = fn(array $p) => ['id' => (int)$p['id'], 'produto_id' => (int)$p['produto_id'], 'quantidade' => (int)$p['quantidade'], 'preco_unitario' => (float)$p['preco_unitario'],
    'preco_total' => (float)$p['preco_total'], 'status' => $p['status'], 'codigo_rastreio' => $p['codigo_rastreio'], 'criado_em' => $p['criado_em'], 'atualizado_em' => $p['atualizado_em']];

if ($method === 'POST' && !$id) {                       // RF07: loja do vendedor -> WooDROP
    if ($u['tipo'] !== 'vendedor') json_out(false, null, 'Apenas vendedores enviam pedidos.', 403);
    $obr = ['produto_id', 'quantidade', 'cliente_nome', 'cliente_endereco', 'cliente_cidade', 'cliente_estado', 'cliente_cep'];
    foreach ($obr as $c) if (!isset($input[$c]) || trim((string)$input[$c]) === '') json_out(false, null, "Campo obrigatório: $c", 422);
    if (strlen($input['cliente_estado']) !== 2) json_out(false, null, 'cliente_estado deve ter 2 letras.', 422);
    [$pid, $err] = Pedido::criar($u, $input);
    if ($err) json_out(false, null, $err, 422);
    json_out(true, $fmt(Pedido::achar($pid, $u)), 'Pedido registrado e fornecedor notificado.', 201);
}
if ($method === 'GET' && !$id) { [$s, $p] = Pedido::sqlLista($u, null); json_out(true, array_map($fmt, qa($s . ' LIMIT 100', $p))); }
if ($id && $method === 'GET' && !isset($parts[2])) {
    $p = Pedido::achar($id, $u); if (!$p) json_out(false, null, 'Pedido não encontrado.', 404); json_out(true, $fmt($p));
}
if ($id && $method === 'PATCH' && ($parts[2] ?? '') === 'status') {  // RF08 (fornecedor)
    if ($u['tipo'] !== 'fornecedor') json_out(false, null, 'Apenas fornecedores atualizam o status.', 403);
    $r = StockService::atualizarStatus($id, (int)$u['id'], (string)($input['status'] ?? ''), $input['codigo_rastreio'] ?? null, !empty($input['confirmar_critico']));
    if ($r['segunda_etapa']) json_out(false, ['requires_second_step' => true], $r['msg'] . ' Reenvie com "confirmar_critico": true.', 409);
    if (!$r['ok']) json_out(false, null, $r['msg'], 422);
    json_out(true, $fmt(Pedido::achar($id, $u)), $r['msg']);
}
