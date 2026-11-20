<?php
/** Front controller (roteador web). Requisições /api/* são delegadas ao roteador da API. */
require __DIR__ . '/../app/bootstrap.php';

$path = '/' . trim(rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/'), '/');
$base = base_path(); if ($base !== '' && str_starts_with($path, $base)) $path = '/' . trim(substr($path, strlen($base)), '/');
if ($path === '/index.php') $path = '/';

if ($path === '/api' || str_starts_with($path, '/api/')) { require ROOT . '/api/index.php'; exit; } // não captura /apikeys
// Webhook do Mercado Livre: chamada servidor-a-servidor, sem sessão.
if ($path === '/integracoes/mercadolivre/notificacoes' && $_SERVER['REQUEST_METHOD'] === 'POST') { (new IntegrationController)->mlNotificacao(); exit; }

start_session();
header('X-Frame-Options: DENY'); header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: same-origin');

$routes = [
    ['GET', '#^/$#', function () { render('landing', ['title' => 'WooDROP - Conexão entre empresas locais e o mercado on-line', 'planos' => PlanoAssinatura::todos()]); }],
    ['GET', '#^/login$#', 'AuthController@loginForm'], ['POST', '#^/login$#', 'AuthController@login'],
    ['GET', '#^/register$#', 'AuthController@registerForm'], ['POST', '#^/register$#', 'AuthController@register'],
    ['GET', '#^/logout$#', 'AuthController@logout'],
    ['GET', '#^/dashboard$#', 'UserController@dashboard'],
    ['GET', '#^/produtos$#', 'ProductController@lista'], ['GET', '#^/produtos/novo$#', 'ProductController@form'], ['POST', '#^/produtos/novo$#', 'ProductController@salvar'],
    ['GET', '#^/produtos/(\d+)/editar$#', 'ProductController@form'], ['POST', '#^/produtos/(\d+)/editar$#', 'ProductController@salvar'],
    ['POST', '#^/produtos/(\d+)/toggle$#', 'ProductController@toggle'], ['POST', '#^/produtos/(\d+)/excluir$#', 'ProductController@excluir'], ['POST', '#^/produtos/(\d+)/verificar$#', 'ProductController@verificar'],
    ['GET', '#^/catalogo$#', 'ProductController@catalogo'], ['GET', '#^/anuncios$#', 'ProductController@anuncios'], ['POST', '#^/anuncios$#', 'ProductController@anunciar'], ['POST', '#^/anuncios/(\d+)/toggle$#', 'ProductController@toggleAnuncio'],
    ['GET', '#^/vinculos$#', 'LinkController@index'], ['POST', '#^/vinculos/solicitar$#', 'LinkController@solicitar'], ['POST', '#^/vinculos/(\d+)/decidir$#', 'LinkController@decidir'],
    ['GET', '#^/pedidos$#', 'OrderController@lista'], ['GET', '#^/pedidos/(\d+)$#', 'OrderController@detalhe'], ['POST', '#^/pedidos/(\d+)/status$#', 'OrderController@status'],
    ['GET', '#^/relatorios$#', 'ReportController@index'],
    ['GET', '#^/assinatura$#', 'SubscriptionController@planos'], ['POST', '#^/assinatura/contratar$#', 'SubscriptionController@contratar'],
    ['GET', '#^/apikeys$#', 'UserController@apiKeys'], ['POST', '#^/apikeys/gerar$#', 'UserController@gerarKey'], ['POST', '#^/apikeys/(\d+)/revogar$#', 'UserController@revogarKey'],
    ['GET', '#^/admin/usuarios$#', 'UserController@adminUsuarios'], ['POST', '#^/admin/usuarios/(\d+)/toggle$#', 'UserController@adminToggleUsuario'], ['POST', '#^/admin/planos/(\d+)/toggle$#', 'UserController@adminTogglePlano'],
    ['GET', '#^/ajax/estoque$#', 'ProductController@estoqueAjax'],
    ['GET', '#^/notificacoes$#', 'NotificationController@lista'], ['POST', '#^/notificacoes/lidas$#', 'NotificationController@lidas'],
    ['GET', '#^/integracoes$#', 'IntegrationController@index'],
    ['GET', '#^/integracoes/mercadolivre/conectar$#', 'IntegrationController@mlConectar'], ['GET', '#^/integracoes/mercadolivre/retorno$#', 'IntegrationController@mlRetorno'],
    ['GET', '#^/integracoes/mercadolivre/concluir$#', 'IntegrationController@mlConcluir'], ['POST', '#^/integracoes/mercadolivre/desconectar$#', 'IntegrationController@mlDesconectar'],
    ['POST', '#^/integracoes/mercadolivre/importar$#', 'IntegrationController@mlImportar'],
    ['POST', '#^/integracoes/mercadolivre/anuncios/(\d+)/publicar$#', 'IntegrationController@mlPublicar'], ['POST', '#^/integracoes/mercadolivre/anuncios/(\d+)/sincronizar$#', 'IntegrationController@mlSincronizar'],
];
foreach ($routes as [$m, $re, $h]) {
    if ($m === $_SERVER['REQUEST_METHOD'] && preg_match($re, $path, $mt)) {
        array_shift($mt);
        if ($h instanceof Closure) { $h(...$mt); } else { [$c, $a] = explode('@', $h); (new $c)->$a(...$mt); }
        exit;
    }
}
http_response_code(404); render('auth/404', ['title' => 'Página não encontrada']);
