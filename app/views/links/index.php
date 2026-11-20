<?php if ($user['tipo'] === 'fornecedor'): ?>
<h1 class="h3 mb-3">Vínculos de vendedores</h1>
<div class="table-responsive card"><table class="table align-middle mb-0"><thead><tr><th>Vendedor</th><th>E-mail</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($vinculos as $v): ?><tr><td><?= e($v['vendedor_nome']) ?></td><td><?= e($v['vendedor_email']) ?></td><td><?= badge_status($v['status']) ?></td>
  <td class="text-end"><?php if ($v['status'] !== 'aprovado'): ?><form class="d-inline" method="post" action="<?= e(url('vinculos/' . $v['id'] . '/decidir')) ?>"><?= csrf_field() ?><button name="acao" value="aprovar" class="btn btn-sm btn-success">Aprovar</button></form><?php endif; ?>
  <?php if ($v['status'] !== 'recusado'): ?><form class="d-inline" method="post" action="<?= e(url('vinculos/' . $v['id'] . '/decidir')) ?>"><?= csrf_field() ?><button name="acao" value="recusar" class="btn btn-sm btn-outline-danger"><?= $v['status'] === 'aprovado' ? 'Revogar' : 'Recusar' ?></button></form><?php endif; ?></td></tr>
<?php endforeach; if (!$vinculos): ?><tr><td colspan="4" class="text-center text-muted py-4">Nenhuma solicitação.</td></tr><?php endif; ?></tbody></table></div>
<?php else: ?>
<h1 class="h3 mb-3">Fornecedores</h1>
<div class="table-responsive card"><table class="table align-middle mb-0"><thead><tr><th>Fornecedor</th><th>Cidade</th><th>Vínculo</th><th></th></tr></thead><tbody>
<?php foreach ($fornecedores as $f): ?><tr><td><?= e($f['nome']) ?></td><td><?= e($f['cidade']) ?></td><td><?= $f['status'] ? badge_status($f['status']) : '—' ?></td>
  <td class="text-end"><?php if (!$f['status'] || $f['status'] === 'recusado'): ?><form method="post" action="<?= e(url('vinculos/solicitar')) ?>"><?= csrf_field() ?><input type="hidden" name="fornecedor_id" value="<?= $f['id'] ?>"><button class="btn btn-sm btn-success">Solicitar vínculo</button></form><?php endif; ?></td></tr>
<?php endforeach; if (!$fornecedores): ?><tr><td colspan="4" class="text-center text-muted py-4">Nenhum fornecedor cadastrado.</td></tr><?php endif; ?></tbody></table></div>
<?php endif; ?>
