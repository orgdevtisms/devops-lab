<?php require __DIR__.'/../src/bootstrap.php'; if (!user()) { header('Location: login.php'); exit; } ?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gestão de Frota</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet"></head>
<body>
<nav class="navbar navbar-dark bg-dark px-3">
  <span class="navbar-brand">🚚 Gestão de Frota</span>
  <span class="text-light small ms-auto me-3" id="who"></span>
  <a class="btn btn-sm btn-outline-light" href="logout.php">Sair</a>
</nav>
<div class="container-fluid"><div class="row">
  <aside class="col-md-2 bg-white border-end py-3 min-vh-100"><div class="nav flex-column nav-pills" id="menu"></div></aside>
  <main class="col-md-10 p-4" id="view"></main>
</div></div>
<div class="toast-container position-fixed bottom-0 end-0 p-3"><div id="toast" class="toast text-bg-dark"><div class="toast-body"></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/validators.js"></script>
<script src="assets/app.js"></script>
</body></html>
