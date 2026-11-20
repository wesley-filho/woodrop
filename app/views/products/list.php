<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h1 class="h3 mb-0">Meus produtos</h1><a class="btn btn-success" href="<?= e(url('produtos/novo')) ?>"><i class="bi bi-plus-lg"></i> Novo produto</a></div>
<div class="table-responsive card"><table class="table table-hover align-middle mb-0">
<thead><tr><th>Produto</th><th class="d-none d-md-table-cell">SKU</th><th class="text-end">Preço-base</th><th class="text-end">Estoque</th><th class="d-none d-sm-table-cell">Status</th><th class="text-end">Ações</th></tr></thead><tbody>
<?php foreach ($produtos as $p): $crit = $p['estoque_atual'] <= $p['estoque_minimo']; ?>
<tr class="<?= $crit ? 'table-danger' : '' ?>">
  <td><?php if ($p['imagem_url']): ?><img src="<?= e(url($p['imagem_url'])) ?>" alt="" class="wd-thumb me-2"><?php endif; ?><?= e($p['nome']) ?><div class="small text-muted"><?= e($p['categoria_nome']) ?></div></td>
  <td class="d-none d-md-table-cell small"><?= e($p['sku']) ?></td><td class="text-end text-nowrap"><?= money($p['preco_base']) ?></td>
  <td class="text-end text-nowrap"><?= (int)$p['estoque_atual'] ?><?= $crit ? ' <span class="badge text-bg-danger">baixo</span>' : '' ?></td>
  <td class="d-none d-sm-table-cell"><?= $p['ativo'] ? '<span class="badge text-bg-success">ativo</span>' : '<span class="badge text-bg-secondary">inativo</span>' ?></td>
  <td class="text-end text-nowrap">
    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('produtos/' . $p['id'] . '/editar')) ?>">Editar</a>
    <form class="d-inline" method="post" action="<?= e(url('produtos/' . $p['id'] . '/toggle')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary"><?= $p['ativo'] ? 'Desativar' : 'Ativar' ?></button></form>
    <form class="d-inline" method="post" action="<?= e(url('produtos/' . $p['id'] . '/excluir')) ?>" data-confirm="Excluir este produto?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Excluir</button></form>
  </td></tr>
<?php endforeach; if (!$produtos): ?><tr><td colspan="6"><div class="wd-empty"><i class="bi bi-box-seam"></i>Nenhum produto cadastrado. <a href="<?= e(url('produtos/novo')) ?>">Cadastre o primeiro</a>.</div></td></tr><?php endif; ?>
</tbody></table></div>
<div class="mt-3"><?= pager($pg, $pages) ?></div>
