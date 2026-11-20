<section class="wd-hero rounded-4 text-white p-4 p-md-5 mb-4">
  <h1 class="display-5 fw-bold">Do Caparaó para o mundo, sem precisar de loja virtual.</h1>
  <p class="lead col-lg-8">A WooDROP conecta fornecedores locais a vendedores on-line pelo modelo de <strong>dropshipping</strong>: você cadastra seus produtos e quem entende de internet faz as vendas.</p>
  <a class="btn btn-warning btn-lg" href="<?= e(url('register')) ?>">Quero participar</a>
  <a class="btn btn-outline-light btn-lg ms-sm-2 mt-2 mt-sm-0" href="<?= e(url('login')) ?>">Já tenho conta</a>
  <p class="small mt-3 mb-0 text-white-50">Desenvolvedor? Veja a <a class="link-light" href="<?= e(url('api/docs')) ?>">documentação da API</a>.</p>
</section>
<div class="row g-3 mb-4 text-center">
  <div class="col-md-4"><div class="card h-100 p-3"><i class="bi bi-shop fs-1 text-success"></i><h2 class="h5">Fornecedores</h2><p class="mb-0">Cadastre produtos e estoque e alcance novos mercados sem gerir uma loja.</p></div></div>
  <div class="col-md-4"><div class="card h-100 p-3"><i class="bi bi-megaphone fs-1 text-primary"></i><h2 class="h5">Vendedores</h2><p class="mb-0">Amplie seu portfólio sem investir em estoque e integre sua loja (ex.: Shopify) via API.</p></div></div>
  <div class="col-md-4"><div class="card h-100 p-3"><i class="bi bi-graph-up-arrow fs-1 text-success"></i><h2 class="h5">Economia regional</h2><p class="mb-0">Mais renda e circulação de recursos no comércio do Caparaó.</p></div></div>
</div>
<h2 class="h4 text-center mb-1">Planos</h2>
<p class="text-center text-muted mb-3">Pagamento por período, com desconto nos planos mais longos.</p>
<div class="row g-4 justify-content-center mb-4 pt-2">
<?php foreach ($planos as $p): $popular = $p['nome'] === 'Plus'; ?>
  <div class="col-md-4"><div class="card h-100 text-center <?= $popular ? 'border-success border-2 wd-plan-popular' : '' ?>"><?= $popular ? '<span class="badge text-bg-success wd-ribbon">Mais escolhido</span>' : '' ?><div class="card-body d-flex flex-column">
    <h3 class="h5"><?= e($p['nome']) ?></h3><div class="display-6"><?= money(PlanoAssinatura::precoFinal($p)) ?></div>
    <div class="text-muted small mb-2"><?= e($p['periodo']) ?><?= $p['desconto_progressivo'] > 0 ? ' · ' . (int)$p['desconto_progressivo'] . '% de desconto' : '' ?></div>
    <p class="small"><?= e($p['descricao']) ?></p>
    <a class="btn <?= $popular ? 'btn-success' : 'btn-outline-success' ?> mt-auto" href="<?= e(url('register')) ?>">Começar</a></div></div></div>
<?php endforeach; ?>
</div>
