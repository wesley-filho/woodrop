<?php
/**
 * Gera database/woodrop_completo.sql: um único script MySQL com a estrutura completa (schema.sql, já com as tabelas
 * do Mercado Livre), os dados iniciais (seed.sql) e os dados de demonstração (os mesmos de scripts/seed_users.php).
 * Serve para instalar o banco de uma vez pelo phpMyAdmin (Hostinger) ou pelo terminal, sem precisar rodar PHP no servidor.
 * Uso: php scripts/gerar_sql_completo.php   (rode de novo sempre que schema.sql ou seed.sql mudarem)
 */
if (PHP_SAPI !== 'cli') exit('CLI apenas.');
date_default_timezone_set('America/Sao_Paulo');
$raiz = dirname(__DIR__);

/** Remove as linhas que prendem o script a um banco chamado "woodrop" (na Hostinger o nome tem prefixo, ex.: u123_woodrop). */
$limpar = function (string $sql): string {
    $fora = '/^\s*(CREATE DATABASE|USE\s|SET NAMES|-- Usuários \(admin|--\s+php scripts\/seed_users\.php)/i';
    return implode("\n", array_filter(explode("\n", $sql), fn($l) => !preg_match($fora, $l))) . "\n";
};
$q = fn(?string $s) => $s === null ? 'NULL' : "'" . str_replace(["\\", "'"], ["\\\\", "''"], $s) . "'";
// bcrypt: password_verify() funciona em qualquer PHP (Argon2id depende de como o PHP do servidor foi compilado).
$hash = fn(string $senha) => password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

$usuarios = [
    ['@admin', 'Administrador WooDROP', 'admin@woodrop.com.br', 'Admin@123', 'admin', '00000000000', 'Alegre'],
    ['@forn', 'Café & Artesanato do Caparaó', 'fornecedor@teste.com', 'Teste@123', 'fornecedor', '12345678000199', 'Alegre'],
    ['@vend', 'Loja Online Teste', 'vendedor@teste.com', 'Teste@123', 'vendedor', '98765432100', 'Vitória'],
];
$produtos = [ // nome, categoria, sku, preço-base, preço sugerido, estoque, mínimo
    ['Café Especial Caparaó 500g', 'Alimentos', 'CAFE-500', '32.90', '45.90', 40, 5],
    ['Panela de Pedra Artesanal', 'Artesanato', 'PANELA-01', '89.00', '129.90', 6, 5],
    ['Geleia de Morango 300g', 'Alimentos', 'GELEIA-300', '14.50', '22.00', 25, 5],
];

$o = [];
$o[] = '-- ============================================================================================';
$o[] = '-- WooDROP — instalação COMPLETA do banco MySQL (estrutura + dados iniciais + demonstração)';
$o[] = '-- Gerado por scripts/gerar_sql_completo.php em ' . date('d/m/Y H:i') . '. Não edite à mão: edite schema.sql/seed.sql e gere de novo.';
$o[] = '--';
$o[] = '-- ATENÇÃO: este script APAGA e recria todas as tabelas do WooDROP no banco selecionado.';
$o[] = '--          Não use num banco com dados reais. Para atualizar um banco existente, use database/migrations/.';
$o[] = '--';
$o[] = '-- Como usar:';
$o[] = '--   phpMyAdmin (Hostinger): selecione o banco (ex.: u123456_woodrop) > Importar > escolha este arquivo.';
$o[] = '--   Terminal: mysql -u USUARIO -p NOME_DO_BANCO < database/woodrop_completo.sql';
$o[] = '--   Banco novo no computador: CREATE DATABASE woodrop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;';
$o[] = '-- Requisitos: MySQL 8.0+ ou MariaDB 10.5+, InnoDB, utf8mb4.';
$o[] = '-- ============================================================================================';
$o[] = 'SET NAMES utf8mb4;';
$o[] = '';
$o[] = '-- -------------------------------------------------------------------------- 1. Estrutura';
$o[] = trim($limpar(file_get_contents("$raiz/database/schema.sql")));
$o[] = '';
$o[] = '-- -------------------------------------------------------------------------- 2. Dados iniciais (planos e categorias)';
$o[] = trim($limpar(file_get_contents("$raiz/database/seed.sql")));
$o[] = '';
$o[] = '-- -------------------------------------------------------------------------- 3. Demonstração';
$o[] = '-- Usuários de teste (os mesmos do README). SENHAS PÚBLICAS: troque-as ou desative estas contas em produção.';
$o[] = '--   admin@woodrop.com.br / Admin@123   ·   fornecedor@teste.com / Teste@123   ·   vendedor@teste.com / Teste@123';
foreach ($usuarios as [$var, $nome, $email, $senha, $tipo, $doc, $cidade]) {
    $o[] = 'INSERT INTO usuarios (nome,email,senha_hash,tipo,cpf_cnpj,telefone,endereco,cidade,estado,cep) VALUES ('
         . implode(',', [$q($nome), $q($email), $q($hash($senha)), $q($tipo), $q($doc), $q('(28) 99999-0000'), $q('Rua Principal, 100'), $q($cidade), $q('ES'), $q('29500-000')]) . ');';
    $o[] = "SET $var = LAST_INSERT_ID();";
}
$o[] = '';
$o[] = '-- Fornecedor e vendedor com plano Premium por 12 meses a partir da importação (pagamento simulado).';
foreach (['@forn', '@vend'] as $var) {
    $o[] = "INSERT INTO assinaturas (usuario_id,plano_id,inicio,fim) SELECT $var, id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH) FROM planos_assinatura WHERE nome='Premium';";
    $o[] = "INSERT INTO pagamentos (assinatura_id,valor,status,metodo) SELECT LAST_INSERT_ID(), ROUND(preco_base * (1 - desconto_progressivo / 100), 2), 'aprovado', 'pix' FROM planos_assinatura WHERE nome='Premium';";
}
$o[] = '';
$o[] = '-- Vínculo aprovado entre o fornecedor e o vendedor de teste (RN01).';
$o[] = "INSERT INTO vinculos (fornecedor_id,vendedor_id,status) VALUES (@forn,@vend,'aprovado');";
$o[] = '';
$o[] = '-- Produtos do fornecedor de teste.';
foreach ($produtos as [$nome, $cat, $sku, $pb, $ps, $est, $min]) {
    $o[] = 'INSERT INTO produtos (fornecedor_id,categoria_id,nome,descricao,sku,preco_base,preco_sugerido,estoque_atual,estoque_minimo,peso_kg,ativo,verificado_em) SELECT @forn, id, '
         . implode(', ', [$q($nome), $q("$nome — produto local."), $q($sku), $pb, $ps, $est, $min, '0.500', '1', 'NOW()']) . " FROM categorias WHERE nome={$q($cat)};";
}
$o[] = '';
$o[] = '-- Fim.';

$destino = "$raiz/database/woodrop_completo.sql";
file_put_contents($destino, implode("\n", $o) . "\n");
echo "Gerado: database/woodrop_completo.sql (" . number_format(filesize($destino) / 1024, 1, ',', '.') . " KB)\n";
