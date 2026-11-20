<?php
class Usuario {
    static function criar(array $d): int {
        q('INSERT INTO usuarios (nome,email,senha_hash,tipo,cpf_cnpj,telefone,endereco,cidade,estado,cep) VALUES (?,?,?,?,?,?,?,?,?,?)',
          [$d['nome'], $d['email'], $d['senha_hash'], $d['tipo'], $d['cpf_cnpj'], $d['telefone'], $d['endereco'], $d['cidade'], $d['estado'], $d['cep']]);
        return (int)db()->lastInsertId();
    }
    static function porEmail(string $e): ?array { return q1('SELECT * FROM usuarios WHERE email=?', [$e]); }
    static function achar(int $id): ?array { return q1('SELECT * FROM usuarios WHERE id=?', [$id]); }
    static function todos(): array { return qa('SELECT id,nome,email,tipo,cidade,ativo,penalidades,criado_em FROM usuarios ORDER BY id DESC'); }
    static function alternar(int $id): void { q('UPDATE usuarios SET ativo = 1 - ativo WHERE id=? AND tipo<>"admin"', [$id]); }
}
