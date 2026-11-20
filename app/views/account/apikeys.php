<h1 class="h3 mb-3">API Keys</h1>
<?php if ($nova): ?><div class="alert alert-warning"><strong>Copie agora — a chave não será exibida novamente:</strong><br><code class="user-select-all"><?= e($nova) ?></code></div><?php endif; ?>
<div class="row g-3"><div class="col-lg-7">
<form class="input-group mb-3" method="post" action="<?= e(url('apikeys/gerar')) ?>"><?= csrf_field() ?><label class="visually-hidden" for="d">Descrição</label><input class="form-control" id="d" name="descricao" placeholder="Descrição (ex.: Loja Shopify)" maxlength="100"><button class="btn btn-success">Gerar chave</button></form>
<div class="table-responsive card"><table class="table align-middle mb-0"><thead><tr><th>Descrição</th><th>Criada</th><th>Último uso</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($keys as $k): ?><tr><td><?= e($k['descricao']) ?></td><td><?= e(date('d/m/Y', strtotime($k['criado_em']))) ?></td><td><?= $k['ultimo_uso'] ? e(date('d/m/Y H:i', strtotime($k['ultimo_uso']))) : '—' ?></td><td><?= $k['ativa'] ? 'ativa' : 'revogada' ?></td>
  <td><?php if ($k['ativa']): ?><form method="post" action="<?= e(url('apikeys/' . $k['id'] . '/revogar')) ?>" data-confirm="Revogar esta chave?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Revogar</button></form><?php endif; ?></td></tr>
<?php endforeach; if (!$keys): ?><tr><td colspan="5" class="text-center text-muted py-3">Nenhuma chave.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="col-lg-5"><div class="card"><div class="card-body"><h2 class="h6">Como usar</h2>
<pre class="small mb-2">curl <?= e(APP_URL) ?>/api/v1/products \
  -H "Authorization: Bearer SUA_CHAVE"</pre>
<a href="<?= e(url('api/docs')) ?>" target="_blank" rel="noopener">Documentação completa (Swagger)</a></div></div></div></div>
