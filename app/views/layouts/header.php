<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'WooDROP') ?></title>
<link rel="icon" type="image/png" href="<?= e(url('assets/img/logo.png')) ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100" data-base="<?= e(base_path()) ?>" data-csrf="<?= e(csrf_token()) ?>">
<a class="visually-hidden-focusable" href="#conteudo">Ir para o conteúdo</a>
<nav class="navbar navbar-expand navbar-dark wd-nav sticky-top">
  <div class="container-fluid">
    <?php if ($user): ?><button class="btn btn-outline-light wd-menu-btn d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Abrir menu"><i class="bi bi-list fs-5"></i></button><?php endif; ?>
    <a class="navbar-brand fw-bold" href="<?= e(url('')) ?>"><img src="<?= e(url('assets/img/logo.png')) ?>" alt="" width="28" height="28" class="me-1">Woo<span class="text-warning">DROP</span></a>
    <ul class="navbar-nav ms-auto align-items-center">
    <?php if ($user): ?>
      <li class="nav-item dropdown me-2">
        <a class="nav-link position-relative" href="#" id="bell" data-bs-toggle="dropdown" aria-label="Notificações"><i class="bi bi-bell-fill"></i>
          <span id="bell-count" class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle d-none">0</span></a>
        <ul class="dropdown-menu dropdown-menu-end p-2" id="bell-list" style="min-width:300px"><li class="text-muted small px-2">Carregando…</li></ul>
      </li>
      <li class="nav-item text-white small d-none d-sm-block me-2"><?= e($user['nome']) ?> <span class="badge text-bg-light"><?= e($user['tipo']) ?></span></li>
      <li class="nav-item"><a class="btn btn-sm btn-outline-light" href="<?= e(url('logout')) ?>">Sair</a></li>
    <?php else: ?>
      <li class="nav-item"><a class="nav-link" href="<?= e(url('login')) ?>">Entrar</a></li>
      <li class="nav-item"><a class="btn btn-sm btn-warning ms-2" href="<?= e(url('register')) ?>">Cadastre-se</a></li>
    <?php endif; ?>
    </ul>
  </div>
</nav>
<div class="container-fluid flex-grow-1 d-flex flex-column"><div class="row flex-grow-1">
<?php if ($user) view('layouts/sidebar'); ?>
<main id="conteudo" class="col py-4 px-3 px-md-4">
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:2000" id="toasts">
<?php foreach (flash() ?? [] as $f): ?>
  <div class="toast align-items-center text-bg-<?= e($f['t']) ?> border-0" role="alert" data-bs-delay="6000"><div class="d-flex"><div class="toast-body"><?= e($f['m']) ?></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button></div></div>
<?php endforeach; ?>
</div>
