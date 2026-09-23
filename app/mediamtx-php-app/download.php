<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if (!file_exists(MEDIAMTX_YAML_PATH)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Nenhum arquivo foi salvo neste servidor ainda.';
    exit;
}

header('Content-Type: text/yaml; charset=utf-8');
header('Content-Disposition: attachment; filename="mediamtx.yml"');
header('Content-Length: ' . (string) filesize(MEDIAMTX_YAML_PATH));
header('X-Content-Type-Options: nosniff');
readfile(MEDIAMTX_YAML_PATH);
