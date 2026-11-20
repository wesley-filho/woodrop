<?php
$cards = [['Produtos ativos', $m['produtos'], 'box-seam', 'produtos', false],
          ['Pedidos a confirmar', $m['pendentes'], 'inbox', 'pedidos?status=recebido', false],
          ['Vínculos a aprovar', $m['vinculos'], 'people', 'vinculos', false],
          ['Alertas de estoque', count($m['alertas']), 'exclamation-triangle', 'produtos', (bool)$m['alertas']]];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 mb-0">Painel do fornecedor</h1>
  <a class="btn btn-success" href="<?= e(url('produtos/novo')) ?>"><i class="bi bi-plus-lg"></i> Novo produto</a>
</div>
<div class="row g-3 mb-4">
<?php foreach ($cards as [$rot, $num, $ic, $href, $alerta]): ?>
  <div class="col-6 col-lg-3"><div class="card wd-stat wd-click h-100 <?= $alerta ? 'border-danger' : '' ?>"><div class="card-body d-flex align-items-center gap-3">
    <span class="wd-stat-icon"><i class="bi bi-<?= $ic ?>"></i></span>
    <div><div class="text-muted small"><?= e($rot) ?></div><div class="wd-stat-num <?= $alerta ? 'text-danger' : '' ?>"><?= (int)$num ?></div></div>
    <a class="stretched-link" href="<?= e(url($href)) ?>" aria-label="<?= e($rot) ?>: <?= (int)$num ?>"></a>
  </div></div></div>
<?php endforeach; ?>
</div>
<?php if ($m['pendentes']): ?>
<div class="alert alert-warning d-flex flex-wrap align-items-center gap-2"><i class="bi bi-clock-history"></i>
  <span>Você tem <strong><?= (int)$m['pendentes'] ?> pedido(s)</strong> esperando confirmação. O prazo é de <?= PRAZO_CONFIRMACAO_H ?>h úteis (RN03).</span>
  <a class="btn btn-sm btn-warning ms-auto" href="<?= e(url('pedidos?status=recebido')) ?>">Ver pedidos</a></div>
<?php endif; ?>
<?php if ($m['alertas']): ?>
<div class="alert alert-danger"><strong><i class="bi bi-exclamation-triangle"></i> Estoque baixo (RN08):</strong> novas baixas exigirão verificação em duas etapas.
  <ul class="mb-0"><?php foreach ($m['alertas'] as $a): ?><li><a href="<?= e(url('produtos/' . $a['id'] . '/editar')) ?>"><?= e($a['nome']) ?></a> — <?= (int)$a['estoque_atual'] ?> un. (mín. <?= (int)$a['estoque_minimo'] ?>)</li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php if ($m['verificar']): ?>
<div class="card mb-3"><div class="card-header"><i class="bi bi-clipboard-check me-1"></i>Verificação periódica de estoque (RN07)</div><div class="card-body">
  <p class="small text-muted">Confirme a quantidade real dos produtos abaixo para manter seu catálogo confiável.</p>
  <?php foreach ($m['verificar'] as $v): ?>
  <form class="row g-2 align-items-center mb-2" method="post" action="<?= e(url('produtos/' . $v['id'] . '/verificar')) ?>"><?= csrf_field() ?>
    <div class="col-12 col-md-5"><?= e($v['nome']) ?></div>
    <div class="col-auto"><label class="visually-hidden" for="q<?= $v['id'] ?>">Quantidade real</label><div class="input-group input-group-sm"><input class="form-control" style="max-width:100px" id="q<?= $v['id'] ?>" type="number" min="0" name="quantidade" value="<?= (int)$v['estoque_atual'] ?>"><span class="input-group-text">un.</span></div></div>
    <div class="col-auto"><button class="btn btn-sm btn-success">Confirmar</button></div></form>
  <?php endforeach; ?></div></div>
<?php endif; ?>
<?php if (!$m['pendentes'] && !$m['alertas'] && !$m['verificar']): ?>
<div class="card"><div class="wd-empty"><i class="bi bi-check2-circle"></i>Tudo em dia: nenhum pedido esperando e nenhum alerta de estoque.</div></div>
<?php endif; ?>
