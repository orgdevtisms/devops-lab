<?php
require __DIR__.'/../src/bootstrap.php';
require __DIR__.'/../src/resources.php';
header('Content-Type: application/json; charset=utf-8');

function out($d, int $c = 200) { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function need(string $perm) { if (!can($perm)) out(['erro'=>'Sem permissão'], 403); }

if (!user()) out(['erro'=>'Não autenticado'], 401);
$r = $_GET['r'] ?? ''; $a = $_GET['a'] ?? 'list';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))
  out(['erro'=>'Token CSRF inválido'], 403);

try {
  if ($r === 'me') out(['user'=>$_SESSION['u'],'perms'=>$_SESSION['perms'],'csrf'=>$_SESSION['csrf'],'ip'=>$_SESSION['ip'],'local'=>$_SESSION['local']]);

  if ($r === 'dashboard') {
    need('dashboard.ver');
    $pdo = db();
    $man = $pdo->query("SELECT m.id, v.placa, v.modelo, m.tipo, m.proxima_data, m.proxima_km, v.km_atual,
        CASE WHEN m.proxima_data IS NOT NULL AND m.proxima_data < CURDATE() THEN 'vencida'
             WHEN m.proxima_km IS NOT NULL AND v.km_atual >= m.proxima_km THEN 'vencida' ELSE 'proxima' END AS situacao
      FROM manutencoes m JOIN veiculos v ON v.id=m.veiculo_id
      WHERE v.status<>'inativo' AND m.status='concluida'
        AND ((m.proxima_data IS NOT NULL AND m.proxima_data <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
          OR (m.proxima_km IS NOT NULL AND m.proxima_km - v.km_atual <= 1000))
        AND NOT EXISTS (SELECT 1 FROM manutencoes m2 WHERE m2.veiculo_id=m.veiculo_id AND m2.tipo=m.tipo
                        AND m2.status='concluida' AND (m2.data_manutencao > m.data_manutencao OR (m2.data_manutencao=m.data_manutencao AND m2.id>m.id)))
      ORDER BY m.proxima_data")->fetchAll();
    $agend = $pdo->query("SELECT m.id, v.placa, v.modelo, m.tipo, m.data_manutencao FROM manutencoes m JOIN veiculos v ON v.id=m.veiculo_id
      WHERE m.status='agendada' AND m.data_manutencao <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY m.data_manutencao")->fetchAll();
    $cnh = $pdo->query("SELECT id, nome, cnh_validade FROM condutores WHERE status='ativo' AND cnh_validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY cnh_validade")->fetchAll();
    $pend = (int)$pdo->query("SELECT COUNT(*) FROM autorizacoes_conducao WHERE status='pendente'")->fetchColumn();
    $km = $pdo->query("SELECT COALESCE(SUM(km_final-km_inicial),0) FROM uso_veiculos WHERE km_final IS NOT NULL AND data_uso >= DATE_FORMAT(CURDATE(),'%Y-%m-01')")->fetchColumn();
    out(['manutencoes'=>$man,'agendadas'=>$agend,'cnh'=>$cnh,'pendentes'=>$pend,'km_mes'=>(int)$km]);
  }

  if (!isset($RES[$r])) out(['erro'=>'Recurso inválido'], 404);
  $res = $RES[$r];

  // Combobox AJAX: só id + rótulo
  if ($a === 'opcoes') {
    $q = trim($_GET['q'] ?? ''); $id = (int)($_GET['id'] ?? 0);
    if ($id) { $st = db()->prepare("SELECT t.id, {$res['label']} AS label FROM {$res['table']} t WHERE t.id=?"); $st->execute([$id]); out($st->fetchAll()); }
    $st = db()->prepare("SELECT t.id, {$res['label']} AS label FROM {$res['table']} t HAVING label LIKE ? ORDER BY label LIMIT 20");
    $st->execute(['%'.$q.'%']); out($st->fetchAll());
  }

  if ($a === 'list') {
    need("$r.ver");
    $page = max(1, (int)($_GET['p'] ?? 1)); $per = 15; $q = trim($_GET['q'] ?? '');
    $where = '1=1'; $par = [];
    if ($q !== '') { $w = []; foreach ($res['search'] as $i => $col) { $w[] = "$col LIKE :q$i"; $par[":q$i"] = "%$q%"; } $where = '('.implode(' OR ', $w).')'; }
    $c = db()->prepare("SELECT COUNT(*) FROM {$res['from']} WHERE $where"); $c->execute($par);
    $st = db()->prepare("SELECT {$res['select']} FROM {$res['from']} WHERE $where ORDER BY {$res['order']} LIMIT $per OFFSET ".(($page-1)*$per));
    $st->execute($par);
    out(['rows'=>$st->fetchAll(),'total'=>(int)$c->fetchColumn(),'per'=>$per]);
  }

  if ($a === 'get') {
    need("$r.ver");
    $st = db()->prepare("SELECT t.* FROM {$res['table']} t WHERE t.id=?"); $st->execute([(int)($_GET['id'] ?? 0)]);
    $row = $st->fetch(); $row ? out($row) : out(['erro'=>'Não encontrado'], 404);
  }

  if ($a === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    need($id ? "$r.editar" : "$r.criar");
    $errs = []; $data = [];
    foreach ($res['fields'] as $f => $def) {
      [$type, $req] = $def; $v = trim((string)($in[$f] ?? ''));
      if ($v === '') { if ($req) $errs[$f] = 'Campo obrigatório'; $data[$f] = null; continue; }
      switch ($type) {
        case 'cpf':   if (!valid_cpf($v))  $errs[$f] = 'CPF inválido';  $v = only_digits($v); break;
        case 'cnpj':  if (!valid_cnpj($v)) $errs[$f] = 'CNPJ inválido'; $v = only_digits($v); break;
        case 'phone': if (!valid_phone($v)) $errs[$f] = 'Telefone inválido'; $v = only_digits($v); break;
        case 'date':  if (!valid_date($v)) $errs[$f] = 'Data inválida'; break;
        case 'placa': $v = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $v)); if (!valid_placa($v)) $errs[$f] = 'Placa inválida'; break;
        case 'email': if (!filter_var($v, FILTER_VALIDATE_EMAIL)) $errs[$f] = 'E-mail inválido'; break;
        case 'digits': $v = only_digits($v); break;
        case 'int': case 'ref': if (!ctype_digit($v)) $errs[$f] = 'Valor inválido'; break;
        case 'dec':   $v = str_replace(',', '.', $v); if (!is_numeric($v)) $errs[$f] = 'Valor inválido'; break;
        case 'enum':  if (!in_array($v, $def[2], true)) $errs[$f] = 'Opção inválida'; break;
        case 'text':  if (mb_strlen($v) > ($def[2] ?? 255)) $errs[$f] = 'Texto muito longo'; break;
      }
      $data[$f] = $v;
    }

    // Regras de negócio
    if (!$errs) {
      if ($r === 'uso') {
        if ($data['km_final'] !== null && (int)$data['km_final'] < (int)$data['km_inicial']) $errs['km_final'] = 'KM final menor que o inicial';
        $st = db()->prepare('SELECT COALESCE(MAX(km_final),0) FROM uso_veiculos WHERE veiculo_id=? AND id<>?');
        $st->execute([$data['veiculo_id'], $id]);
        if ((int)$data['km_inicial'] < (int)$st->fetchColumn()) $errs['km_inicial'] = 'KM inicial menor que o último KM final do veículo';
        $st = db()->prepare('SELECT cnh_validade, status FROM condutores WHERE id=?'); $st->execute([$data['condutor_id']]);
        $cd = $st->fetch();
        if ($cd && $cd['cnh_validade'] < $data['data_uso']) $errs['condutor_id'] = 'CNH vencida na data de uso';
        if ($cd && $cd['status'] !== 'ativo') $errs['condutor_id'] = 'Condutor inativo';
      }
      if ($r === 'autorizacoes') {
        if ($data['data_fim'] < $data['data_inicio']) $errs['data_fim'] = 'Data final anterior à inicial';
        if (in_array($data['status'], ['aprovada','negada'], true) && !can('autorizacoes.aprovar')) $errs['status'] = 'Sem permissão para aprovar/negar';
      }
    }
    if ($errs) out(['erro'=>'Corrija os campos','campos'=>$errs], 422);

    try {
      if ($id) {
        $set = implode(',', array_map(fn($f) => "$f=:$f", array_keys($data)));
        $st = db()->prepare("UPDATE {$res['table']} SET $set WHERE id=:__id");
        $st->execute(array_merge(array_combine(array_map(fn($f) => ":$f", array_keys($data)), $data), [':__id'=>$id]));
      } else {
        $cols = implode(',', array_keys($data)); $ph = implode(',', array_map(fn($f) => ":$f", array_keys($data)));
        $st = db()->prepare("INSERT INTO {$res['table']} ($cols) VALUES ($ph)");
        $st->execute(array_combine(array_map(fn($f) => ":$f", array_keys($data)), $data));
        $id = (int)db()->lastInsertId();
      }
    } catch (PDOException $e) {
      if ($e->getCode() === '23000') out(['erro'=>'Registro duplicado ou referência inválida'], 409);
      throw $e;
    }
    if ($r === 'uso' && $data['km_final'] !== null)
      db()->prepare('UPDATE veiculos SET km_atual=GREATEST(km_atual,?) WHERE id=?')->execute([$data['km_final'], $data['veiculo_id']]);
    if ($r === 'autorizacoes' && $data['status'] === 'aprovada')
      db()->prepare("UPDATE autorizacoes_conducao SET aprovado_por=?, aprovado_em=NOW() WHERE id=? AND aprovado_por IS NULL")->execute([$_SESSION['u']['id'], $id]);
    audit($in['id'] ?? false ? 'editar' : 'criar', $r, $id, json_encode($data, JSON_UNESCAPED_UNICODE));
    out(['ok'=>true,'id'=>$id]);
  }

  if ($a === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    need("$r.excluir");
    $id = (int)(json_decode(file_get_contents('php://input'), true)['id'] ?? 0);
    try { db()->prepare("DELETE FROM {$res['table']} WHERE id=?")->execute([$id]); }
    catch (PDOException $e) { if ($e->getCode() === '23000') out(['erro'=>'Registro em uso por outros cadastros'], 409); throw $e; }
    audit('excluir', $r, $id);
    out(['ok'=>true]);
  }
  out(['erro'=>'Ação inválida'], 400);
} catch (Throwable $e) {
  error_log($e);
  out(['erro'=>'Erro interno'], 500);
}
