# Painel de Configuração MediaMTX (PHP)

Aplicação PHP para rodar num Apache (com `mod_php` ou `php-fpm` + `mod_proxy_fcgi`)
que gera e, opcionalmente, grava diretamente o `mediamtx.yml` do seu servidor.

## Arquivos

| Arquivo         | Função                                                            |
|-----------------|--------------------------------------------------------------------|
| `index.php`     | Interface — formulário com todas as seções do MediaMTX + preview  |
| `config.php`     | Caminho do `mediamtx.yml` real e da pasta de backups              |
| `save.php`       | Endpoint chamado pelo botão "Salvar no servidor" (grava o arquivo)|
| `download.php`   | Serve o `mediamtx.yml` atualmente salvo como download             |
| `.htaccess`      | Bloqueia listagem de diretório e acesso direto a `.yml`           |
| `backups/`       | Cópia automática do arquivo anterior a cada gravação              |

O formulário em si roda 100% no navegador (o YAML é montado em JavaScript);
o PHP entra apenas para persistir o resultado no disco do servidor e para
servir a interface.

## Instalação

1. Copie a pasta para dentro do seu `DocumentRoot` (ou de um `VirtualHost`
   dedicado), por exemplo:
   ```
   sudo cp -r mediamtx-php-app /var/www/mediamtx-gui
   sudo chown -R www-data:www-data /var/www/mediamtx-gui
   ```
2. Certifique-se de que o Apache tem o módulo PHP habilitado:
   ```
   sudo a2enmod php8.3   # ajuste a versão instalada
   sudo systemctl restart apache2
   ```
3. Garanta que `AllowOverride All` está habilitado para essa pasta no
   `VirtualHost`, para que o `.htaccess` funcione:
   ```apache
   <Directory /var/www/mediamtx-gui>
       AllowOverride All
       Require all granted
   </Directory>
   ```
4. Edite `config.php` e aponte `MEDIAMTX_YAML_PATH` para o arquivo real
   usado pelo serviço MediaMTX (ex: `/usr/local/etc/mediamtx/mediamtx.yml`).
5. Dê permissão de escrita ao usuário do Apache nessa pasta:
   ```
   sudo chown www-data:www-data /usr/local/etc/mediamtx
   ```
   Sem isso, o botão "Salvar no servidor" fica desabilitado e a interface
   avisa — mas "Baixar" e "Copiar" continuam funcionando normalmente.

## Segurança — leia antes de expor na rede

Este arquivo de configuração contém usuários, senhas e URLs de câmeras
(as URLs RTSP do seu `mediamtx.yml` já trazem usuário/senha embutidos).
Antes de deixar essa aplicação acessível fora do seu ambiente confiável:

- **Habilite autenticação básica** — descomente as linhas de `AuthType Basic`
  no `.htaccess` e gere a senha com `htpasswd`.
- Sirva a aplicação por **HTTPS** (a interface por si só não criptografa nada).
- Considere restringir o acesso por IP/VPN, já que ela pode reiniciar
  a configuração de todo o servidor de streaming.

## Reiniciar o MediaMTX após salvar

Salvar o YAML não reinicia o serviço automaticamente. Depois de salvar,
recarregue o MediaMTX no servidor onde ele roda, por exemplo:
```
sudo systemctl restart mediamtx
```
Se preferir automatizar isso, dá para chamar esse comando dentro de
`save.php` via `shell_exec()` — deixei de fora por padrão porque exige
liberar `sudo` sem senha para o usuário do Apache, o que é uma decisão
de segurança que cabe a você tomar conscientemente.

## Limitação conhecida

O formulário é pré-preenchido com os valores do `mediamtx.yml` original
enviado na conversa. Se o arquivo salvo no servidor for editado por fora
(manualmente ou por outra ferramenta), a interface **não** relê esse
arquivo e o reconverte em campos automaticamente — ela sempre parte do
estado atual do formulário no navegador. Use "Baixar do servidor" para
conferir o conteúdo realmente salvo a qualquer momento.
