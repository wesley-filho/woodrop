<?php
class NotificationService {
    /** RF07: avisa o fornecedor de um novo pedido recebido via API. */
    static function novoPedido(int $fid, int $pedidoId, string $produto, int $qtd): void {
        Notificacao::criar($fid, "Novo pedido #$pedidoId", "$qtd x $produto. Confirme em até " . PRAZO_CONFIRMACAO_H . "h úteis (RN03).");
    }
    /** RF08: avisa o vendedor sobre mudança de status. */
    static function statusAlterado(int $vid, int $pedidoId, string $status): void {
        Notificacao::criar($vid, "Pedido #$pedidoId: " . str_replace('_', ' ', $status), 'O fornecedor atualizou o status do pedido.');
    }
    static function estoqueBaixo(int $fid, array $p): void {
        Notificacao::criar($fid, 'Alerta de estoque baixo', "{$p['nome']} está com {$p['estoque_atual']} unidade(s) (mínimo {$p['estoque_minimo']}).");
    }
}
