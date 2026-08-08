#!/usr/bin/env bash
#
# 仕坦登 ERP — 每日備份
#
# 由 /etc/cron.d/staton-backup 每天 03:15 執行。
# 也可手動執行：sudo /usr/local/bin/staton-backup.sh
#
# 備份內容：資料庫 + 上傳的商品圖 + .env
# 保留策略：資料庫 30 天、圖片每週一份保留 8 週
#
# ⚠ 主機商的 snapshot 只能防「機器掛掉」，防不了「誤刪資料」。
#   本腳本產生的檔案請務必再同步到主機以外的地方（S3 / Google Drive），
#   並且每季實際還原一次驗證 —— 沒驗過的備份等於沒有備份。
set -euo pipefail

APP_DIR="/var/www/staton-erp"
BACKUP_DIR="/var/backups/staton-erp"
DB_NAME="staton_erp"
KEEP_DB_DAYS=30
KEEP_FILE_WEEKS=8

STAMP="$(date +%Y%m%d-%H%M)"
mkdir -p "${BACKUP_DIR}"

# ---- 資料庫 ----
# 從 .env 讀連線資訊，避免密碼寫死在腳本裡
DB_USER="$(grep -E '^database.default.username' "${APP_DIR}/.env" | cut -d= -f2- | tr -d " '\"")"
DB_PASS="$(grep -E '^database.default.password' "${APP_DIR}/.env" | cut -d= -f2- | tr -d " '\"")"

DUMP="${BACKUP_DIR}/db-${STAMP}.sql.gz"
mysqldump --single-transaction --quick --default-character-set=utf8mb4 \
  -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" | gzip -9 > "${DUMP}"

# 驗證備份不是空的（mysqldump 失敗時也可能產生空檔）
if [[ "$(stat -c%s "${DUMP}")" -lt 1024 ]]; then
  echo "[$(date)] 錯誤：備份檔過小，可能失敗 → ${DUMP}"
  exit 1
fi
echo "[$(date)] 資料庫備份完成 ${DUMP} ($(du -h "${DUMP}" | cut -f1))"

# ---- 上傳檔案（每週一才做，圖片不常變且體積大）----
if [[ "$(date +%u)" == "1" ]]; then
  FILES="${BACKUP_DIR}/uploads-${STAMP}.tar.gz"
  tar -czf "${FILES}" -C "${APP_DIR}/public" uploads 2>/dev/null || true
  echo "[$(date)] 商品圖備份完成 ${FILES} ($(du -h "${FILES}" | cut -f1))"
fi

# ---- .env（含資料庫密碼，權限鎖死）----
cp "${APP_DIR}/.env" "${BACKUP_DIR}/env-${STAMP}.bak"
chmod 600 "${BACKUP_DIR}/env-${STAMP}.bak"

# ---- 清理舊備份 ----
find "${BACKUP_DIR}" -name 'db-*.sql.gz'      -mtime "+${KEEP_DB_DAYS}" -delete
find "${BACKUP_DIR}" -name 'env-*.bak'        -mtime "+${KEEP_DB_DAYS}" -delete
find "${BACKUP_DIR}" -name 'uploads-*.tar.gz' -mtime "+$((KEEP_FILE_WEEKS * 7))" -delete

# ---- 異地同步（設定好 aws cli 後把下面這行的註解拿掉）----
# aws s3 sync "${BACKUP_DIR}" "s3://你的備份bucket/staton-erp/" --storage-class STANDARD_IA

echo "[$(date)] 備份作業結束"
