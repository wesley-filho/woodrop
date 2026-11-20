<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h1 class="h3 mb-0">Meus anúncios</h1>
  <a class="btn btn-outline-success" href="<?= e(url('integracoes')) ?>"><i class="bi bi-plug"></i> Publicar no Mercado Livre</a></div>
<div class="table-responsive card"><table class="table align-middle mb-0"><thead><tr><th>Produto</th><th class="d-none d-md-table-cell">Fornecedor</th><th class="text-end d-none d-sm-table-cell">Preço-base</th><th>Preço de venda</th><th class="text-end">Estoque</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($anuncios as $a): ?><tr>
  <td><?= e($a['produto_nome']) ?></td><td class="d-none d-md-table-cell"><?= e($a['fornecedor_nome']) ?></td><td class="text-end text-nowrap d-none d-sm-table-cell"><?= money($a['preco_base']) ?></td>
  <td><form class="d-flex gap-1" method="post" action="<?= e(url('anuncios')) ?>"><?= csrf_field() ?><input type="hidden" name="produto_id" value="<?= $a['produto_id'] ?>"><input type="hidden" name="voltar" value="anuncios">
      <label class="visually-hidden" for="a<?= $a['id'] ?>">Preço de venda</label>
      <div class="input-group input-group-sm" style="width:150px"><span class="input-group-text">R$</span><input class="form-control" id="a<?= $a['id'] ?>" name="preco_venda" inputmode="decimal" value="<?= e(decimal_br($a['preco_venda'])) ?>"></div>
      <button class="btn btn-sm btn-outline-success">Salvar</button></form></td>
  <td class="text-end"><?= (int)$a['estoque_atual'] ?></td><td><?= $a['ativo'] ? '<span class="badge text-bg-success">ativo</span>' : '<span class="badge text-bg-secondary">inativo</span>' ?></td>
  <td class="text-end"><form method="post" action="<?= e(url('anuncios/' . $a['id'] . '/toggle')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary"><?= $a['ativo'] ? 'Desativar' : 'Ativar' ?></button></form></td></tr>
<?php endforeach; if (!$anuncios): ?><tr><td colspan="7"><div class="wd-empty"><i class="bi bi-megaphone"></i>Nenhum anúncio ainda. Escolha produtos no <a href="<?= e(url('catalogo')) ?>">catálogo</a>.</div></td></tr><?php endif; ?>
</tbody></table></div>
