<?php $pedir2 = (($_SESSION['pedir_2etapa'] ?? 0) == $p['id']) || (StockService::critico($p) && $p['status'] === 'recebido'); unset($_SESSION['pedir_2etapa']); ?>
<a class="small text-decoration-none d-inline-block mb-2" href="<?= e(url('pedidos')) ?>"><i class="bi bi-arrow-left"></i> Voltar para pedidos</a>
<h1 class="h3 mb-3">Pedido #<?= $p['id'] ?> <?= badge_status($p['status']) ?></h1>
<div class="row g-3">
 <div class="col-lg-7"><div class="card"><div class="card-body">
   <dl class="row mb-0">
     <?php if ($p['canal'] === 'mercadolivre'): ?><dt class="col-sm-4">Origem</dt><dd class="col-sm-8"><span class="badge text-bg-warning">Mercado Livre</span> venda #<?= e($p['pedido_externo_id']) ?></dd><?php endif; ?>
     <dt class="col-sm-4">Produto</dt><dd class="col-sm-8"><?= e($p['produto_nome']) ?></dd>
     <dt class="col-sm-4">Quantidade</dt><dd class="col-sm-8"><?= (int)$p['quantidade'] ?> × <?= money($p['preco_unitario']) ?> = <strong><?= money($p['preco_total']) ?></strong></dd>
     <dt class="col-sm-4">Vendedor</dt><dd class="col-sm-8"><?= e($p['vendedor_nome']) ?></dd>
     <dt class="col-sm-4">Fornecedor</dt><dd class="col-sm-8"><?= e($p['fornecedor_nome']) ?></dd>
     <dt class="col-sm-4">Entrega para</dt><dd class="col-sm-8"><?= e($p['cliente_nome']) ?><br><?= e($p['cliente_endereco']) ?> — <?= e($p['cliente_cidade']) ?>/<?= e($p['cliente_estado']) ?> · CEP <?= e($p['cliente_cep']) ?><br>Tel. <?= e($p['cliente_telefone']) ?></dd>
     <dt class="col-sm-4">Rastreio</dt><dd class="col-sm-8"><?= e($p['codigo_rastreio'] ?: '—') ?></dd>
     <dt class="col-sm-4">Recebido em</dt><dd class="col-sm-8"><?= e(date('d/m/Y H:i', strtotime($p['criado_em']))) ?><?= $p['confirmado_em'] ? ' · confirmado em ' . e(date('d/m/Y H:i', strtotime($p['confirmado_em']))) : '' ?></dd>
     <?php if ($p['observacoes']): ?><dt class="col-sm-4">Observações</dt><dd class="col-sm-8"><?= nl2br(e($p['observacoes'])) ?></dd><?php endif; ?>
   </dl></div></div>
   <p class="small text-muted mt-2">RN09: a WooDROP não se responsabiliza por atrasos ou divergências logísticas com o consumidor final.</p></div>
<?php if ($user['tipo'] === 'fornecedor' && $proximos): ?>
 <div class="col-lg-5"><div class="card"><div class="card-header">Atualizar status</div><div class="card-body">
  <form method="post" action="<?= e(url('pedidos/' . $p['id'] . '/status')) ?>"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="ns">Novo status</label><select class="form-select" id="ns" name="status" required><?php foreach ($proximos as $s): ?><option value="<?= $s ?>"><?= e(str_replace('_', ' ', $s)) ?></option><?php endforeach; ?></select></div>
    <div class="mb-3"><label class="form-label" for="cr">Código de rastreio (ao enviar)</label><input class="form-control" id="cr" name="codigo_rastreio" value="<?= e($p['codigo_rastreio']) ?>"></div>
    <?php if ($p['status'] === 'recebido'): ?>
    <p class="small">Estoque atual: <strong><?= (int)$p['estoque_atual'] ?></strong> (mín. <?= (int)$p['estoque_minimo'] ?>). Confirmar o pedido dá baixa automática (RN05).</p>
    <?php if ($pedir2): ?><div class="form-check mb-3 alert alert-warning py-2"><input class="form-check-input" type="checkbox" id="s2" name="segunda_etapa" value="1"><label class="form-check-label" for="s2"><strong>RN08 – 2ª etapa:</strong> confirmo que há estoque físico real para esta baixa.</label></div><?php endif; ?>
    <?php endif; ?>
    <button class="btn btn-success">Salvar</button></form></div></div></div>
<?php endif; ?></div>
