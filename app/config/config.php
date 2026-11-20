<?php
/** Configuração geral: .env e constantes do negócio. */
$GLOBALS['__env'] = [];
$envFile = __DIR__ . '/../../.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        $l = trim($l);
        if ($l === '' || $l[0] === '#' || strpos($l, '=') === false) continue;
        [$k, $v] = explode('=', $l, 2);
        $GLOBALS['__env'][trim($k)] = trim($v, " \t\"'");
    }
}
/** Variável de ambiente real (ex.: SetEnv no Apache, testes) tem prioridade sobre o .env. */
function env(string $k, $d = null) { $v = getenv($k); return ($v !== false && $v !== '') ? $v : ($GLOBALS['__env'][$k] ?? $d); }

define('APP_ENV', env('APP_ENV', 'development'));
define('APP_URL', rtrim(env('APP_URL', 'http://localhost/woodrop/public'), '/'));
define('ROOT', dirname(__DIR__, 2));
define('ESTOQUE_MIN_PADRAO', 5);        // RN08: limite crítico padrão
define('LIMITE_SKU_BASICO', 100);       // 6.1.2: Básico até 100 SKUs
define('PRAZO_CONFIRMACAO_H', 24);      // RN03: 24 horas úteis
define('DIAS_VERIFICACAO_ESTOQUE', 30); // RN07: periodicidade do questionário de estoque
date_default_timezone_set('America/Sao_Paulo');
