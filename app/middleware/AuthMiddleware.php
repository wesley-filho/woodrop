<?php
class AuthMiddleware {
    /** Exige login, tipo de usuário e (RN06) assinatura ativa — exceto administradores. */
    static function exigir(array $tipos = [], bool $assinatura = true): array {
        $u = user();
        if (!$u) { flash('warning', 'Faça login para continuar.'); redirect('login'); }
        $db = Usuario::achar((int)$u['id']);
        if (!$db || !$db['ativo']) { session_destroy(); header('Location: ' . url('login')); exit; }
        if ($tipos && !in_array($u['tipo'], $tipos, true)) { http_response_code(403); exit('Acesso negado.'); }
        if ($assinatura && $u['tipo'] !== 'admin' && !Assinatura::ativa((int)$u['id'])) {
            flash('warning', 'RN06: é necessário um plano de assinatura ativo para acessar a plataforma.');
            redirect('assinatura');
        }
        return $u;
    }
}
