-- ============================================================================================
-- WooDROP — instalação COMPLETA do banco MySQL (estrutura + dados iniciais + demonstração)
-- Gerado por scripts/gerar_sql_completo.php em 18/11/2026 13:50. Não edite à mão: edite schema.sql/seed.sql e gere de novo.
--
-- ATENÇÃO: este script APAGA e recria todas as tabelas do WooDROP no banco selecionado.
--          Não use num banco com dados reais. Para atualizar um banco existente, use database/migrations/.
--
-- Como usar:
--   phpMyAdmin (Hostinger): selecione o banco (ex.: u123456_woodrop) > Importar > escolha este arquivo.
--   Terminal: mysql -u USUARIO -p NOME_DO_BANCO < database/woodrop_completo.sql
--   Banco novo no computador: CREATE DATABASE woodrop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Requisitos: MySQL 8.0+ ou MariaDB 10.5+, InnoDB, utf8mb4.
-- ============================================================================================
SET NAMES utf8mb4;

-- -------------------------------------------------------------------------- 1. Estrutura
-- WooDROP - Modelo lógico (Figura 3 do TCC) + tabelas de apoio (pagamentos, logs, rate_limits).
-- Engine InnoDB (ACID) para garantir integridade em pedidos e estoques.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS marketplace_notificacoes, anuncios_externos, integracoes, rate_limits, logs, pagamentos, notificacoes, api_keys, assinaturas, planos_assinatura, vinculos, pedidos, anuncios, produtos, categorias, usuarios;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  tipo ENUM('fornecedor','vendedor','admin') NOT NULL,
  cpf_cnpj VARCHAR(20),
  telefone VARCHAR(20),
  endereco VARCHAR(255),
  cidade VARCHAR(100),
  estado VARCHAR(2),
  cep VARCHAR(10),
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  penalidades INT NOT NULL DEFAULT 0,               -- RN03: contador de penalidades
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_usuarios_tipo (tipo)
) ENGINE=InnoDB;

CREATE TABLE categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  descricao VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fornecedor_id INT NOT NULL,
  categoria_id INT,
  nome VARCHAR(200) NOT NULL,
  descricao TEXT,
  sku VARCHAR(60) NOT NULL UNIQUE,
  preco_base DECIMAL(10,2) NOT NULL,
  preco_sugerido DECIMAL(10,2),
  estoque_atual INT NOT NULL DEFAULT 0,
  estoque_minimo INT NOT NULL DEFAULT 5,
  peso_kg DECIMAL(8,3),
  imagem_url VARCHAR(500),
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  verificado_em DATETIME NULL,                      -- RN07: última confirmação de estoque
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_prod_forn FOREIGN KEY (fornecedor_id) REFERENCES usuarios(id),
  CONSTRAINT fk_prod_cat FOREIGN KEY (categoria_id) REFERENCES categorias(id),
  INDEX idx_prod_forn (fornecedor_id), INDEX idx_prod_cat (categoria_id)
) ENGINE=InnoDB;

CREATE TABLE anuncios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendedor_id INT NOT NULL,
  produto_id INT NOT NULL,
  preco_venda DECIMAL(10,2) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_anuncio (vendedor_id, produto_id),
  CONSTRAINT fk_an_vend FOREIGN KEY (vendedor_id) REFERENCES usuarios(id),
  CONSTRAINT fk_an_prod FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;

CREATE TABLE pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendedor_id INT NOT NULL,
  fornecedor_id INT NOT NULL,
  produto_id INT NOT NULL,
  quantidade INT NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL,
  preco_total DECIMAL(10,2) NOT NULL,
  status ENUM('recebido','em_separacao','enviado','entregue','cancelado') NOT NULL DEFAULT 'recebido',
  cliente_nome VARCHAR(150) NOT NULL,
  cliente_endereco VARCHAR(255) NOT NULL,
  cliente_cidade VARCHAR(100) NOT NULL,
  cliente_estado VARCHAR(2) NOT NULL,
  cliente_cep VARCHAR(10) NOT NULL,
  cliente_telefone VARCHAR(20),
  codigo_rastreio VARCHAR(60),
  observacoes TEXT,
  confirmado_em DATETIME NULL,                      -- RN03/RN05: momento da confirmação pelo fornecedor
  penalizado TINYINT(1) NOT NULL DEFAULT 0,         -- RN03: penalidade já aplicada
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ped_vend FOREIGN KEY (vendedor_id) REFERENCES usuarios(id),
  CONSTRAINT fk_ped_forn FOREIGN KEY (fornecedor_id) REFERENCES usuarios(id),
  CONSTRAINT fk_ped_prod FOREIGN KEY (produto_id) REFERENCES produtos(id),
  INDEX idx_ped_status (status), INDEX idx_ped_forn (fornecedor_id), INDEX idx_ped_vend (vendedor_id)
) ENGINE=InnoDB;

CREATE TABLE vinculos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fornecedor_id INT NOT NULL,
  vendedor_id INT NOT NULL,
  status ENUM('pendente','aprovado','recusado') NOT NULL DEFAULT 'pendente',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_vinculo (fornecedor_id, vendedor_id),
  CONSTRAINT fk_vin_forn FOREIGN KEY (fornecedor_id) REFERENCES usuarios(id),
  CONSTRAINT fk_vin_vend FOREIGN KEY (vendedor_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE planos_assinatura (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) NOT NULL,
  periodo ENUM('mensal','semestral','anual') NOT NULL,
  preco_base DECIMAL(10,2) NOT NULL,                -- valor do período SEM desconto
  desconto_progressivo DECIMAL(5,2) NOT NULL DEFAULT 0,
  descricao TEXT,
  ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE assinaturas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  plano_id INT NOT NULL,
  inicio DATE NOT NULL,
  fim DATE NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ass_usu FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  CONSTRAINT fk_ass_pl FOREIGN KEY (plano_id) REFERENCES planos_assinatura(id),
  INDEX idx_ass_usu (usuario_id, ativo)
) ENGINE=InnoDB;

CREATE TABLE pagamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assinatura_id INT NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  status VARCHAR(20) NOT NULL,
  metodo VARCHAR(30),
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pag_ass FOREIGN KEY (assinatura_id) REFERENCES assinaturas(id)
) ENGINE=InnoDB;

CREATE TABLE api_keys (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  chave VARCHAR(64) NOT NULL UNIQUE,                -- SHA-256 (hex) da chave; a chave em claro é exibida uma única vez
  descricao VARCHAR(100),
  ativa TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_uso DATETIME NULL,
  CONSTRAINT fk_key_usu FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE notificacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  mensagem TEXT,
  lida TINYINT(1) NOT NULL DEFAULT 0,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_not_usu FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  INDEX idx_not_usu (usuario_id, lida)
) ENGINE=InnoDB;

-- Apoio: auditoria de ações críticas e rate limiting
CREATE TABLE logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  acao VARCHAR(60) NOT NULL,
  detalhes TEXT,
  ip VARCHAR(45),
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_logs_acao (acao)
) ENGINE=InnoDB;

CREATE TABLE rate_limits (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  chave VARCHAR(100) NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rl (chave, criado_em)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Integração com marketplaces (igual a database/migrations/001_mercadolivre.sql)
-- ---------------------------------------------------------------------------
-- Conta do vendedor no marketplace (OAuth 2.0). Tokens guardados cifrados (libsodium) — ver cifrar()/decifrar() em app/helpers.php.
CREATE TABLE IF NOT EXISTS integracoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  canal VARCHAR(20) NOT NULL,                        -- 'mercadolivre'
  conta_id VARCHAR(40) NOT NULL,                     -- user_id do vendedor no marketplace
  conta_nome VARCHAR(100),                           -- nickname
  conta_teste TINYINT(1) NOT NULL DEFAULT 0,         -- usuário de teste do Mercado Livre (tag test_user)
  user_products TINYINT(1) NOT NULL DEFAULT 0,       -- vendedor no modelo User Products (publica com family_name)
  access_token TEXT NOT NULL,
  refresh_token TEXT NOT NULL,
  expira_em DATETIME NOT NULL,
  status ENUM('ativa','reconectar') NOT NULL DEFAULT 'ativa',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_integ_usuario (usuario_id, canal),
  UNIQUE KEY uq_integ_conta (canal, conta_id),
  CONSTRAINT fk_integ_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Anúncio do WooDROP publicado no marketplace (1 anúncio -> 1 item por canal).
CREATE TABLE IF NOT EXISTS anuncios_externos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  anuncio_id INT NOT NULL,
  canal VARCHAR(20) NOT NULL,
  item_id VARCHAR(40) NULL,                          -- ex.: MLB1234567890 (NULL enquanto a publicação falha)
  categoria_id VARCHAR(20),
  status VARCHAR(20),                                -- active | paused | closed | under_review | erro
  permalink VARCHAR(500),
  erro TEXT,                                         -- última mensagem de erro do marketplace
  sincronizado_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ext_anuncio (anuncio_id, canal),
  UNIQUE KEY uq_ext_item (canal, item_id),
  CONSTRAINT fk_ext_anuncio FOREIGN KEY (anuncio_id) REFERENCES anuncios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Fila de notificações (webhooks). O Mercado Livre exige HTTP 200 em até 500 ms: guardamos e processamos depois.
CREATE TABLE IF NOT EXISTS marketplace_notificacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  canal VARCHAR(20) NOT NULL,
  topico VARCHAR(40) NOT NULL,
  recurso VARCHAR(200) NOT NULL,
  conta_id VARCHAR(40) NOT NULL,
  tentativas INT NOT NULL DEFAULT 0,
  erro TEXT,
  recebido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processado_em DATETIME NULL,
  INDEX idx_mknot_pend (processado_em, id)
) ENGINE=InnoDB;

-- Origem do pedido: NULL = API/painel do WooDROP; 'mercadolivre' = venda importada. Chave única evita importar duas vezes.
ALTER TABLE pedidos
  ADD COLUMN canal VARCHAR(20) NULL AFTER vendedor_id,
  ADD COLUMN pedido_externo_id VARCHAR(40) NULL AFTER canal,
  ADD UNIQUE KEY uq_ped_externo (canal, pedido_externo_id, produto_id);

-- -------------------------------------------------------------------------- 2. Dados iniciais (planos e categorias)
-- Tabela 4: planos. preco_base = valor do período sem desconto; final = preco_base * (1 - desconto/100)
-- Básico 49,90 | Plus 6 x 49,90 = 299,40 -10% = 269,46 | Premium 12 x 49,90 = 598,80 -20% = 479,04
INSERT INTO planos_assinatura (nome, periodo, preco_base, desconto_progressivo, descricao) VALUES
('Básico','mensal',49.90,0,'Até 100 SKUs, gestão de catálogo e estoque, suporte por e-mail.'),
('Plus','semestral',299.40,10,'Tudo do Básico + SKUs ilimitados, relatórios gerenciais e suporte prioritário.'),
('Premium','anual',598.80,20,'Tudo do Plus + API dedicada, consultoria em dropshipping e isenção de taxas no 1º mês.');

INSERT INTO categorias (nome, descricao) VALUES
('Alimentos','Produtos alimentícios e gastronomia regional'),
('Artesanato','Peças artesanais do Caparaó'),
('Vestuário','Roupas e acessórios'),
('Casa & Decoração','Itens para casa'),
('Turismo','Lembranças e produtos turísticos'),
('Outros','Demais categorias');

-- -------------------------------------------------------------------------- 3. Demonstração
-- Usuários de teste (os mesmos do README). SENHAS PÚBLICAS: troque-as ou desative estas contas em produção.
--   admin@woodrop.com.br / Admin@123   ·   fornecedor@teste.com / Teste@123   ·   vendedor@teste.com / Teste@123
INSERT INTO usuarios (nome,email,senha_hash,tipo,cpf_cnpj,telefone,endereco,cidade,estado,cep) VALUES ('Administrador WooDROP','admin@woodrop.com.br','$2y$12$8nTjy8T0Kfule9cXY63A8.Bxz2Qo1dcSnm8slRiu5qS3c9qUl3jOK','admin','00000000000','(28) 99999-0000','Rua Principal, 100','Alegre','ES','29500-000');
SET @admin = LAST_INSERT_ID();
INSERT INTO usuarios (nome,email,senha_hash,tipo,cpf_cnpj,telefone,endereco,cidade,estado,cep) VALUES ('Café & Artesanato do Caparaó','fornecedor@teste.com','$2y$12$NKAGWiR9zmr5o.K9iddFaubtf4bI/ad/pBdiT4lB1udYXBscWgyYa','fornecedor','12345678000199','(28) 99999-0000','Rua Principal, 100','Alegre','ES','29500-000');
SET @forn = LAST_INSERT_ID();
INSERT INTO usuarios (nome,email,senha_hash,tipo,cpf_cnpj,telefone,endereco,cidade,estado,cep) VALUES ('Loja Online Teste','vendedor@teste.com','$2y$12$Go5phzdfYBj6Dzzp1IugJuQk76txTAt5SzOtC/FwSsL35WGphpeEu','vendedor','98765432100','(28) 99999-0000','Rua Principal, 100','Vitória','ES','29500-000');
SET @vend = LAST_INSERT_ID();

-- Fornecedor e vendedor com plano Premium por 12 meses a partir da importação (pagamento simulado).
INSERT INTO assinaturas (usuario_id,plano_id,inicio,fim) SELECT @forn, id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH) FROM planos_assinatura WHERE nome='Premium';
INSERT INTO pagamentos (assinatura_id,valor,status,metodo) SELECT LAST_INSERT_ID(), ROUND(preco_base * (1 - desconto_progressivo / 100), 2), 'aprovado', 'pix' FROM planos_assinatura WHERE nome='Premium';
INSERT INTO assinaturas (usuario_id,plano_id,inicio,fim) SELECT @vend, id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH) FROM planos_assinatura WHERE nome='Premium';
INSERT INTO pagamentos (assinatura_id,valor,status,metodo) SELECT LAST_INSERT_ID(), ROUND(preco_base * (1 - desconto_progressivo / 100), 2), 'aprovado', 'pix' FROM planos_assinatura WHERE nome='Premium';

-- Vínculo aprovado entre o fornecedor e o vendedor de teste (RN01).
INSERT INTO vinculos (fornecedor_id,vendedor_id,status) VALUES (@forn,@vend,'aprovado');

-- Produtos do fornecedor de teste.
INSERT INTO produtos (fornecedor_id,categoria_id,nome,descricao,sku,preco_base,preco_sugerido,estoque_atual,estoque_minimo,peso_kg,ativo,verificado_em) SELECT @forn, id, 'Café Especial Caparaó 500g', 'Café Especial Caparaó 500g — produto local.', 'CAFE-500', 32.90, 45.90, 40, 5, 0.500, 1, NOW() FROM categorias WHERE nome='Alimentos';
INSERT INTO produtos (fornecedor_id,categoria_id,nome,descricao,sku,preco_base,preco_sugerido,estoque_atual,estoque_minimo,peso_kg,ativo,verificado_em) SELECT @forn, id, 'Panela de Pedra Artesanal', 'Panela de Pedra Artesanal — produto local.', 'PANELA-01', 89.00, 129.90, 6, 5, 0.500, 1, NOW() FROM categorias WHERE nome='Artesanato';
INSERT INTO produtos (fornecedor_id,categoria_id,nome,descricao,sku,preco_base,preco_sugerido,estoque_atual,estoque_minimo,peso_kg,ativo,verificado_em) SELECT @forn, id, 'Geleia de Morango 300g', 'Geleia de Morango 300g — produto local.', 'GELEIA-300', 14.50, 22.00, 25, 5, 0.500, 1, NOW() FROM categorias WHERE nome='Alimentos';

-- Fim.
