<?php
require __DIR__.'/../src/bootstrap.php';
if (!user() || !can('autorizacoes.ver')) { http_response_code(403); exit('Acesso negado'); }
$st = db()->prepare('SELECT a.*, v.placa, v.marca, v.modelo, c.nome, c.cpf, c.cnh_numero, c.cnh_categoria, c.cnh_validade, u.nome AS aprovador
  FROM autorizacoes_conducao a JOIN veiculos v ON v.id=a.veiculo_id JOIN condutores c ON c.id=a.condutor_id
  LEFT JOIN usuarios u ON u.id=a.aprovado_por WHERE a.id=?');
$st->execute([(int)($_GET['id'] ?? 0)]); $a = $st->fetch();
if (!$a) exit('Não encontrado');
$d = fn($x) => $x ? date('d/m/Y', strtotime($x)) : '-';
$cpf = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $a['cpf']);
audit('imprimir', 'autorizacoes', (int)$a['id']);
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Autorização #<?= (int)$a['id'] ?></title>
<style>body{font-family:Arial,sans-serif;max-width:720px;margin:30px auto}td{padding:6px 4px}.b{border-top:1px solid #000;text-align:center;padding-top:4px}@media print{button{display:none}}</style></head><body>
<h2 style="text-align:center">FORMULÁRIO DE AUTORIZAÇÃO PARA CONDUÇÃO DE VEÍCULO Nº <?= (int)$a['id'] ?></h2>
<table width="100%">
<tr><td><b>Condutor:</b> <?= h($a['nome']) ?></td><td><b>CPF:</b> <?= h($cpf) ?></td></tr>
<tr><td><b>CNH:</b> <?= h($a['cnh_numero']) ?> (cat. <?= h($a['cnh_categoria']) ?>)</td><td><b>Validade:</b> <?= $d($a['cnh_validade']) ?></td></tr>
<tr><td><b>Veículo:</b> <?= h($a['marca'].' '.$a['modelo']) ?></td><td><b>Placa:</b> <?= h($a['placa']) ?></td></tr>
<tr><td><b>Período:</b> <?= $d($a['data_inicio']) ?> a <?= $d($a['data_fim']) ?></td><td><b>Situação:</b> <?= h(strtoupper($a['status'])) ?></td></tr>
<tr><td colspan="2"><b>Finalidade:</b> <?= h($a['finalidade']) ?></td></tr>
<tr><td colspan="2"><b>Destino:</b> <?= h($a['destino'] ?? '-') ?></td></tr>
<tr><td colspan="2"><b>Aprovado por:</b> <?= h($a['aprovador'] ?? '-') ?> em <?= $d($a['aprovado_em']) ?></td></tr></table>
<p style="margin-top:60px"><table width="100%"><tr><td class="b" width="45%">Condutor</td><td></td><td class="b" width="45%">Responsável pela autorização</td></tr></table></p>
<button onclick="print()">Imprimir</button></body></html>
