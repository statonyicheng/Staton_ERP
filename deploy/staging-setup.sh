#!/usr/bin/env bash
#
# 仕坦登 ERP — 測試站一次性建置（與正式站同一台機器，獨立資料庫）
#
# 用法（在伺服器上）：
#   sudo bash /var/www/staton-erp-staging/deploy/staging-setup.sh
#   —— 若目錄還不存在，先：
#      sudo git clone https://github.com/statonyicheng/Staton_ERP.git /var/www/staton-erp-staging
#
# 做完之後測試站可用 HTTP 存取（DNS 指過來後再跑 certbot 上 HTTPS）。
# 可重複執行：已存在的資料庫／設定會跳過，不會覆蓋。
#
# ⚠ 本腳本只會寫入測試站，永遠不碰正式站的資料庫與程式碼。
set -euo pipefail

STAGING_DIR="/var/www/staton-erp-staging"
PROD_DIR="/var/www/staton-erp"
APP_USER="www-data"
DB_NAME="staton_erp_staging"
DB_USER="staton_staging"
DB_PASS_FILE="/root/.staton_staging_db_password"
DOMAIN="staging.erp.staton.com.tw"
REPO="https://github.com/statonyicheng/Staton_ERP.git"

say() { echo -e "\n\033[1;34m==> $*\033[0m"; }
ok()  { echo -e "    \033[0;32m✓\033[0m $*"; }

if [[ $EUID -ne 0 ]]; then echo "請用 sudo 執行"; exit 1; fi

# ---------------------------------------------------------------- 程式碼
say "取得程式碼"
if [[ ! -d "${STAGING_DIR}/.git" ]]; then
  git clone "${REPO}" "${STAGING_DIR}"
  ok "已 clone 到 ${STAGING_DIR}"
else
  git -C "${STAGING_DIR}" pull --ff-only
  ok "已更新到最新版"
fi

# ---------------------------------------------------------------- 相依套件
say "安裝相依套件"
if [[ ! -f "${STAGING_DIR}/composer.phar" ]]; then
  if [[ -f "${PROD_DIR}/composer.phar" ]]; then
    cp "${PROD_DIR}/composer.phar" "${STAGING_DIR}/composer.phar"
  else
    curl -sS https://getcomposer.org/installer | php -- --install-dir="${STAGING_DIR}"
  fi
fi
(cd "${STAGING_DIR}" && php composer.phar install --no-dev --no-interaction --quiet)
ok "vendor/ 完成"

# ---------------------------------------------------------------- 資料庫
say "建立測試站資料庫"
if ! mysql -e "USE ${DB_NAME}" 2>/dev/null; then
  DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | head -c 24)"
  mysql <<SQL
CREATE DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
  echo "${DB_PASS}" > "${DB_PASS_FILE}"
  chmod 600 "${DB_PASS_FILE}"
  ok "資料庫 ${DB_NAME} 已建立（密碼在 ${DB_PASS_FILE}）"
else
  ok "資料庫已存在，跳過"
fi

# ---------------------------------------------------------------- .env
say "建立 .env"
if [[ ! -f "${STAGING_DIR}/.env" ]]; then
  DB_PASS="$(cat "${DB_PASS_FILE}")"
  cat > "${STAGING_DIR}/.env" <<ENV
# 測試站設定（本檔不進版控）
CI_ENVIRONMENT = production

app.baseURL = 'http://${DOMAIN}/'
app.indexPage = ''
app.appTimezone = 'Asia/Taipei'
app.defaultLocale = 'zh-Hant'
# 上了 HTTPS（certbot）之後，把下面兩項與 baseURL 一起改成 https/true
app.forceGlobalSecureRequests = false

database.default.hostname = localhost
database.default.database = ${DB_NAME}
database.default.username = ${DB_USER}
database.default.password = '${DB_PASS}'
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306

# 跟正式站不同名，兩站同時開著也不會互相踢掉登入
session.cookieName = staton_erp_staging_session
session.expiration = 7200
cookie.secure = false
cookie.httponly = true
cookie.samesite = 'Lax'

logger.threshold = 4
ENV
  chmod 600 "${STAGING_DIR}/.env"
  ok ".env 已建立"
else
  ok ".env 已存在，保留不動"
fi

# ---------------------------------------------------------------- 權限
say "設定檔案權限"
chown -R "${APP_USER}:${APP_USER}" "${STAGING_DIR}"
chmod 600 "${STAGING_DIR}/.env"
ok "owner = ${APP_USER}"

# ---------------------------------------------------------------- Nginx
say "設定 Nginx"
PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
PHP_SOCK="/run/php/php${PHP_VER}-fpm.sock"
SITE_CONF="/etc/nginx/sites-available/staton-erp-staging"

cat > "$SITE_CONF" <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};

    root ${STAGING_DIR}/public;
    index index.php;

    client_max_body_size 32M;

    # 測試站不要被搜尋引擎收錄
    add_header X-Robots-Tag "noindex, nofollow" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files \$uri \$uri/ /index.php\$is_args\$args;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${PHP_SOCK};
        fastcgi_read_timeout 120;
    }

    location ~ /\. { deny all; }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2?|ttf)\$ {
        expires 7d;
        access_log off;
        try_files \$uri =404;
    }

    access_log /var/log/nginx/staton-erp-staging.access.log;
    error_log  /var/log/nginx/staton-erp-staging.error.log;
}
NGINX

ln -sf "$SITE_CONF" /etc/nginx/sites-enabled/staton-erp-staging
nginx -t
systemctl reload nginx
ok "Nginx 已設定（${DOMAIN} → ${STAGING_DIR}/public）"

# ---------------------------------------------------------------- 資料表
say "建立資料表"
sudo -u "${APP_USER}" bash -c "cd ${STAGING_DIR} && php spark migrate"
ok "migration 完成"

say "測試站建置完成"
cat <<EOF

現在可以這樣驗（DNS 還沒指過來也能測）：
  curl -sI -H "Host: ${DOMAIN}" http://localhost/login

接下來：
  1. 把正式站資料複製過來：sudo bash ${STAGING_DIR}/deploy/staging-sync.sh
  2. 在 GoDaddy 加 A 記錄 staging.erp → 這台機器的 IP
  3. DNS 生效後上 HTTPS：sudo certbot --nginx -d ${DOMAIN}
     憑證裝好後記得把 .env 的 baseURL 改 https、forceGlobalSecureRequests 與 cookie.secure 改 true

EOF
