<?php $scripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script><script>
const M=' . json_encode($mensal) . ', T=' . json_encode($top) . ';
new Chart(document.getElementById("cVendas"),{type:"bar",data:{labels:M.map(x=>x.mes),datasets:[{label:"Vendas (R$)",data:M.map(x=>+x.total),backgroundColor:"#2E7D32"}]}});
new Chart(document.getElementById("cTop"),{type:"bar",data:{labels:T.map(x=>x.nome),datasets:[{label:"Unidades vendidas",data:T.map(x=>+x.qtd),backgroundColor:"#1565C0"}]},options:{indexAxis:"y"}});
</script>'; ?>
<h1 class="h3 mb-3">Relatórios</h1>
<div class="row g-3 mb-4">
  <div class="col-lg-6"><div class="card"><div class="card-header">Vendas dos últimos 6 meses</div><div class="card-body"><canvas id="cVendas" height="200" role="img" aria-label="Gráfico de vendas por mês"></canvas></div></div></div>
  <div class="col-lg-6"><div class="card"><div class="card-header">Produtos mais vendidos</div><div class="card-body"><canvas id="cTop" height="200" role="img" aria-label="Gráfico de produtos mais vendidos"></canvas></div></div></div>
</div>
<div class="card"><div class="card-header">Desempenho de vendedores</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Vendedor</th><th>Pedidos</th><th>Total</th></tr></thead><tbody>
<?php foreach ($vendedores as $v): ?><tr><td><?= e($v['nome']) ?></td><td><?= (int)$v['pedidos'] ?></td><td><?= money($v['total']) ?></td></tr><?php endforeach; if (!$vendedores): ?><tr><td colspan="3" class="text-muted text-center py-3">Sem dados.</td></tr><?php endif; ?></tbody></table></div></div>
