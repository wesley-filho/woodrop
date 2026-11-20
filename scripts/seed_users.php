<?php
/** Cria admin, fornecedor e vendedor de teste (senhas com Argon2id), assinaturas, produtos e vínculo.  Uso: php scripts/seed_users.php */
if (PHP_SAPI !== 'cli') exit('CLI apenas.');
require __DIR__ . '/../app/bootstrap.php';

$mk = function (string $nome, string $email, string $tipo, string $senha, string $doc, string $cidade) {
    if ($u = Usuario::porEmail($email)) return (int)$u['id'];
    return Usuario::criar(['nome' => $nome, 'email' => $email, 'senha_hash' => hash_senha($senha), 'tipo' => $tipo, 'cpf_cnpj' => $doc, 'telefone' => '(28) 99999-0000',
        'endereco' => 'Rua Principal, 100', 'cidade' => $cidade, 'estado' => 'ES', 'cep' => '29500-000']);
};
$admin = $mk('Administrador WooDROP', 'admin@woodrop.com.br', 'admin', 'Admin@123', '00000000000', 'Alegre');
$forn  = $mk('Café & Artesanato do Caparaó', 'fornecedor@teste.com', 'fornecedor', 'Teste@123', '12345678000199', 'Alegre');
$vend  = $mk('Loja Online Teste', 'vendedor@teste.com', 'vendedor', 'Teste@123', '98765432100', 'Vitória');

foreach ([$forn, $vend] as $id) if (!Assinatura::ativa($id)) SubscriptionService::contratar($id, 3, 'pix'); // Premium para testes

if (!q1('SELECT id FROM vinculos WHERE fornecedor_id=? AND vendedor_id=?', [$forn, $vend])) q('INSERT INTO vinculos (fornecedor_id,vendedor_id,status) VALUES (?,?,"aprovado")', [$forn, $vend]);
if (!q1('SELECT id FROM produtos WHERE fornecedor_id=?', [$forn])) {
    foreach ([['Café Especial Caparaó 500g', 1, 'CAFE-500', 32.90, 45.90, 40, 5], ['Panela de Pedra Artesanal', 2, 'PANELA-01', 89.00, 129.90, 6, 5], ['Geleia de Morango 300g', 1, 'GELEIA-300', 14.50, 22.00, 25, 5]] as [$n, $c, $sku, $pb, $ps, $est, $min])
        Produto::salvar($forn, ['categoria_id' => $c, 'nome' => $n, 'descricao' => $n . ' — produto local.', 'sku' => $sku, 'preco_base' => $pb, 'preco_sugerido' => $ps,
            'estoque_atual' => $est, 'estoque_minimo' => $min, 'peso_kg' => 0.5, 'imagem_url' => null, 'ativo' => 1]);
}
echo "OK.\n admin@woodrop.com.br / Admin@123  (ALTERE A SENHA)\n fornecedor@teste.com / Teste@123\n vendedor@teste.com / Teste@123\n";
