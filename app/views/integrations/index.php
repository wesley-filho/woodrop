<?php
$badgeMl = function (?string $s): string {
    $m = ['active' => ['success', 'ativo'], 'paused' => ['warning', 'pausado'], 'closed' => ['secondary', 'encerrado'], 'under_review' => ['info', 'em revisão'],
          'inactive' => ['secondary', 'inativo'], 'erro' => ['danger', 'erro']];
    [$cor, $txt] = $m[$s] ?? ['light', $s ?: 'não publicado'];
    return '<span class="badge text-bg-' . $cor . '">' . e($txt) . '</span>';
};
?>
<h1 class="h3 mb-3">Integrações</h1>

<div class="card mb-4"><div class="card-body">
  <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
    <h2 class="h5 mb-0 me-auto"><i class="bi bi-bag-check me-1 text-warning"></i>Mercado Livre</h2>
    <?php if ($ml): ?>
      <?= $ml['status'] === 'ativa' ? '<span class="badge text-bg-success">conectado</span>' : '<span class="badge text-bg-danger">reconexão necessária</span>' ?>
      <?php if ($ml['conta_teste']): ?><span class="badge text-bg-info">conta de teste</span><?php endif; ?>
      <?php if ($ml['user_products']): ?><span class="badge text-bg-light border">User Products</span><?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if (!$configurado): ?>
    <div class="alert alert-warning mb-0">A integração ainda não foi configurada. O administrador precisa criar o aplicativo no
      <a href="https://developers.mercadolivre.com.br/devcenter" target="_blank" rel="noopener">DevCenter do Mercado Livre</a> e preencher
      <code>ML_APP_ID</code>, <code>ML_CLIENT_SECRET</code> e <code>ML_REDIRECT_URI</code> no arquivo <code>.env</code> (veja o README).</div>
  <?php elseif (!$ml): ?>
    <p class="mb-3">Conecte sua conta para publicar seus anúncios no Mercado Livre. As vendas pagas chegam como pedidos para o fornecedor, e o estoque
      é atualizado sozinho quando o fornecedor confirma, cancela ou ajusta o estoque.</p>
    <a class="btn btn-warning" href="<?= e(url('integracoes/mercadolivre/conectar')) ?>"><i class="bi bi-plug me-1"></i>Conectar conta do Mercado Livre</a>
    <p class="small text-muted mt-2 mb-0">Para testes, entre no Mercado Livre com um <strong>usuário de teste</strong> antes de conectar (veja o README).</p>
  <?php else: ?>
    <p class="mb-2">Conta: <strong><?= e($ml['conta_nome'] ?: $ml['conta_id']) ?></strong> <span class="text-muted small">(ID <?= e($ml['conta_id']) ?>)</span></p>
    <?php if ($ml['conta_teste']): ?><div class="alert alert-info small py-2">Conta de teste: os anúncios saem com o título obrigatório “<?= e(MercadoLivreService::TITULO_TESTE) ?>”
      e só podem ser comprados por outros usuários de teste.</div><?php endif; ?>
    <?php if ($ml['status'] !== 'ativa'): ?><div class="alert alert-danger small py-2">A autorização expirou ou foi revogada. Conecte a conta novamente.</div><?php endif; ?>
    <div class="d-flex flex-wrap gap-2">
      <form method="post" action="<?= e(url('integracoes/mercadolivre/importar')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Importar vendas agora</button></form>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('integracoes/mercadolivre/conectar')) ?>"><i class="bi bi-arrow-repeat me-1"></i>Reconectar</a>
      <form method="post" action="<?= e(url('integracoes/mercadolivre/desconectar')) ?>" onsubmit="return confirm('Desconectar a conta do Mercado Livre?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Desconectar</button></form>
    </div>
  <?php endif; ?>
</div></div>

<?php if ($ml): ?>
<h2 class="h5">Seus anúncios no Mercado Livre</h2>
<div class="table-responsive card"><table class="table align-middle mb-0"><thead><tr><th>Produto</th><th>Preço de venda</th><th>Disponível</th><th>Mercado Livre</th><th></th></tr></thead><tbody>
<?php foreach ($anuncios as $a): $disp = MercadoLivreService::disponivel((int)$a['produto_id'], (int)$a['estoque_atual']); ?><tr>
  <td><?= e($a['produto_nome']) ?><div class="small text-muted"><?= e($a['fornecedor_nome']) ?><?= !$a['ativo'] || !$a['produto_ativo'] ? ' · <span class="text-danger">anúncio ou produto inativo</span>' : '' ?></div></td>
  <td><?= money($a['preco_venda']) ?></td>
  <td><?= $disp ?></td>
  <td><?= $badgeMl($a['item_id'] ? $a['ml_status'] : ($a['ml_status'] === 'erro' ? 'erro' : null)) ?>
    <?php if ($a['item_id']): ?><div class="small"><?= $a['permalink'] ? '<a href="' . e($a['permalink']) . '" target="_blank" rel="noopener">' . e($a['item_id']) . ' <i class="bi bi-box-arrow-up-right"></i></a>' : e($a['item_id']) ?></div><?php endif; ?>
    <?php if ($a['erro']): ?><div class="small text-danger" style="max-width:420px"><?= e($a['erro']) ?></div><?php endif; ?></td>
  <td>
  <?php if (!$a['item_id']): ?>
    <form class="d-flex gap-1" method="post" action="<?= e(url('integracoes/mercadolivre/anuncios/' . $a['id'] . '/publicar')) ?>"><?= csrf_field() ?>
      <label class="visually-hidden" for="cat<?= $a['id'] ?>">Categoria do Mercado Livre</label>
      <input class="form-control form-control-sm" style="width:150px" id="cat<?= $a['id'] ?>" name="categoria_id" value="<?= e($a['categoria_id'] ?? '') ?>" placeholder="Categoria (opcional)" pattern="[Mm][Ll][Bb][0-9]+" title="Código da categoria, ex.: MLB3530">
      <button class="btn btn-sm btn-warning text-nowrap" <?= $disp < 1 || !$a['ativo'] || !$a['produto_ativo'] || $ml['status'] !== 'ativa' ? 'disabled' : '' ?>>Publicar</button></form>
  <?php else: ?>
    <form method="post" action="<?= e(url('integracoes/mercadolivre/anuncios/' . $a['id'] . '/sincronizar')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary text-nowrap" <?= $ml['status'] !== 'ativa' ? 'disabled' : '' ?>>Sincronizar</button></form>
  <?php endif; ?></td></tr>
<?php endforeach; if (!$anuncios): ?><tr><td colspan="5" class="text-center text-muted py-4">Nenhum anúncio. Crie anúncios a partir do <a href="<?= e(url('catalogo')) ?>">catálogo</a>.</td></tr><?php endif; ?>
</tbody></table></div>
<p class="small text-muted mt-2">Sem categoria, contas de teste usam “Outros” (MLB3530) e contas reais usam o preditor de categorias do Mercado Livre. O estoque enviado é o estoque do fornecedor menos os pedidos ainda não confirmados.</p>
<?php endif; ?>
