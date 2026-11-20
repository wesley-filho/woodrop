<?php
class AuthController {
    function loginForm() { render('auth/login', ['title' => 'Entrar']); }
    /** RF02: login com e-mail+senha, rate limit, sessão regenerada. */
    function login() {
        csrf_check();
        if (!rate_limit('login:' . ip(), 5, 300)) { flash('danger', 'Muitas tentativas. Aguarde 5 minutos.'); redirect('login'); }
        $u = Usuario::porEmail(trim($_POST['email'] ?? ''));
        if (!$u || !$u['ativo'] || !password_verify($_POST['senha'] ?? '', $u['senha_hash'])) {
            log_action('login_falha', $_POST['email'] ?? '', null); flash('danger', 'E-mail ou senha inválidos.'); redirect('login');
        }
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id'], 'nome' => $u['nome'], 'tipo' => $u['tipo']];
        log_action('login', '', (int)$u['id']);
        redirect('dashboard');
    }
    function registerForm() { render('auth/register', ['title' => 'Cadastro']); }
    /** RF01: cadastro com validação de e-mail único. */
    function register() {
        csrf_check();
        $d = array_map('trim', array_intersect_key($_POST, array_flip(['nome', 'email', 'tipo', 'cpf_cnpj', 'telefone', 'endereco', 'cidade', 'estado', 'cep'])));
        $d['cpf_cnpj'] = preg_replace('/\D/', '', $d['cpf_cnpj'] ?? '');
        $senha = $_POST['senha'] ?? ''; $err = [];
        if (mb_strlen($d['nome'] ?? '') < 3) $err[] = 'Informe o nome.';
        if (!filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL)) $err[] = 'E-mail inválido.';
        if (!in_array($d['tipo'] ?? '', ['fornecedor', 'vendedor'], true)) $err[] = 'Selecione o tipo de conta.';
        if (!in_array(strlen($d['cpf_cnpj']), [11, 14], true)) $err[] = 'CPF/CNPJ inválido.';
        if (strlen($senha) < 8) $err[] = 'A senha deve ter ao menos 8 caracteres.';
        if (!$err && Usuario::porEmail($d['email'])) $err[] = 'Este e-mail já está cadastrado.';
        if ($err) { flash('danger', implode(' ', $err)); redirect('register'); }
        $d['senha_hash'] = hash_senha($senha);
        $d['estado'] = strtoupper(substr($d['estado'] ?? '', 0, 2));
        $id = Usuario::criar($d);
        log_action('usuario_criado', $d['tipo'], $id);
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $id, 'nome' => $d['nome'], 'tipo' => $d['tipo']];
        flash('success', 'Conta criada! Escolha um plano para começar.');
        redirect('assinatura');
    }
    function logout() { log_action('logout'); $_SESSION = []; session_destroy(); header('Location: ' . url('')); exit; }
}
