-- WooDROP - Migração 001: integração com marketplaces (inicialmente Mercado Livre).
-- Para bancos já existentes:  mysql -u root -p woodrop < database/migrations/001_mercadolivre.sql
-- (Instalações novas já recebem estas tabelas pelo database/schema.sql.)
SET NAMES utf8mb4; -- garante acentos corretos mesmo se o cliente mysql usar outro charset por padrão

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
