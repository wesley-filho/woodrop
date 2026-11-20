<?php
class Pedido {
    static function sqlLista(array $u, ?string $status): array {
        $w = []; $p = [];
        if ($u['tipo'] === 'fornecedor') { $w[] = 'pe.fornecedor_id=?'; $p[] = $u['id']; }
        elseif ($u['tipo'] === 'vendedor') { $w[] = 'pe.vendedor_id=?'; $p[] = $u['id']; }
        if ($status) { $w[] = 'pe.status=?'; $p[] = $status; }
        $sql = 'SELECT pe.*, pr.nome produto_nome, uv.nome vendedor_nome, uf.nome fornecedor_nome FROM pedidos pe
                JOIN produtos pr ON pr.id=pe.produto_id JOIN usuarios uv ON uv.id=pe.vendedor_id JOIN usuarios uf ON uf.id=pe.fornecedor_id'
             . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY pe.id DESC';
        return [$sql, $p];
    }
    /** Pedido visível ao usuário (dono vendedor/fornecedor ou admin). */
    static function achar(int $id, array $u): ?array {
        $p = q1('SELECT pe.*, pr.nome produto_nome, pr.estoque_atual, pr.estoque_minimo, uv.nome vendedor_nome, uf.nome fornecedor_nome
                 FROM pedidos pe JOIN produtos pr ON pr.id=pe.produto_id JOIN usuarios uv ON uv.id=pe.vendedor_id JOIN usuarios uf ON uf.id=pe.fornecedor_id WHERE pe.id=?', [$id]);
        if (!$p) return null;
        if ($u['tipo'] === 'admin' || (int)$p['vendedor_id'] === (int)$u['id'] || (int)$p['fornecedor_id'] === (int)$u['id']) return $p;
        return null;
    }
    /**
     * RF07: registra pedido enviado pela loja do vendedor e notifica o fornecedor.
     * RN01: exige vínculo aprovado + produto ativo + estoque suficiente.
     * (RN05: a baixa de estoque só ocorre na confirmação pelo fornecedor — ver StockService.)
     * $origem (opcional, só uso interno): ['canal' => 'mercadolivre', 'id' => id da venda no marketplace].
     * @return array [id|null, erro|null]
     */
    static function criar(array $vend, array $d, ?array $origem = null): array {
        $prod = Produto::achar((int)$d['produto_id']);
        if (!$prod || !$prod['ativo']) return [null, 'Produto não encontrado ou inativo.'];
        if (!Vinculo::aprovado((int)$prod['fornecedor_id'], (int)$vend['id'])) return [null, 'RN01: vínculo aprovado com o fornecedor é obrigatório.'];
        $qtd = (int)$d['quantidade'];
        if ($qtd < 1) return [null, 'Quantidade inválida.'];
        if ((int)$prod['estoque_atual'] < $qtd) return [null, 'RN01: estoque insuficiente (disponível: ' . (int)$prod['estoque_atual'] . ').'];
        $unit = (float)$prod['preco_base'];
        q('INSERT INTO pedidos (vendedor_id,canal,pedido_externo_id,fornecedor_id,produto_id,quantidade,preco_unitario,preco_total,cliente_nome,cliente_endereco,cliente_cidade,cliente_estado,cliente_cep,cliente_telefone,observacoes)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
          [$vend['id'], $origem['canal'] ?? null, $origem['id'] ?? null, $prod['fornecedor_id'], $prod['id'], $qtd, $unit, round($unit * $qtd, 2), $d['cliente_nome'], $d['cliente_endereco'], $d['cliente_cidade'],
           strtoupper($d['cliente_estado']), $d['cliente_cep'], $d['cliente_telefone'] ?? null, $d['observacoes'] ?? null]);
        $id = (int)db()->lastInsertId();
        NotificationService::novoPedido((int)$prod['fornecedor_id'], $id, $prod['nome'], $qtd);
        log_action('pedido_criado', "pedido=$id produto={$prod['id']} qtd=$qtd", (int)$vend['id']);
        MercadoLivreService::sincronizarProduto((int)$prod['id']); // reserva: reduz a quantidade disponível nos marketplaces
        return [$id, null];
    }
}
