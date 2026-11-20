<?php
class Anuncio {
    /** Cria/atualiza anúncio. RN01: exige vínculo aprovado e estoque; RN04: preço >= preço-base. Retorna erro ou null. */
    static function salvar(int $vid, int $pid, float $preco): ?string {
        $p = Produto::achar($pid);
        if (!$p || !$p['ativo']) return 'Produto indisponível.';
        if (!Vinculo::aprovado((int)$p['fornecedor_id'], $vid)) return 'RN01: é necessário vínculo aprovado com o fornecedor.';
        if ((int)$p['estoque_atual'] <= 0) return 'RN01: produto sem estoque disponível.';
        if ($preco < (float)$p['preco_base']) return 'RN04: o preço de venda não pode ser inferior ao preço-base (' . money($p['preco_base']) . ').';
        q('INSERT INTO anuncios (vendedor_id,produto_id,preco_venda) VALUES (?,?,?) ON DUPLICATE KEY UPDATE preco_venda=VALUES(preco_venda), ativo=1', [$vid, $pid, $preco]);
        MercadoLivreService::sincronizarProduto($pid); // novo preço no Mercado Livre, se publicado
        return null;
    }
    static function doVendedor(int $vid): array {
        return qa('SELECT a.*, p.nome produto_nome, p.preco_base, p.estoque_atual, u.nome fornecedor_nome FROM anuncios a JOIN produtos p ON p.id=a.produto_id JOIN usuarios u ON u.id=p.fornecedor_id WHERE a.vendedor_id=? ORDER BY a.id DESC', [$vid]);
    }
    static function alternar(int $id, int $vid): void {
        q('UPDATE anuncios SET ativo = 1 - ativo WHERE id=? AND vendedor_id=?', [$id, $vid]);
        if (q1('SELECT id FROM anuncios_externos WHERE anuncio_id=? AND item_id IS NOT NULL', [$id])) MercadoLivreService::sincronizarAnuncio($id); // pausa/reativa no ML
    }
}
