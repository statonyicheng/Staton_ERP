#!/usr/bin/env bash
#
# 仕坦登 ERP — Ubuntu 伺服器一次性佈建腳本
#
# 適用：AWS Lightsail / EC2 的 Ubuntu 22.04 或 24.04
# 用法：
#   sudo bash provision.sh
#
# 這支腳本做完之後，伺服器就具備 Nginx + PHP-FPM + MariaDB + 防火牆 + 每日備份，
# 但「還沒有你的程式碼和資料」——那兩步在 README.md 的步驟 5、6。
#
# 可重複執行（已安裝的會跳過）。
set -euo pipefail

APP_DIR="/var/www/staton-erp"
APP_USER="www-data"
DB_NAME="staton_erp"
DB_USER="staton_erp"
BACKUP_DIR="/var/backups/staton-erp"

say() { echo -e "\n\033[1;34m==> $*\033[0m"; }
ok()  { echo -e "    \033[0;32m✓\033[0m $*"; }

if [[ $EUID -ne 0 ]]; then echo "請用 sudo 執行"; exit 1; fi

# ---------------------------------------------------------------- 系統更新
say "更新套件庫"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq
ok "完成"

# ---------------------------------------------------------------- Swap
# 2GB 記憶體的機器跑 PHP-FPM + MariaDB 會偏緊，加 2GB swap 當緩衝
say "設定 Swap"
if [[ ! -f /swapfile ]]; then
  fallocate -l 2G /swapfile
  chmod 600 /swapfile
  mkswap /swapfile >/dev/null
  swapon /swapfile
  grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
  sysctl -w vm.swappiness=10 >/dev/null
  grep -q 'vm.swappiness' /etc/sysctl.conf || echo 'vm.swappiness=10' >> /etc/sysctl.conf
  ok "已建立 2GB swap"
else
  ok "swap 已存在，跳過"
fi

# ---------------------------------------------------------------- 套件安裝
say "安裝 Nginx / PHP / MariaDB"
apt-get install -y -qq nginx mariadb-server unzip curl git \
  php-fpm php-cli php-mysql php-mbstring php-xml php-curl php-zip php-gd php-intl php-bcmath

PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
PHP_SOCK="/run/php/php${PHP_VER}-fpm.sock"
ok "PHP ${PHP_VER}（CodeIgniter 4.6 需要 8.1 以上）"

if [[ "$(printf '%s\n8.1' "$PHP_VER" | sort -V | head -1)" != "8.1" ]]; then
  echo "PHP 版本過舊（${PHP_VER}），請改用 Ubuntu 24.04 或加裝 ondrej PPA"; exit 1
fi

# CI4 與 PhpSpreadsheet / mPDF 需要的擴充
for ext in mbstring xml curl zip gd intl mysqli; do
  php -m | grep -qi "^${ext}$" && ok "擴充 ${ext} 已啟用" || echo "    ! 缺少擴充 ${ext}"
done

# ---------------------------------------------------------------- PHP 調校
say "調整 PHP 設定"
PHP_INI="/etc/php/${PHP_VER}/fpm/php.ini"
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 32M/' "$PHP_INI"
sed -i 's/^post_max_size = .*/post_max_size = 36M/' "$PHP_INI"
sed -i 's/^memory_limit = .*/memory_limit = 256M/' "$PHP_INI"
sed -i 's/^max_execution_time = .*/max_execution_time = 120/' "$PHP_INI"
sed -i 's/^;date.timezone.*/date.timezone = Asia\/Taipei/' "$PHP_INI"
systemctl restart "php${PHP_VER}-fpm"
ok "上傳上限 32M、記憶體 256M、時區 Asia/Taipei"

# ---------------------------------------------------------------- 資料庫
say "建立資料庫與專用帳號"
if ! mysql -e "USE ${DB_NAME}" 2>/dev/null; then
  DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | head -c 24)"
  mysql <<SQL
CREATE DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
  echo "${DB_PASS}" > /root/.staton_db_password
  chmod 600 /root/.staton_db_password
  ok "資料庫 ${DB_NAME} 已建立"
  echo -e "    \033[1;33m資料庫密碼已存在 /root/.staton_db_password，稍後要填進 .env\033[0m"
else
  ok "資料庫已存在，跳過"
fi

# 不要用 root 連線；並關閉遠端連線（只允許本機）
sed -i 's/^bind-address.*/bind-address = 127.0.0.1/' /etc/mysql/mariadb.conf.d/50-server.cnf || true
systemctl restart mariadb
ok "MariaDB 僅允許本機連線"

# ---------------------------------------------------------------- 目錄
say "建立應用程式目錄"
mkdir -p "${APP_DIR}"
chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}"
ok "${APP_DIR}"

# ---------------------------------------------------------------- Nginx
say "設定 Nginx"
SITE_CONF="/etc/nginx/sites-available/staton-erp"
cat > "$SITE_CONF" <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name _;

    # 網站根目錄指向 public/，app/ writable/ vendor/ .env 都在它之外，無法被瀏覽器讀取
    root ${APP_DIR}/public;
    index index.php;

    client_max_body_size 32M;

    # 安全標頭
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

    # 隱藏檔一律拒絕（.env、.git 等）
    location ~ /\. { deny all; }

    # 靜態資源快取
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2?|ttf)\$ {
        expires 30d;
        access_log off;
        try_files \$uri =404;
    }

    access_log /var/log/nginx/staton-erp.access.log;
    error_log  /var/log/nginx/staton-erp.error.log;
}
NGINX

ln -sf "$SITE_CONF" /etc/nginx/sites-enabled/staton-erp
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
ok "Nginx 已設定（root = ${APP_DIR}/public）"

# ---------------------------------------------------------------- 防火牆
say "設定防火牆"
ufw allow OpenSSH >/dev/null
ufw allow 'Nginx Full' >/dev/null
ufw --force enable >/dev/null
ok "只開放 22 / 80 / 443"

# ---------------------------------------------------------------- 備份
say "設定每日備份"
mkdir -p "${BACKUP_DIR}"
chmod 700 "${BACKUP_DIR}"
cp "$(dirname "$0")/backup.sh" /usr/local/bin/staton-backup.sh 2>/dev/null || true
chmod +x /usr/local/bin/staton-backup.sh 2>/dev/null || true
cat > /etc/cron.d/staton-backup <<CRON
# 每天 03:15 備份仕坦登 ERP 資料庫
15 3 * * * root /usr/local/bin/staton-backup.sh >> /var/log/staton-backup.log 2>&1
CRON
ok "每日 03:15 自動備份到 ${BACKUP_DIR}"

# ---------------------------------------------------------------- 憑證工具
say "安裝 HTTPS 憑證工具"
apt-get install -y -qq certbot python3-certbot-nginx
ok "certbot 已安裝（有網域後執行：sudo certbot --nginx -d 你的網域）"

say "佈建完成"
cat <<EOF

接下來（詳見 deploy/README.md）：
  1. 把程式碼放到 ${APP_DIR}
  2. cd ${APP_DIR} && php composer.phar install --no-dev
  3. 建立 .env（資料庫密碼在 /root/.staton_db_password）
  4. php spark migrate
  5. 匯入你的資料
  6. sudo chown -R ${APP_USER}:${APP_USER} ${APP_DIR}
  7. 有網域後：sudo certbot --nginx -d 你的網域

EOF
