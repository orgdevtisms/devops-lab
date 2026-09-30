<?php
require __DIR__.'/../src/bootstrap.php';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['login_csrf'] ?? '', $_POST['t'] ?? '')) $erro = 'Sessão expirada, tente novamente.';
  else { $erro = login(trim($_POST['email'] ?? ''), $_POST['senha'] ?? ''); if (!$erro) { header('Location: index.php'); exit; } }
}
$_SESSION['login_csrf'] = bin2hex(random_bytes(16));
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Frota - Login</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh"><div class="container" style="max-width:380px">
<div class="card shadow-sm"><div class="card-body p-4"><h1 class="h4 mb-3">🚚 Gestão de Frota</h1>
<?php if ($erro): ?><div class="alert alert-danger py-2"><?= h($erro) ?></div><?php endif; ?>
<form method="post" autocomplete="off"><input type="hidden" name="t" value="<?= h($_SESSION['login_csrf']) ?>">
<div class="mb-3"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" required autofocus></div>
<div class="mb-3"><label class="form-label">Senha</label><input class="form-control" type="password" name="senha" required></div>
<button class="btn btn-primary w-100">Entrar</button></form></div></div></div></body></html>
