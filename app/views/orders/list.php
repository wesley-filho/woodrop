<?php
$tipo = $user['tipo'];
$verVendedor = $tipo !== 'vendedor';      // o vendedor não precisa ver o próprio nome em cada linha
$verFornecedor = $tipo !== 'fornecedor';
$colunas = 6 + (int)$verVendedor + (int)$verFornecedor;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2"><h1 class="h3 mb-0">Pedidos</h1>
  <form method="get" class="d-flex gap-2"><label class="visually-hidden" for="st">Filtrar por status</label><select class="form-select form-select-sm" id="st" name="status" onchange="this.form.submit()">
    <option value="">Todos os status</option><?php foreach (['recebido', 'em_separacao', 'enviado', 'entregue', 'cancelado'] as $s): ?><option value="<?= $s ?>" <?= $filtro === $s ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $s)) ?></option><?php endforeach; ?></select>
    <noscript><button class="btn btn-sm btn-outline-secondary">Filtrar</button></noscript></form></div>
<p class="small text-muted mb-3"><i class="bi bi-info-circle"></i> RN09: a WooDROP não se responsabiliza por logística nem pelo relacionamento com o consumidor final.</p>
<div class="table-responsive card"><table class="table table-hover align-middle mb-0"><thead><tr><th>#</th><th>Produto</th><th class="text-end d-none d-sm-table-cell">Qtd</th><th class="text-end">Total</th>
  <?php if ($verVendedor): ?><th class="d-none d-lg-table-cell">Vendedor</th><?php endif; ?><?php if ($verFornecedor): ?><th class="d-none d-lg-table-cell">Fornecedor</th><?php endif; ?>
  <th>Status</th><th class="d-none d-md-table-cell">Data</th><th class="d-none d-sm-table-cell"></th></tr></thead><tbody>
<?php foreach ($pedidos as $p): $agir = $tipo === 'fornecedor' && $p['status'] === 'recebido'; ?><tr>
  <td class="text-nowrap"><?= $p['id'] ?><?= $p['canal'] === 'mercadolivre' ? ' <span class="badge text-bg-warning" title="Venda do Mercado Livre">ML</span>' : '' ?></td>
  <td><a class="text-reset text-decoration-none" href="<?= e(url('pedidos/' . $p['id'])) ?>"><?= e($p['produto_nome']) ?></a><div class="small text-muted d-sm-none"><?= (int)$p['quantidade'] ?> un.</div></td><td class="text-end d-none d-sm-table-cell"><?= (int)$p['quantidade'] ?></td><td class="text-end text-nowrap"><?= money($p['preco_total']) ?></td>
  <?php if ($verVendedor): ?><td class="d-none d-lg-table-cell"><?= e($p['vendedor_nome']) ?></td><?php endif; ?><?php if ($verFornecedor): ?><td class="d-none d-lg-table-cell"><?= e($p['fornecedor_nome']) ?></td><?php endif; ?>
  <td><?= badge_status($p['status']) ?><?php if ($agir): ?><a class="d-sm-none d-block small mt-1" href="<?= e(url('pedidos/' . $p['id'])) ?>">Confirmar</a><?php endif; ?></td><td class="d-none d-md-table-cell text-nowrap small"><?= e(date('d/m/Y H:i', strtotime($p['criado_em']))) ?></td>
  <td class="text-end d-none d-sm-table-cell"><a class="btn btn-sm <?= $agir ? 'btn-success' : 'btn-outline-primary' ?> text-nowrap" href="<?= e(url('pedidos/' . $p['id'])) ?>"><?= $agir ? 'Confirmar' : ($tipo === 'fornecedor' ? 'Atualizar' : 'Ver') ?></a></td></tr>
<?php endforeach; if (!$pedidos): ?><tr><td colspan="<?= $colunas ?>"><div class="wd-empty"><i class="bi bi-inbox"></i><?= $filtro ? 'Nenhum pedido com este status.' : 'Nenhum pedido ainda.' ?></div></td></tr><?php endif; ?></tbody></table></div>
<div class="mt-3"><?= pager($pg, $pages) ?></div>
