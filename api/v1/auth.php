<?php
// POST /auth/login — público; devolve uma API Key nova (exibida uma única vez).
if ($method === 'POST' && ($parts[1] ?? '') === 'login') {
    if (!rate_limit('apilogin:' . ip(), 5, 300)) json_out(false, null, 'Muitas tentativas. Aguarde.', 429);
    $u = Usuario::porEmail(trim($input['email'] ?? ''));
    if (!$u || !$u['ativo'] || !password_verify($input['senha'] ?? '', $u['senha_hash'])) { log_action('api_login_falha', $input['email'] ?? ''); json_out(false, null, 'Credenciais inválidas.', 401); }
    if ($u['tipo'] === 'admin') json_out(false, null, 'Administradores não utilizam a API.', 403);
    q('UPDATE api_keys SET ativa=0 WHERE usuario_id=? AND descricao="login via API"', [$u['id']]);
    $key = ApiKey::gerar((int)$u['id'], 'login via API');
    log_action('api_login', '', (int)$u['id']);
    json_out(true, ['api_key' => $key, 'usuario' => ['id' => (int)$u['id'], 'nome' => $u['nome'], 'tipo' => $u['tipo']]], 'Autenticado. Guarde a chave: ela não será exibida novamente.');
}
