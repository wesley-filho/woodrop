<?php
class Vinculo {
    static function aprovado(int $fid, int $vid): bool { return (bool)q1('SELECT id FROM vinculos WHERE fornecedor_id=? AND vendedor_id=? AND status="aprovado"', [$fid, $vid]); }
    static function solicitar(int $fid, int $vid): ?string {
        $f = Usuario::achar($fid);
        if (!$f || $f['tipo'] !== 'fornecedor' || !$f['ativo']) return 'Fornecedor não encontrado.';
        $v = q1('SELECT status FROM vinculos WHERE fornecedor_id=? AND vendedor_id=?', [$fid, $vid]);
        if ($v && $v['status'] !== 'recusado') return 'Já existe uma solicitação (' . $v['status'] . ').';
        q('INSERT INTO vinculos (fornecedor_id,vendedor_id,status) VALUES (?,?,"pendente") ON DUPLICATE KEY UPDATE status="pendente"', [$fid, $vid]);
        Notificacao::criar($fid, 'Nova solicitação de vínculo', 'Um vendedor deseja anunciar seus produtos.');
        log_action('vinculo_solicitado', "f=$fid v=$vid", $vid);
        return null;
    }
    static function decidir(int $id, int $fid, bool $aprova): void {
        $v = q1('SELECT * FROM vinculos WHERE id=? AND fornecedor_id=?', [$id, $fid]);
        if (!$v) return;
        q('UPDATE vinculos SET status=? WHERE id=?', [$aprova ? 'aprovado' : 'recusado', $id]);
        Notificacao::criar((int)$v['vendedor_id'], 'Vínculo ' . ($aprova ? 'aprovado' : 'recusado'), 'Um fornecedor respondeu à sua solicitação.');
        log_action('vinculo_' . ($aprova ? 'aprovado' : 'recusado'), "id=$id", $fid);
    }
    static function doFornecedor(int $fid): array { return qa('SELECT v.*, u.nome vendedor_nome, u.email vendedor_email FROM vinculos v JOIN usuarios u ON u.id=v.vendedor_id WHERE v.fornecedor_id=? ORDER BY v.status="pendente" DESC, v.id DESC', [$fid]); }
    static function doVendedor(int $vid): array { return qa('SELECT v.*, u.nome fornecedor_nome FROM vinculos v JOIN usuarios u ON u.id=v.fornecedor_id WHERE v.vendedor_id=? ORDER BY v.id DESC', [$vid]); }
    /** Fornecedores ativos com o status do vínculo do vendedor (se houver). */
    static function fornecedoresParaVendedor(int $vid): array {
        return qa('SELECT u.id, u.nome, u.cidade, v.status FROM usuarios u LEFT JOIN vinculos v ON v.fornecedor_id=u.id AND v.vendedor_id=? WHERE u.tipo="fornecedor" AND u.ativo=1 ORDER BY u.nome', [$vid]);
    }
}
