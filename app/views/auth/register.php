<div class="row justify-content-center"><div class="col-lg-7">
<div class="card shadow-sm"><div class="card-body p-4">
<h1 class="h4 mb-3">Criar conta</h1>
<form method="post" action="<?= e(url('register')) ?>" class="row g-3"><?= csrf_field() ?>
  <div class="col-12"><label class="form-label" for="tipo">Eu sou</label>
    <select class="form-select" id="tipo" name="tipo" required><option value="fornecedor">Fornecedor (tenho produtos/estoque)</option><option value="vendedor">Vendedor on-line (quero anunciar produtos)</option></select></div>
  <div class="col-md-7"><label class="form-label" for="nome">Nome / Razão social</label><input class="form-control" id="nome" name="nome" required autocomplete="name"></div>
  <div class="col-md-5"><label class="form-label" for="cpf">CPF ou CNPJ</label><input class="form-control" id="cpf" name="cpf_cnpj" required inputmode="numeric"></div>
  <div class="col-md-7"><label class="form-label" for="em">E-mail</label><input class="form-control" type="email" id="em" name="email" required autocomplete="email"></div>
  <div class="col-md-5"><label class="form-label" for="tel">Telefone</label><input class="form-control" id="tel" name="telefone" type="tel" inputmode="tel" autocomplete="tel" placeholder="(28) 99999-0000"></div>
  <div class="col-md-8"><label class="form-label" for="end">Endereço</label><input class="form-control" id="end" name="endereco" autocomplete="street-address"></div>
  <div class="col-md-4"><label class="form-label" for="cep">CEP</label><input class="form-control" id="cep" name="cep" inputmode="numeric" autocomplete="postal-code" placeholder="00000-000"></div>
  <div class="col-md-8"><label class="form-label" for="cid">Cidade</label><input class="form-control" id="cid" name="cidade" autocomplete="address-level2"></div>
  <div class="col-md-4"><label class="form-label" for="uf">UF</label><input class="form-control" id="uf" name="estado" maxlength="2" value="ES" autocomplete="address-level1" style="text-transform:uppercase"></div>
  <div class="col-md-6"><label class="form-label" for="pw">Senha (mín. 8 caracteres)</label><input class="form-control" type="password" id="pw" name="senha" minlength="8" required autocomplete="new-password"></div>
  <div class="col-12 small text-muted">Ao se cadastrar você concorda que a WooDROP não se responsabiliza pela logística ou pelo relacionamento com o consumidor final (RN09).</div>
  <div class="col-12 d-flex flex-wrap align-items-center gap-3"><button class="btn btn-success">Criar conta</button><span class="small">Já tem conta? <a href="<?= e(url('login')) ?>">Entrar</a></span></div>
</form></div></div></div></div>
