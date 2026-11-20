<?php
/**
 * Cria um usuário de teste do Mercado Livre (site MLB) usando a conta conectada por um vendedor do WooDROP.
 * Uso: php scripts/ml_usuario_teste.php vendedor@teste.com
 *
 * Fluxo sugerido: conecte primeiro a SUA conta real (só para criar os usuários de teste), rode este script duas vezes
 * (um vendedor e um comprador de teste), depois entre no Mercado Livre com o vendedor de teste e clique em "Reconectar".
 * Guarde as credenciais: o Mercado Livre não mostra de novo a senha de um usuário de teste.
 */
if (PHP_SAPI !== 'cli') exit('CLI apenas.');
require __DIR__ . '/../app/bootstrap.php';
$email = $argv[1] ?? '';
$u = $email ? Usuario::porEmail($email) : null;
if (!$u) { fwrite(STDERR, "Uso: php scripts/ml_usuario_teste.php EMAIL_DO_VENDEDOR_NO_WOODROP\n"); exit(1); }
$integ = MercadoLivreService::integracao((int)$u['id']);
if (!$integ) { fwrite(STDERR, "Este usuário ainda não conectou uma conta do Mercado Livre (menu Integrações).\n"); exit(1); }
try { $t = MercadoLivreService::criarUsuarioTeste($integ); }
catch (Throwable $e) { fwrite(STDERR, 'Falha: ' . $e->getMessage() . "\n"); exit(1); }
log_action('ml_usuario_teste', "id={$t['id']}", (int)$u['id']);
echo "Usuário de teste criado (guarde estes dados):\n";
echo "  ID:       {$t['id']}\n  Apelido:  " . ($t['nickname'] ?? '') . "\n  Senha:    " . ($t['password'] ?? '') . "\n";
echo "  Código de verificação por e-mail: os últimos 4 ou 6 dígitos do ID (ex.: " . substr((string)$t['id'], -6) . ").\n";
