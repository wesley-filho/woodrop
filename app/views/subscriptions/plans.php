<h1 class="h3 mb-3">Assinatura</h1>
<?php if ($ativa): ?><div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-patch-check-fill"></i><span>Plano <strong><?= e($ativa['plano_nome']) ?></strong> (<?= e($ativa['periodo']) ?>) ativo até <strong><?= e(date('d/m/Y', strtotime($ativa['fim']))) ?></strong>.</span></div>
<?php elseif ($user['tipo'] !== 'admin'): ?><div class="alert alert-warning">Você não possui assinatura ativa (RN06). Escolha um plano para acessar a plataforma.</div><?php endif; ?>
<?php if ($user['tipo'] !== 'admin'): ?>
<div class="row g-4 mb-4 pt-2">
<?php foreach ($planos as $p): $atual = $ativa && (int)$ativa['plano_id'] === (int)$p['id']; $popular = $p['nome'] === 'Plus'; ?>
 <div class="col-md-4"><div class="card h-100 <?= $atual ? 'border-primary border-2' : ($popular ? 'border-success border-2 wd-plan-popular' : '') ?>">
   <?php if ($popular && !$atual): ?><span class="badge text-bg-success wd-ribbon">Mais escolhido</span><?php endif; ?>
   <div class="card-body text-center d-flex flex-column">
   <h2 class="h5"><?= e($p['nome']) ?> <?= $atual ? '<span class="badge text-bg-primary align-middle">seu plano</span>' : '' ?></h2><div class="display-6"><?= money(PlanoAssinatura::precoFinal($p)) ?></div>
   <div class="small text-muted mb-2"><?= e($p['periodo']) ?><?php if ($p['desconto_progressivo'] > 0): ?> · <del><?= money($p['preco_base']) ?></del> (−<?= (int)$p['desconto_progressivo'] ?>%)<?php endif; ?></div>
   <p class="small"><?= e($p['descricao']) ?></p>
   <form class="mt-auto" method="post" action="<?= e(url('assinatura/contratar')) ?>" <?= $ativa && !$atual ? 'data-confirm="' . e('Você já tem o plano ' . $ativa['plano_nome'] . ' ativo até ' . date('d/m/Y', strtotime($ativa['fim'])) . '. Contratar o plano ' . $p['nome'] . ' substitui o plano atual agora. Continuar?') . '"' : '' ?>><?= csrf_field() ?><input type="hidden" name="plano_id" value="<?= $p['id'] ?>">
     <?php if (!$atual): ?><label class="visually-hidden" for="m<?= $p['id'] ?>">Forma de pagamento</label><select class="form-select form-select-sm mb-2" id="m<?= $p['id'] ?>" name="metodo"><option value="pix">PIX</option><option value="cartao">Cartão</option><option value="boleto">Boleto</option></select><?php endif; ?>
     <?php if ($atual): ?><button class="btn btn-outline-primary w-100" type="button" disabled>Plano atual</button>
     <?php else: ?><button class="btn btn-success w-100"><?= $ativa ? 'Trocar para este plano' : 'Contratar' ?></button><?php endif; ?></form></div></div></div>
<?php endforeach; ?></div>
<p class="small text-muted">Pagamento simulado nesta versão; a integração com gateway é trabalho futuro.</p>
<?php endif; ?>
<?php if ($historico): ?><h2 class="h5">Histórico</h2><ul class="list-group"><?php foreach ($historico as $h): ?><li class="list-group-item d-flex justify-content-between"><span><?= e($h['plano_nome']) ?> — <?= e(date('d/m/Y', strtotime($h['inicio']))) ?> a <?= e(date('d/m/Y', strtotime($h['fim']))) ?></span><?= $h['ativo'] ? '<span class="badge text-bg-success align-self-center">ativo</span>' : '' ?></li><?php endforeach; ?></ul><?php endif; ?>
