<?php $p = $p ?? []; $v = fn($k, $d = '') => e($p[$k] ?? $d); ?>
<a class="small text-decoration-none d-inline-block mb-2" href="<?= e(url('produtos')) ?>"><i class="bi bi-arrow-left"></i> Voltar para meus produtos</a>
<h1 class="h3 mb-3"><?= e($title) ?></h1>
<form method="post" enctype="multipart/form-data" class="card" style="max-width:960px" action="<?= e(url($p ? 'produtos/' . $p['id'] . '/editar' : 'produtos/novo')) ?>"><?= csrf_field() ?>
<div class="card-body row g-3">
  <h2 class="h6 text-muted text-uppercase small mb-0">Informações</h2>
  <div class="col-md-8"><label class="form-label" for="nome">Nome</label><input class="form-control" id="nome" name="nome" required maxlength="200" value="<?= $v('nome') ?>"></div>
  <div class="col-md-4"><label class="form-label" for="sku">SKU <span class="text-muted small">(vazio = automático)</span></label><input class="form-control" id="sku" name="sku" maxlength="60" value="<?= $v('sku') ?>"></div>
  <div class="col-12"><label class="form-label" for="desc">Descrição</label><textarea class="form-control" id="desc" name="descricao" rows="3"><?= $v('descricao') ?></textarea></div>
  <div class="col-md-4"><label class="form-label" for="cat">Categoria</label><select class="form-select" id="cat" name="categoria_id"><option value="">—</option>
    <?php foreach ($categorias as $c): ?><option value="<?= $c['id'] ?>" <?= ($p['categoria_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?></select></div>

  <h2 class="h6 text-muted text-uppercase small mb-0 mt-4">Preço</h2>
  <div class="col-md-4"><label class="form-label" for="pb">Preço-base</label><div class="input-group"><span class="input-group-text">R$</span><input class="form-control" id="pb" name="preco_base" required inputmode="decimal" value="<?= e(decimal_br($p['preco_base'] ?? '')) ?>" aria-describedby="pb-ajuda"></div>
    <div class="form-text" id="pb-ajuda">Valor que você recebe por unidade.</div></div>
  <div class="col-md-4"><label class="form-label" for="ps">Preço sugerido de venda</label><div class="input-group"><span class="input-group-text">R$</span><input class="form-control" id="ps" name="preco_sugerido" inputmode="decimal" value="<?= e(decimal_br($p['preco_sugerido'] ?? '')) ?>"></div></div>

  <h2 class="h6 text-muted text-uppercase small mb-0 mt-4">Estoque e envio</h2>
  <div class="col-6 col-md-4"><label class="form-label" for="ea">Estoque atual</label><div class="input-group"><input class="form-control" type="number" min="0" id="ea" name="estoque_atual" value="<?= $v('estoque_atual', 0) ?>"><span class="input-group-text">un.</span></div></div>
  <div class="col-6 col-md-4"><label class="form-label" for="em">Estoque mínimo</label><div class="input-group"><input class="form-control" type="number" min="0" id="em" name="estoque_minimo" value="<?= $v('estoque_minimo', ESTOQUE_MIN_PADRAO) ?>" aria-describedby="em-ajuda"><span class="input-group-text">un.</span></div>
    <div class="form-text" id="em-ajuda">Abaixo disso você recebe um alerta.</div></div>
  <div class="col-md-4"><label class="form-label" for="pk">Peso</label><div class="input-group"><input class="form-control" id="pk" name="peso_kg" inputmode="decimal" value="<?= e(isset($p['peso_kg']) && $p['peso_kg'] !== null ? rtrim(rtrim(number_format((float)$p['peso_kg'], 3, ',', ''), '0'), ',') : '') ?>"><span class="input-group-text">kg</span></div></div>

  <h2 class="h6 text-muted text-uppercase small mb-0 mt-4">Imagem e situação</h2>
  <div class="col-md-8"><label class="form-label" for="img">Imagem <span class="text-muted small">(JPG, PNG ou WEBP, até 2 MB)</span></label>
    <?php if (!empty($p['imagem_url'])): ?><div class="mb-2"><img src="<?= e(url($p['imagem_url'])) ?>" alt="Imagem atual do produto" class="wd-preview"></div><?php endif; ?>
    <input class="form-control" type="file" id="img" name="imagem" accept="image/jpeg,image/png,image/webp"></div>
  <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="at" name="ativo" <?= ($p['ativo'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label" for="at">Produto ativo (visível para vendedores)</label></div></div>
</div>
<div class="card-footer bg-white d-flex gap-2"><button class="btn btn-success"><i class="bi bi-check-lg"></i> Salvar</button> <a class="btn btn-link" href="<?= e(url('produtos')) ?>">Cancelar</a></div>
</form>
