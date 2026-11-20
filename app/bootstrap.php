<?php
require __DIR__ . '/config/config.php';
require __DIR__ . '/config/database.php';
require __DIR__ . '/helpers.php';
spl_autoload_register(function ($c) {
    foreach (['models', 'services', 'middleware', 'controllers'] as $d) {
        $f = ROOT . "/app/$d/$c.php"; if (is_file($f)) { require $f; return; }
    }
});
/** Sessão segura: HttpOnly, SameSite=Strict e Secure em produção/HTTPS. */
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => APP_ENV === 'production' || !empty($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Strict']);
        session_start();
    }
}
