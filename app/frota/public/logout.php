<?php
require __DIR__.'/../src/bootstrap.php';
if (user()) audit('logout', 'sessao', null);
$_SESSION = []; session_destroy();
header('Location: login.php');
