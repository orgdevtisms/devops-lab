<?php
// Uso: php bin/criar_admin.php "Nome" email@dominio.com
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../src/bootstrap.php';
[$_, $nome, $email] = $argv + [null, null, null];
if (!$nome || !$email) exit("Uso: php bin/criar_admin.php \"Nome\" email\n");
echo "Senha (mín. 10 caracteres): "; system('stty -echo'); $s = trim(fgets(STDIN)); system('stty echo'); echo "\n";
if (strlen($s) < 10) exit("Senha curta.\n");
db()->prepare('INSERT INTO usuarios (nome,email,senha_hash) VALUES (?,?,?)')->execute([$nome, $email, password_hash($s, PASSWORD_DEFAULT)]);
$id = db()->lastInsertId();
db()->prepare("INSERT INTO usuario_papeis SELECT ?, id FROM papeis WHERE nome='admin'")->execute([$id]);
echo "Admin criado (id $id).\n";
