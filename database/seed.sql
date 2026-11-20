USE woodrop;
SET NAMES utf8mb4; -- garante acentos corretos mesmo se o cliente mysql usar outro charset por padrão
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
-- Usuários (admin, fornecedor e vendedor de teste) são criados com senha hasheada por:
--   php scripts/seed_users.php
