<?php
/** Regras de estoque e status de pedido (RN03, RN05, RN07, RN08). */
class StockService {
    const TRANSICOES = [
        'recebido' => ['em_separacao', 'cancelado'],
        'em_separacao' => ['enviado', 'cancelado'],
        'enviado' => ['entregue'],
        'entregue' => [], 'cancelado' => [],
    ];
    static function critico(array $p): bool { return (int)$p['estoque_atual'] <= (int)$p['estoque_minimo']; } // RN08

    /**
     * Atualiza o status de um pedido (RF08) em transação ACID.
     * - recebido -> em_separacao = confirmação: RN05 baixa o estoque; RN08 exige 2ª etapa se estoque crítico.
     * - cancelado após confirmação devolve o estoque.
     * @return array ['ok'=>bool,'msg'=>string,'segunda_etapa'=>bool]
     */
    static function atualizarStatus(int $pedidoId, int $fid, string $novo, ?string $rastreio = null, bool $segundaEtapa = false): array {
        $pdo = db(); $pdo->beginTransaction();
        try {
            $ped = q1('SELECT * FROM pedidos WHERE id=? AND fornecedor_id=? FOR UPDATE', [$pedidoId, $fid]);
            if (!$ped) { $pdo->rollBack(); return ['ok' => false, 'msg' => 'Pedido não encontrado.', 'segunda_etapa' => false]; }
            if (!in_array($novo, self::TRANSICOES[$ped['status']] ?? [], true)) {
                $pdo->rollBack(); return ['ok' => false, 'msg' => "Transição inválida: {$ped['status']} → $novo.", 'segunda_etapa' => false];
            }
            $prod = q1('SELECT * FROM produtos WHERE id=? FOR UPDATE', [$ped['produto_id']]);
            if ($ped['status'] === 'recebido' && $novo === 'em_separacao') {
                if ((int)$prod['estoque_atual'] < (int)$ped['quantidade']) { $pdo->rollBack(); return ['ok' => false, 'msg' => 'Estoque insuficiente para confirmar. Atualize o estoque ou cancele o pedido.', 'segunda_etapa' => false]; }
                if (self::critico($prod) && !$segundaEtapa) { // RN08
                    $pdo->rollBack(); return ['ok' => false, 'msg' => 'RN08: estoque crítico. Confirme a verificação em duas etapas para dar baixa.', 'segunda_etapa' => true];
                }
                q('UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id=?', [$ped['quantidade'], $prod['id']]); // RN05
                q('UPDATE pedidos SET confirmado_em=NOW() WHERE id=?', [$pedidoId]);
                log_action('estoque_baixa', "produto={$prod['id']} qtd={$ped['quantidade']} pedido=$pedidoId", $fid);
            }
            if ($novo === 'cancelado' && $ped['confirmado_em']) { // devolve estoque já baixado
                q('UPDATE produtos SET estoque_atual = estoque_atual + ? WHERE id=?', [$ped['quantidade'], $prod['id']]);
                log_action('estoque_estorno', "produto={$prod['id']} qtd={$ped['quantidade']} pedido=$pedidoId", $fid);
            }
            q('UPDATE pedidos SET status=?, codigo_rastreio=COALESCE(?, codigo_rastreio) WHERE id=?', [$novo, $rastreio ?: null, $pedidoId]);
            $pdo->commit();
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
        log_action('pedido_status', "pedido=$pedidoId status=$novo", $fid);
        NotificationService::statusAlterado((int)$ped['vendedor_id'], $pedidoId, $novo);
        MercadoLivreService::sincronizarProduto((int)$ped['produto_id']); // estoque/reserva mudaram: atualiza marketplaces
        $p = Produto::achar((int)$ped['produto_id']);
        if ($p && self::critico($p)) NotificationService::estoqueBaixo($fid, $p); // alerta de estoque baixo
        return ['ok' => true, 'msg' => 'Status atualizado.', 'segunda_etapa' => false];
    }

    /** RN07: fornecedor responde ao questionário confirmando a quantidade real em estoque. */
    static function verificar(int $pid, int $fid, int $qtd): bool {
        $r = q('UPDATE produtos SET estoque_atual=?, verificado_em=NOW() WHERE id=? AND fornecedor_id=?', [max(0, $qtd), $pid, $fid]);
        log_action('estoque_verificado', "produto=$pid qtd=$qtd", $fid);
        MercadoLivreService::sincronizarProduto($pid);
        return $r->rowCount() >= 0;
    }
    /** Produtos que precisam de verificação (RN07). */
    static function pendentesVerificacao(int $fid): array {
        return qa('SELECT id,nome,estoque_atual FROM produtos WHERE fornecedor_id=? AND ativo=1 AND (verificado_em IS NULL OR verificado_em < (NOW() - INTERVAL ' . (int)DIAS_VERIFICACAO_ESTOQUE . ' DAY))', [$fid]);
    }
}
