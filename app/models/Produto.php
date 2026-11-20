<?php
class Produto {
    static function achar(int $id): ?array { return q1('SELECT p.*, u.nome fornecedor_nome FROM produtos p JOIN usuarios u ON u.id=p.fornecedor_id WHERE p.id=?', [$id]); }
    static function sqlDoFornecedor(int $fid): array {
        return ['SELECT p.*, c.nome categoria_nome FROM produtos p LEFT JOIN categorias c ON c.id=p.categoria_id WHERE p.fornecedor_id=? ORDER BY p.id DESC', [$fid]];
    }
    static function doFornecedor(int $fid): array { [$s, $p] = self::sqlDoFornecedor($fid); return qa($s, $p); }
    static function contar(int $fid): int { return (int)q1('SELECT COUNT(*) c FROM produtos WHERE fornecedor_id=?', [$fid])['c']; }
    static function salvar(int $fid, array $d, ?int $id = null): int {
        $v = [$d['categoria_id'] ?: null, $d['nome'], $d['descricao'], $d['sku'], $d['preco_base'], $d['preco_sugerido'] ?: null,
              $d['estoque_atual'], $d['estoque_minimo'], $d['peso_kg'] ?: null, $d['imagem_url'], $d['ativo']];
        if ($id) {
            q('UPDATE produtos SET categoria_id=?,nome=?,descricao=?,sku=?,preco_base=?,preco_sugerido=?,estoque_atual=?,estoque_minimo=?,peso_kg=?,imagem_url=?,ativo=?,verificado_em=NOW() WHERE id=? AND fornecedor_id=?', array_merge($v, [$id, $fid]));
            MercadoLivreService::sincronizarProduto($id);
            return $id;
        }
        q('INSERT INTO produtos (categoria_id,nome,descricao,sku,preco_base,preco_sugerido,estoque_atual,estoque_minimo,peso_kg,imagem_url,ativo,fornecedor_id,verificado_em) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())', array_merge($v, [$fid]));
        return (int)db()->lastInsertId();
    }
    static function alternar(int $id, int $fid): void { q('UPDATE produtos SET ativo = 1 - ativo WHERE id=? AND fornecedor_id=?', [$id, $fid]); MercadoLivreService::sincronizarProduto($id); }
    /** Exclui se não houver pedidos; caso contrário apenas desativa (preserva histórico). */
    static function excluir(int $id, int $fid): string {
        $tem = q1('SELECT COUNT(*) c FROM pedidos WHERE produto_id=?', [$id])['c'] > 0;
        if ($tem) { q('UPDATE produtos SET ativo=0 WHERE id=? AND fornecedor_id=?', [$id, $fid]); MercadoLivreService::sincronizarProduto($id); return 'desativado'; }
        if (!q1('SELECT id FROM produtos WHERE id=? AND fornecedor_id=?', [$id, $fid])) return 'excluido';
        MercadoLivreService::encerrarProduto($id); // encerra os itens no Mercado Livre antes de apagar os anúncios
        q('DELETE FROM anuncios WHERE produto_id=?', [$id]);
        q('DELETE FROM produtos WHERE id=? AND fornecedor_id=?', [$id, $fid]);
        return 'excluido';
    }
    /** Catálogo do vendedor: apenas produtos de fornecedores com vínculo aprovado (RN01). */
    static function catalogo(int $vid): array {
        return qa('SELECT p.*, u.nome fornecedor_nome, c.nome categoria_nome, a.id anuncio_id, a.preco_venda, a.ativo anuncio_ativo
                   FROM produtos p
                   JOIN vinculos v ON v.fornecedor_id=p.fornecedor_id AND v.vendedor_id=? AND v.status="aprovado"
                   JOIN usuarios u ON u.id=p.fornecedor_id
                   LEFT JOIN categorias c ON c.id=p.categoria_id
                   LEFT JOIN anuncios a ON a.produto_id=p.id AND a.vendedor_id=?
                   WHERE p.ativo=1 ORDER BY p.nome', [$vid, $vid]);
    }
    /** Produto visível ao usuário? (fornecedor: próprio; vendedor: vínculo aprovado; admin: todos) */
    static function visivel(array $u, array $p): bool {
        if ($u['tipo'] === 'admin') return true;
        if ($u['tipo'] === 'fornecedor') return (int)$p['fornecedor_id'] === (int)$u['id'];
        return $p['ativo'] && Vinculo::aprovado((int)$p['fornecedor_id'], (int)$u['id']);
    }
}
