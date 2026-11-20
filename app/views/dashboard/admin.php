<?php
$icones = ['fornecedor' => 'shop', 'vendedor' => 'megaphone', 'admin' => 'shield-lock'];
$cards = [];
foreach ($m['usuarios'] as $r) $cards[] = ['Usuários: ' . $r['tipo'], (string)(int)$r['c'], $icones[$r['tipo']] ?? 'people'];
$cards[] = ['Pedidos', (string)$m['pedidos'], 'truck'];
$cards[] = ['Assinaturas ativas', (string)$m['assinaturas'], 'credit-card'];
$cards[] = ['Receita (assinaturas)', money($m['receita']), 'cash-coin'];
?>
<h1 class="h3 mb-3">Painel administrativo</h1>
<div class="row g-3 mb-4">
<?php foreach ($cards as [$rot, $val, $ic]): ?>
  <div class="col-6 col-lg-3"><div class="card wd-stat h-100"><div class="card-body d-flex align-items-center gap-3">
    <span class="wd-stat-icon"><i class="bi bi-<?= $ic ?>"></i></span>
    <div class="text-truncate"><div class="text-muted small"><?= e($rot) ?></div><div class="wd-stat-num text-nowrap" style="font-size:1.5rem"><?= e($val) ?></div></div>
  </div></div></div>
<?php endforeach; ?>
</div>
