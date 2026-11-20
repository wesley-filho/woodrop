<?php
class PlanoAssinatura {
    const MESES = ['mensal' => 1, 'semestral' => 6, 'anual' => 12];
    static function todos(bool $soAtivos = true): array { return qa('SELECT * FROM planos_assinatura' . ($soAtivos ? ' WHERE ativo=1' : '') . ' ORDER BY id'); }
    static function achar(int $id): ?array { return q1('SELECT * FROM planos_assinatura WHERE id=?', [$id]); }
    /** Valor final do período já com o desconto progressivo (Tabela 4). */
    static function precoFinal(array $p): float { return round((float)$p['preco_base'] * (1 - (float)$p['desconto_progressivo'] / 100), 2); }
    static function alternar(int $id): void { q('UPDATE planos_assinatura SET ativo = 1 - ativo WHERE id=?', [$id]); }
}
