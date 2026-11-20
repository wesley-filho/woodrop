<?php
class Pagamento {
    static function registrar(int $assId, float $valor, string $status, string $metodo): void { q('INSERT INTO pagamentos (assinatura_id,valor,status,metodo) VALUES (?,?,?,?)', [$assId, $valor, $status, $metodo]); }
}
