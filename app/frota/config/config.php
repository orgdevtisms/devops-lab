<?php
return [
  'db' => ['host'=>'127.0.0.1','name'=>'frota','user'=>'frota_app','pass'=>'TROCAR_SENHA','charset'=>'utf8mb4'],
  'geo' => true,                 // cidade/UF do IP via ip-api.com (HTTP, uso não comercial)
  'trusted_proxies' => [],       // IPs de proxies reversos confiáveis (para ler X-Forwarded-For)
  'session_name' => 'FROTASESS',
  'idle_timeout' => 1800,        // segundos
  'app_tz' => 'America/Sao_Paulo',
];
