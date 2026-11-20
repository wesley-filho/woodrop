<?php
// GET /stock/{product_id} — estoque em tempo real
$u = ApiKeyMiddleware::autenticar();
if ($method !== 'GET' || !isset($parts[1])) json_out(false, null, 'Use GET /stock/{product_id}.', 405);
$p = Produto::achar((int)$parts[1]);
if (!$p || !Produto::visivel($u, $p)) json_out(false, null, 'Produto não encontrado.', 404);
json_out(true, ['product_id' => (int)$p['id'], 'estoque_atual' => (int)$p['estoque_atual'], 'estoque_minimo' => (int)$p['estoque_minimo'],
                'alerta_estoque_baixo' => StockService::critico($p), 'disponivel' => (int)$p['estoque_atual'] > 0 && (bool)$p['ativo']]);
