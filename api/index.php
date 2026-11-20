<?php
/** Roteador da API REST — /api/v1/*  (formato: { success, data, message }). Documentação: /api/docs */
$p = '/' . trim(rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');
$b = base_path(); if ($b !== '' && str_starts_with($p, $b)) $p = '/' . trim(substr($p, strlen($b)), '/');

// Documentação OpenAPI/Swagger (RNF03)
if ($p === '/api/docs' || $p === '/api/docs/') {
    $spec = e(url('api/docs/openapi.yaml'));
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>WooDROP API</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css"></head><body><div id="ui"></div>'
       . '<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script><script>SwaggerUIBundle({url:"' . $spec . '",dom_id:"#ui"});</script></body></html>';
    exit;
}
if ($p === '/api/docs/openapi.yaml') { header('Content-Type: application/yaml; charset=utf-8'); readfile(__DIR__ . '/docs/openapi.yaml'); exit; }

header('Content-Type: application/json; charset=utf-8'); header('X-Content-Type-Options: nosniff');
if (!str_starts_with($p, '/api/v1/')) json_out(false, null, 'Endpoint não encontrado. Use /api/v1/.', 404);

$parts = explode('/', trim(substr($p, strlen('/api/v1/')), '/'));
$method = $_SERVER['REQUEST_METHOD'];
$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '[]', true);
if (!is_array($input)) $input = $_POST ?: [];

try {
    switch ($parts[0]) {
        case 'auth':     require __DIR__ . '/v1/auth.php'; break;
        case 'products': require __DIR__ . '/v1/products.php'; break;
        case 'orders':   require __DIR__ . '/v1/orders.php'; break;
        case 'stock':    require __DIR__ . '/v1/stock.php'; break;
        case 'links':    require __DIR__ . '/v1/links.php'; break;
    }
    json_out(false, null, 'Endpoint ou método não encontrado.', 404);
} catch (Throwable $e) {
    error_log('[WooDROP API] ' . $e->getMessage());
    json_out(false, null, APP_ENV === 'production' ? 'Erro interno.' : $e->getMessage(), 500);
}
