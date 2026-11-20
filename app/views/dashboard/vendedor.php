<?php
$cards = [['Anúncios ativos', $m['anuncios'], 'megaphone', 'anuncios'], ['Pedidos enviados', $m['pedidos'], 'truck', 'pedidos'], ['Fornecedores parceiros', $m['vinculos'], 'people', 'vinculos']];
$passos = [[$m['vinculos'] > 0, 'Peça vínculo a um fornecedor', 'Só os produtos de fornecedores que aprovaram você aparecem no catálogo.', 'vinculos', 'Ver fornecedores'],
           [$m['anuncios'] > 0, 'Anuncie produtos do catálogo', 'Escolha o preço de venda (nunca menor que o preço-base).', 'catalogo', 'Abrir catálogo'],
           [$m['mercadolivre'], 'Conecte o Mercado Livre', 'Publique seus anúncios e receba as vendas como pedidos.', 'integracoes', 'Ir para Integrações']];
$feitos = count(array_filter(array_column($passos, 0)));
?>
<h1 class="h3 mb-3">Painel do vendedor</h1>
<div class="row g-3 mb-4">
<?php foreach ($cards as [$rot, $num, $ic, $href]): ?>
  <div class="col-12 col-md-4"><div class="card wd-stat wd-click h-100"><div class="card-body d-flex align-items-center gap-3">
    <span class="wd-stat-icon"><i class="bi bi-<?= $ic ?>"></i></span>
    <div><div class="text-muted small"><?= e($rot) ?></div><div class="wd-stat-num"><?= (int)$num ?></div></div>
    <a class="stretched-link" href="<?= e(url($href)) ?>" aria-label="<?= e($rot) ?>: <?= (int)$num ?>"></a>
  </div></div></div>
<?php endforeach; ?>
</div>
<?php if ($feitos < count($passos)): ?>
<div class="card"><div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-signpost-2 me-1"></i>Primeiros passos</span><span class="small text-muted"><?= $feitos ?> de <?= count($passos) ?></span></div>
<div class="card-body py-1">
<?php foreach ($passos as $i => [$ok, $tit, $txt, $href, $btn]): ?>
  <div class="wd-step <?= $ok ? 'done' : '' ?>">
    <span class="wd-step-mark"><?= $ok ? '<i class="bi bi-check-lg"></i><span class="visually-hidden">Concluído:</span>' : $i + 1 ?></span>
    <div class="flex-grow-1"><div class="fw-semibold"><?= e($tit) ?></div><div class="small text-muted"><?= e($txt) ?></div></div>
    <?php if (!$ok): ?><a class="btn btn-sm btn-outline-success text-nowrap" href="<?= e(url($href)) ?>"><?= e($btn) ?></a><?php endif; ?>
  </div>
<?php endforeach; ?>
</div></div>
<?php endif; ?>
