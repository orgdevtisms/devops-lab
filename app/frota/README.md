# Gestão de Frota (PHP + Apache + MySQL)

## Instalação (Ubuntu Server)
```bash
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql
sudo a2enmod headers rewrite
sudo cp -r frota /var/www/frota && sudo chown -R www-data:www-data /var/www/frota
sudo cp /var/www/frota/apache-frota.conf /etc/apache2/sites-available/frota.conf
sudo a2ensite frota && sudo systemctl reload apache2

sudo mysql < /var/www/frota/sql/schema.sql
sudo mysql -e "CREATE USER 'frota_app'@'localhost' IDENTIFIED BY 'SENHA_FORTE'; GRANT SELECT,INSERT,UPDATE,DELETE ON frota.* TO 'frota_app'@'localhost';"
# edite config/config.php com a senha
php /var/www/frota/bin/criar_admin.php "Seu Nome" voce@empresa.com
```
Só `public/` fica exposto; `config/`, `src/` e `sql/` ficam fora do DocumentRoot. Use HTTPS em produção (cookie de sessão sai como `Secure` automaticamente).

## Segurança implementada
- **SQL injection**: PDO com prepared statements reais (`EMULATE_PREPARES=false`), colunas por whitelist (`src/resources.php`).
- **RBAC**: usuários N:N papéis N:N permissões (`recurso.acao`), checado no servidor a cada requisição; o menu do front só reflete as permissões.
- **IP e local**: registrados no login (`log_acesso`) e em toda gravação (`auditoria`). Local via ip-api.com (HTTP, uso não comercial); troque em `geo_lookup()` por MaxMind GeoLite2 se preferir offline.
- Sessão com regeneração de ID, cookie HttpOnly/SameSite=Strict, timeout por inatividade, CSRF em todo POST, bloqueio de 5 falhas/15 min por IP, XSS tratado com escape no front e CSP.
- Validações de CPF, CNPJ, datas, telefone e placa no front e no back.

## Regras de negócio
- KM final ≥ KM inicial; KM inicial ≥ último KM final do veículo; `km_atual` do veículo atualiza sozinho.
- Uso bloqueado se a CNH estiver vencida na data de uso ou o condutor estiver inativo.
- Aprovar/negar autorização exige a permissão `autorizacoes.aprovar`.
- Avisos: manutenção com próxima data em até 30 dias ou próximo KM a até 1.000 km; CNH vencendo em 30 dias.
