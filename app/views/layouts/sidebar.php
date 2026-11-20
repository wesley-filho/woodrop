<?php
$t = $user['tipo'];
$menu = ['fornecedor' => [['dashboard', 'speedometer2', 'Painel'], ['produtos', 'box-seam', 'Meus produtos'], ['vinculos', 'people', 'Vínculos'], ['pedidos', 'truck', 'Pedidos'], ['relatorios', 'graph-up', 'Relatórios'], ['apikeys', 'key', 'API Keys'], ['assinatura', 'credit-card', 'Assinatura']],
         'vendedor' => [['dashboard', 'speedometer2', 'Painel'], ['vinculos', 'people', 'Fornecedores'], ['catalogo', 'shop', 'Catálogo'], ['anuncios', 'megaphone', 'Meus anúncios'], ['integracoes', 'plug', 'Integrações'], ['pedidos', 'truck', 'Pedidos'], ['apikeys', 'key', 'API Keys'], ['assinatura', 'credit-card', 'Assinatura']],
         'admin' => [['dashboard', 'speedometer2', 'Painel'], ['admin/usuarios', 'people', 'Usuários e planos'], ['pedidos', 'truck', 'Pedidos'], ['relatorios', 'graph-up', 'Relatórios']]][$t];
// Página atual sem o prefixo da instalação: "pedidos/12" também destaca "Pedidos".
$atual = trim(substr((string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(base_path())), '/');
$bloqueado = $t === 'fornecedor' && !SubscriptionService::recursoAvancado((int)$user['id']); // relatórios só nos planos Plus/Premium
?>
<div class="offcanvas-md offcanvas-start col-md-3 col-lg-2 bg-white border-end p-0" tabindex="-1" id="sidebar" aria-labelledby="sidebar-titulo">
  <div class="offcanvas-header d-md-none border-bottom">
    <div><h2 class="offcanvas-title h6 mb-0" id="sidebar-titulo"><?= e($user['nome']) ?></h2><span class="badge text-bg-light border"><?= e($t) ?></span></div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Fechar menu"></button>
  </div>
  <div class="wd-sidebar-inner w-100">
  <nav class="nav flex-column p-2 gap-1" aria-label="Menu principal">
  <?php foreach ($menu as [$href, $ic, $lb]): $ativo = $atual === $href || str_starts_with($atual, $href . '/'); ?>
    <a class="nav-link wd-link <?= $ativo ? 'active' : '' ?>" href="<?= e(url($href)) ?>" <?= $ativo ? 'aria-current="page"' : '' ?>><i class="bi bi-<?= $ic ?> me-2"></i><?= e($lb) ?>
      <?php if ($href === 'relatorios' && $bloqueado): ?><span class="badge text-bg-warning ms-auto" title="Disponível nos planos Plus e Premium"><i class="bi bi-lock-fill"></i> Plus</span><?php endif; ?></a>
  <?php endforeach; ?>
  </nav>
  </div>
</div>
