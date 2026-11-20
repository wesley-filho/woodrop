<h1 class="h3 mb-1">Catálogo de fornecedores parceiros</h1>
<p class="text-muted">Somente produtos de fornecedores com vínculo aprovado (RN01). O preço de venda não pode ser menor que o preço-base (RN04).</p>
<div class="row g-3">
<?php foreach ($produtos as $p): $sem = $p['estoque_atual'] <= 0; $anunciado = $p['anuncio_id'] && $p['anuncio_ativo']; ?>
  <div class="col-md-6 col-xl-4"><div class="card h-100">
    <?php if ($p['imagem_url']): ?><img src="<?= e(url($p['imagem_url'])) ?>" class="card-img-top wd-cover" alt="<?= e($p['nome']) ?>">
    <?php else: ?><div class="wd-cover-empty" aria-hidden="true"><i class="bi bi-box-seam"></i></div><?php endif; ?>
    <div class="card-body d-flex flex-column">
      <div class="d-flex justify-content-between gap-2"><h2 class="h6 mb-1"><?= e($p['nome']) ?></h2>
        <?= $anunciado ? '<span class="badge text-bg-success align-self-start">anunciado</span>' : '' ?></div>
      <div class="small text-muted mb-2"><?= e($p['fornecedor_nome']) ?> · <?= e($p['categoria_nome'] ?: 'Sem categoria') ?> · SKU <?= e($p['sku']) ?></div>
      <div class="small mb-1">Preço-base <strong><?= money($p['preco_base']) ?></strong><?= $p['preco_sugerido'] ? ' · sugerido ' . money($p['preco_sugerido']) : '' ?></div>
      <div class="small mb-3">Estoque: <span class="wd-stock fw-semibold" data-id="<?= $p['id'] ?>"><?= (int)$p['estoque_atual'] ?></span> un. <?= $sem ? '<span class="badge text-bg-danger">sem estoque</span>' : '' ?></div>
      <form class="mt-auto" method="post" action="<?= e(url('anuncios')) ?>"><?= csrf_field() ?><input type="hidden" name="produto_id" value="<?= $p['id'] ?>">
        <label class="form-label small mb-1" for="pv<?= $p['id'] ?>">Seu preço de venda</label>
        <div class="input-group"><span class="input-group-text">R$</span>
        <input class="form-control" id="pv<?= $p['id'] ?>" name="preco_venda" required inputmode="decimal" value="<?= e(decimal_br($p['preco_venda'] ?? $p['preco_sugerido'] ?? $p['preco_base'])) ?>">
        <button class="btn btn-success" <?= $sem ? 'disabled' : '' ?>><?= $p['anuncio_id'] ? 'Atualizar' : 'Anunciar' ?></button></div></form>
    </div></div></div>
<?php endforeach; if (!$produtos): ?>
  <div class="col-12"><div class="card"><div class="wd-empty"><i class="bi bi-shop"></i>Nenhum produto disponível ainda. Peça vínculo a um fornecedor em <a href="<?= e(url('vinculos')) ?>">Fornecedores</a>.</div></div></div>
<?php endif; ?>
</div>
