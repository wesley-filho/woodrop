-- WooDROP - Migração 002: corrige acentos "quebrados" (ex.: "BÃ¡sico" em vez de "Básico").
-- Acontece quando o seed.sql foi importado com o cliente mysql em latin1 (dupla codificação).
-- Seguro para rodar mais de uma vez: texto correto não é alterado (a reconversão dele é inválida e o COALESCE mantém o original).
-- Uso: mysql --default-character-set=utf8mb4 -u USUARIO -p BANCO < database/migrations/002_corrige_acentos.sql
--      (ou phpMyAdmin > Importar, com o conjunto de caracteres utf-8)
SET NAMES utf8mb4;

-- Sinal do problema: os bytes de "Ã" ou "Â" (C3 83 / C3 82) seguidos de C2 — nunca aparecem em português correto.
UPDATE planos_assinatura
   SET nome = COALESCE(CONVERT(CAST(CONVERT(nome USING latin1) AS BINARY) USING utf8mb4), nome),
       descricao = COALESCE(CONVERT(CAST(CONVERT(descricao USING latin1) AS BINARY) USING utf8mb4), descricao)
 WHERE HEX(CONCAT(nome, COALESCE(descricao, ''))) REGEXP '^(..)*C3(82|83)C2';

UPDATE categorias
   SET nome = COALESCE(CONVERT(CAST(CONVERT(nome USING latin1) AS BINARY) USING utf8mb4), nome),
       descricao = COALESCE(CONVERT(CAST(CONVERT(descricao USING latin1) AS BINARY) USING utf8mb4), descricao)
 WHERE HEX(CONCAT(nome, COALESCE(descricao, ''))) REGEXP '^(..)*C3(82|83)C2';
