<?php
/** Funções utilitárias: segurança (XSS, CSRF, rate limit), logs, views e respostas JSON. */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); } // XSS
/**
 * Prefixo das URLs. Em XAMPP é "/woodrop/public"; na Hostinger o .htaccess da raiz encaminha "/login" para
 * public/index.php sem "/public" no endereço — nesse caso o prefixo é vazio e os links ficam limpos.
 */
function base_path(): string {
    static $b = null;
    if ($b === null) {
        $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $uri = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        if ($b !== '' && $uri !== $b && !str_starts_with($uri, $b . '/')) $b = '';
    }
    return $b;
}
function url(string $p = ''): string { return base_path() . '/' . ltrim($p, '/'); }
function redirect(string $p): never { header('Location: ' . url($p)); exit; }
function flash(?string $t = null, ?string $m = null) {
    if ($t !== null) { $_SESSION['flash'][] = ['t' => $t, 'm' => $m]; return null; }
    $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f;
}
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$t)) { http_response_code(419); exit('Token CSRF inválido. Recarregue a página.'); }
}
function user(): ?array { return $_SESSION['user'] ?? null; }
function money($v): string { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
/** Valor para campos de formulário no padrão brasileiro (32,90). Vazio continua vazio. */
function decimal_br($v): string { return ($v === null || $v === '') ? '' : number_format((float)$v, 2, ',', ''); }
function hash_senha(string $s): string { return password_hash($s, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT); }
function view(string $name, array $data = []): void { extract($data, EXTR_SKIP); $user = user(); require ROOT . "/app/views/$name.php"; }
function render(string $name, array $data = []): void { view('layouts/header', $data); view($name, $data); view('layouts/footer', $data); }
function ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }

/** Cifra segredos guardados no banco (tokens OAuth de marketplaces) com libsodium. Chave: APP_KEY do .env. */
function chave_app(): string { return sodium_crypto_generichash('woodrop|' . (string)(env('APP_KEY') ?: env('ML_CLIENT_SECRET', '')), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES); }
function cifrar(string $s): string { $n = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES); return base64_encode($n . sodium_crypto_secretbox($s, $n, chave_app())); }
function decifrar(string $c): ?string {
    $b = base64_decode($c, true); $nl = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
    if ($b === false || strlen($b) <= $nl) return null;
    $r = sodium_crypto_secretbox_open(substr($b, $nl), substr($b, 0, $nl), chave_app());
    return $r === false ? null : $r;
}

/** Registro de ações críticas (login, estoque, pedidos). */
function log_action(string $acao, string $det = '', ?int $uid = null): void {
    try { q('INSERT INTO logs (usuario_id,acao,detalhes,ip) VALUES (?,?,?,?)', [$uid ?? (user()['id'] ?? null), $acao, $det, ip()]); } catch (Throwable $e) {}
}
/** Rate limiting simples em banco: máx $max eventos por $janela segundos para a chave. */
function rate_limit(string $chave, int $max, int $janela): bool {
    q('DELETE FROM rate_limits WHERE criado_em < (NOW() - INTERVAL 1 DAY)');
    $c = (int)q1('SELECT COUNT(*) c FROM rate_limits WHERE chave=? AND criado_em > (NOW() - INTERVAL ? SECOND)', [$chave, $janela])['c'];
    if ($c >= $max) return false;
    q('INSERT INTO rate_limits (chave) VALUES (?)', [$chave]);
    return true;
}
function json_out(bool $ok, $data = null, string $msg = '', int $code = 200): never {
    http_response_code($code); header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $ok, 'data' => $data ?? new stdClass, 'message' => $msg], JSON_UNESCAPED_UNICODE); exit;
}
/** Horas úteis (seg-sex) entre duas datas — usado na RN03. */
function horas_uteis(string $ini, string $fim): float {
    $t = strtotime($ini); $end = strtotime($fim); $h = 0.0;
    while ($t < $end) { $n = min($t + 3600, $end); if ((int)date('N', $t) < 6) $h += ($n - $t) / 3600; $t = $n; }
    return $h;
}
function paginar(string $sql, array $p, int $pp = 10): array {
    $pg = max(1, (int)($_GET['p'] ?? 1));
    $tot = (int)q1("SELECT COUNT(*) c FROM ($sql) t", $p)['c'];
    $pages = max(1, (int)ceil($tot / $pp)); $pg = min($pg, $pages);
    return [qa($sql . ' LIMIT ' . (int)$pp . ' OFFSET ' . (int)(($pg - 1) * $pp), $p), $pg, $pages];
}
function pager(int $pg, int $pages): string {
    if ($pages < 2) return ''; $q = $_GET; $h = '<nav aria-label="Paginação"><ul class="pagination pagination-sm">';
    for ($i = 1; $i <= $pages; $i++) { $q['p'] = $i; $h .= '<li class="page-item' . ($i == $pg ? ' active' : '') . '"><a class="page-link" href="?' . e(http_build_query($q)) . '">' . $i . '</a></li>'; }
    return $h . '</ul></nav>';
}
function badge_status(string $s): string {
    $m = ['recebido' => 'secondary', 'em_separacao' => 'info', 'enviado' => 'primary', 'entregue' => 'success', 'cancelado' => 'danger',
          'pendente' => 'warning', 'aprovado' => 'success', 'recusado' => 'danger'];
    return '<span class="badge text-bg-' . ($m[$s] ?? 'secondary') . '">' . e(str_replace('_', ' ', $s)) . '</span>';
}
