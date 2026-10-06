FROM php:8.5-apache

# ============================================================
# Ambiente
# ============================================================
ENV APP_ENV=development \
    APACHE_DOCUMENT_ROOT=/var/www/html

WORKDIR /var/www/html

# ============================================================
# Dependências mínimas
# ============================================================
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        zip \
    && a2enmod \
        headers \
        rewrite \
        expires \
    && rm -rf /var/lib/apt/lists/*

# ============================================================
# Apache - hardening
# ============================================================

# Não expor versão do Apache
RUN printf '%s\n' \
    'ServerTokens Prod' \
    'ServerSignature Off' \
    > /etc/apache2/conf-available/security-hardening.conf \
    && a2enconf security-hardening

# ============================================================
# PHP - hardening
# ============================================================
RUN cat > /usr/local/etc/php/conf.d/security.ini <<'EOF'
expose_php = Off

display_errors = On
display_startup_errors = On
log_errors = On
error_log = /proc/self/fd/2

session.cookie_httponly = 1
session.cookie_samesite = Lax

; Em desenvolvimento não force HTTPS.
; Em produção deverá ser ativado.
session.cookie_secure = 0

allow_url_fopen = Off
allow_url_include = Off

cgi.fix_pathinfo = 0

max_execution_time = 30
max_input_time = 60
memory_limit = 256M

upload_max_filesize = 20M
post_max_size = 25M

realpath_cache_size = 4096K
realpath_cache_ttl = 600
EOF

# ============================================================
# Headers de segurança
# ============================================================
RUN cat > /etc/apache2/conf-available/security-headers.conf <<'EOF'
<IfModule mod_headers.c>

    Header always set X-Content-Type-Options "nosniff"

    Header always set X-Frame-Options "SAMEORIGIN"

    Header always set Referrer-Policy "strict-origin-when-cross-origin"

    Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"

    Header always set Cross-Origin-Opener-Policy "same-origin"

</IfModule>
EOF

RUN a2enconf security-headers

# ============================================================
# .htaccess
# ============================================================
RUN printf '%s\n' \
    'Options -Indexes' \
    > /var/www/html/.htaccess

# ============================================================
# Aplicação
# ============================================================
COPY --chown=www-data:www-data app/ /var/www/html/

# Permissões
RUN find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# ============================================================
# Apache
# ============================================================
RUN printf '%s\n' \
    'ServerName localhost' \
    > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

EXPOSE 80

CMD ["apache2-foreground"]
