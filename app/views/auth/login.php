<div class="row justify-content-center"><div class="col-md-5 col-lg-4">
<div class="card shadow-sm"><div class="card-body p-4">
<h1 class="h4 mb-3">Entrar</h1>
<form method="post" action="<?= e(url('login')) ?>"><?= csrf_field() ?>
  <div class="mb-3"><label class="form-label" for="email">E-mail</label><input class="form-control" type="email" id="email" name="email" required autofocus autocomplete="username"></div>
  <div class="mb-3"><label class="form-label" for="senha">Senha</label><input class="form-control" type="password" id="senha" name="senha" required autocomplete="current-password"></div>
  <button class="btn btn-success w-100">Entrar</button>
</form>
<p class="small mt-3 mb-0">Ainda não tem conta? <a href="<?= e(url('register')) ?>">Cadastre-se</a></p>
</div></div></div></div>
