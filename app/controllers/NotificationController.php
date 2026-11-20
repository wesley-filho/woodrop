<?php
class NotificationController {
    /** JSON usado pelo sino de notificações (AJAX). */
    function lista() { $u = AuthMiddleware::exigir([], false); json_out(true, ['nao_lidas' => Notificacao::naoLidas((int)$u['id']), 'itens' => Notificacao::recentes((int)$u['id'])]); }
    function lidas() { $u = AuthMiddleware::exigir([], false); csrf_check(); Notificacao::marcarLidas((int)$u['id']); json_out(true); }
}
