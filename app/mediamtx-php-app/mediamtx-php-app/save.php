<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

function fail(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'Método não permitido.');
}

$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string) $token)) {
    fail(403, 'Sessão expirada ou token inválido. Recarregue a página e tente novamente.');
}

$yaml = $_POST['yaml'] ?? '';
if (trim((string) $yaml) === '') {
    fail(400, 'Conteúdo YAML vazio — nada para salvar.');
}

$dir = dirname(MEDIAMTX_YAML_PATH);
if (!is_dir($dir) || !is_writable($dir)) {
    fail(500, 'Sem permissão de escrita em ' . $dir . '. Ajuste o dono/permissões para o usuário do Apache (ex: www-data).');
}

// Backup do arquivo anterior, se existir, antes de sobrescrever.
if (file_exists(MEDIAMTX_YAML_PATH)) {
    if (!is_dir(BACKUP_DIR)) {
        @mkdir(BACKUP_DIR, 0750, true);
    }
    if (is_dir(BACKUP_DIR) && is_writable(BACKUP_DIR)) {
        $stamp = date('Y-m-d_H-i-s');
        @copy(MEDIAMTX_YAML_PATH, BACKUP_DIR . "/mediamtx_{$stamp}.yml");
    }
}

$bytes = @file_put_contents(MEDIAMTX_YAML_PATH, $yaml);
if ($bytes === false) {
    fail(500, 'Falha ao escrever em ' . MEDIAMTX_YAML_PATH . '. Verifique as permissões do arquivo.');
}

echo json_encode([
    'ok'      => true,
    'path'    => MEDIAMTX_YAML_PATH,
    'bytes'   => $bytes,
    'savedAt' => date('d/m/Y H:i:s'),
]);
