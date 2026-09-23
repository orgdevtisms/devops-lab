<?php
declare(strict_types=1);

/**
 * Caminho do arquivo mediamtx.yml que esta aplicação vai ler/gravar.
 *
 * Aponte para o mediamtx.yml real usado pelo seu serviço MediaMTX,
 * por exemplo '/usr/local/etc/mediamtx/mediamtx.yml' ou
 * '/opt/mediamtx/mediamtx.yml'. Por padrão fica dentro da própria
 * pasta da aplicação, o que é mais simples para testar mas exige
 * copiar o arquivo manualmente para onde o MediaMTX o lê.
 */
define('MEDIAMTX_YAML_PATH', __DIR__ . '/mediamtx.yml');

/**
 * Pasta onde uma cópia de backup é salva automaticamente
 * toda vez que o botão "Salvar no servidor" sobrescreve o arquivo.
 */
define('BACKUP_DIR', __DIR__ . '/backups');
