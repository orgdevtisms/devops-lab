<?php
declare(strict_types=1);

function cfg(string $k) { static $c; $c ??= require __DIR__.'/../config/config.php'; return $c[$k]; }
date_default_timezone_set(cfg('app_tz'));

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
session_name(cfg('session_name'));
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Strict']);
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

function db(): PDO {
  static $p = null;
  if (!$p) {
    $d = cfg('db');
    $p = new PDO("mysql:host={$d['host']};dbname={$d['name']};charset={$d['charset']}", $d['user'], $d['pass'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,   // prepared statements reais
    ]);
  }
  return $p;
}

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ---------- IP e local ----------
function client_ip(): string {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  if (in_array($ip, cfg('trusted_proxies'), true) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $cand = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    if (filter_var($cand, FILTER_VALIDATE_IP)) $ip = $cand;
  }
  return $ip;
}
function geo_lookup(string $ip): string {
  if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return 'Rede local';
  if (!cfg('geo')) return 'Não identificado';
  $ctx = stream_context_create(['http'=>['timeout'=>2]]);
  $j = @file_get_contents("http://ip-api.com/json/".urlencode($ip)."?fields=status,city,regionName,country&lang=pt-BR", false, $ctx);
  $d = $j ? json_decode($j, true) : null;
  return ($d && ($d['status'] ?? '') === 'success') ? "{$d['city']}/{$d['regionName']} - {$d['country']}" : 'Não identificado';
}

// ---------- Autenticação e RBAC ----------
function user(): ?array {
  if (empty($_SESSION['u'])) return null;
  if (time() - ($_SESSION['last'] ?? 0) > cfg('idle_timeout')) { $_SESSION = []; session_destroy(); return null; }
  $_SESSION['last'] = time();
  return $_SESSION['u'];
}
function can(string $perm): bool { return in_array($perm, $_SESSION['perms'] ?? [], true); }

function log_acesso(?int $uid, string $email, bool $ok, string $local): void {
  $st = db()->prepare('INSERT INTO log_acesso (usuario_id,email_tentado,ip,local_aprox,user_agent,sucesso) VALUES (?,?,?,?,?,?)');
  $st->execute([$uid, mb_substr($email,0,150), client_ip(), $local, mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,255), (int)$ok]);
}

function login(string $email, string $senha): string {
  $ip = client_ip();
  $st = db()->prepare('SELECT COUNT(*) FROM log_acesso WHERE ip=? AND sucesso=0 AND criado_em > NOW() - INTERVAL 15 MINUTE');
  $st->execute([$ip]);
  if ((int)$st->fetchColumn() >= 5) return 'Muitas tentativas. Aguarde 15 minutos.';

  $st = db()->prepare('SELECT id,nome,email,senha_hash FROM usuarios WHERE email=? AND ativo=1');
  $st->execute([$email]);
  $u = $st->fetch();
  $dummy = password_hash('dummy', PASSWORD_DEFAULT);   // iguala o tempo de resposta p/ usuário inexistente
  $ok = password_verify($senha, $u['senha_hash'] ?? $dummy) && $u;
  $local = geo_lookup($ip);
  if (!$ok) { log_acesso(null, $email, false, $local); return 'Credenciais inválidas.'; }

  session_regenerate_id(true);
  $p = db()->prepare('SELECT DISTINCT pm.codigo FROM usuario_papeis up
    JOIN papel_permissoes pp ON pp.papel_id=up.papel_id JOIN permissoes pm ON pm.id=pp.permissao_id WHERE up.usuario_id=?');
  $p->execute([$u['id']]);
  $_SESSION = ['perms'=>$p->fetchAll(PDO::FETCH_COLUMN),
    'u'=>['id'=>(int)$u['id'],'nome'=>$u['nome'],'email'=>$u['email']],
    'ip'=>$ip,'local'=>$local,'csrf'=>bin2hex(random_bytes(32)),'last'=>time()];
  log_acesso((int)$u['id'], $email, true, $local);
  db()->prepare('UPDATE usuarios SET ultimo_login=NOW() WHERE id=?')->execute([$u['id']]);
  return '';
}

function audit(string $acao, string $recurso, ?int $id, ?string $detalhe = null): void {
  $st = db()->prepare('INSERT INTO auditoria (usuario_id,ip,local_aprox,acao,recurso,registro_id,detalhe) VALUES (?,?,?,?,?,?,?)');
  $st->execute([$_SESSION['u']['id'] ?? null, $_SESSION['ip'] ?? client_ip(), $_SESSION['local'] ?? null, $acao, $recurso, $id, $detalhe]);
}

// ---------- Validadores (espelham o JS) ----------
function only_digits(string $s): string { return preg_replace('/\D/', '', $s); }
function valid_cpf(string $c): bool {
  $c = only_digits($c);
  if (strlen($c) !== 11 || preg_match('/^(\d)\1{10}$/', $c)) return false;
  for ($t = 9; $t < 11; $t++) {
    $s = 0; for ($i = 0; $i < $t; $i++) $s += (int)$c[$i] * (($t + 1) - $i);
    if ((int)$c[$t] !== ((10 * $s) % 11) % 10) return false;
  }
  return true;
}
function valid_cnpj(string $c): bool {
  $c = only_digits($c);
  if (strlen($c) !== 14 || preg_match('/^(\d)\1{13}$/', $c)) return false;
  foreach ([12, 13] as $t) {
    $w = $t === 12 ? [5,4,3,2,9,8,7,6,5,4,3,2] : [6,5,4,3,2,9,8,7,6,5,4,3,2];
    $s = 0; foreach ($w as $i => $p) $s += (int)$c[$i] * $p;
    $d = $s % 11 < 2 ? 0 : 11 - $s % 11;
    if ((int)$c[$t] !== $d) return false;
  }
  return true;
}
function valid_date(string $d): bool {
  return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}
function valid_phone(string $p): bool { return (bool)preg_match('/^[1-9][1-9](9\d{8}|[2-5]\d{7})$/', only_digits($p)); }
function valid_placa(string $p): bool { return (bool)preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $p); }
