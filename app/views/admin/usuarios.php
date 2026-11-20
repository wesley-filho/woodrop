<h1 class="h3 mb-3">Usuários</h1>
<div class="table-responsive card mb-4"><table class="table align-middle mb-0"><thead><tr><th>#</th><th>Nome</th><th>E-mail</th><th>Tipo</th><th>Cidade</th><th>Penalid.</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($usuarios as $u2): ?><tr><td><?= $u2['id'] ?></td><td><?= e($u2['nome']) ?></td><td><?= e($u2['email']) ?></td><td><?= e($u2['tipo']) ?></td><td><?= e($u2['cidade']) ?></td><td><?= (int)$u2['penalidades'] ?></td>
  <td><?= $u2['ativo'] ? '<span class="badge text-bg-success">ativo</span>' : '<span class="badge text-bg-secondary">inativo</span>' ?></td>
  <td><?php if ($u2['tipo'] !== 'admin'): ?><form method="post" action="<?= e(url('admin/usuarios/' . $u2['id'] . '/toggle')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary"><?= $u2['ativo'] ? 'Desativar' : 'Ativar' ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<h2 class="h5">Planos de assinatura</h2>
<div class="table-responsive card"><table class="table align-middle mb-0"><thead><tr><th>Plano</th><th>Período</th><th>Valor final</th><th>Desconto</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($planos as $p): ?><tr><td><?= e($p['nome']) ?></td><td><?= e($p['periodo']) ?></td><td><?= money(PlanoAssinatura::precoFinal($p)) ?></td><td><?= (int)$p['desconto_progressivo'] ?>%</td><td><?= $p['ativo'] ? 'ativo' : 'inativo' ?></td>
  <td><form method="post" action="<?= e(url('admin/planos/' . $p['id'] . '/toggle')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary"><?= $p['ativo'] ? 'Desativar' : 'Ativar' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div>
