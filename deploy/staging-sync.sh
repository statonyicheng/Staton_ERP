#!/usr/bin/env bash
#
# 把正式站的資料複製一份到測試站（單向：正式 → 測試，永遠不會反過來）
#
# 用法（在伺服器上）：
#   sudo bash /var/www/staton-erp-staging/deploy/staging-sync.sh
#   sudo bash .../staging-sync.sh --with-uploads   # 連商品圖一起（很大，預設不複製）
#
# 為什麼要這支：測試站要拿「跟正式站一樣的資料」來驗證改動，才驗得準。
#
# 安全設計：
#   · 只讀正式站、只寫測試站；正式站的資料庫與檔案完全不會被更動。
#   · 會覆蓋測試站現有資料，所以覆蓋前先幫測試站留一份備份，並要求手動確認。
#   · 正式站看起來不正常（表數過少）時直接中止，不會把壞資料蓋過去。
set -euo pipefail

PROD_DB="staton_erp"
STAGING_DB="staton_erp_staging"
PROD_DIR="/var/www/staton-erp"
STAGING_DIR="/var/www/staton-erp-staging"
APP_USER="www-data"
BACKUP_DIR="/var/backups/staton-erp"
WITH_UPLOADS="no"

for arg in "$@"; do
  [[ "${arg}" == "--with-uploads" ]] && WITH_UPLOADS="yes"
done

say() { echo -e "\n\033[1;34m==> $*\033[0m"; }
ok()  { echo -e "    \033[0;32m✓\033[0m $*"; }

if [[ $EUID -ne 0 ]]; then echo "請用 sudo 執行"; exit 1; fi
if ! mysql -e "USE ${STAGING_DB}" 2>/dev/null; then
  echo "測試站資料庫 ${STAGING_DB} 不存在，請先跑 staging-setup.sh"; exit 1
fi

PROD_TABLES="$(mysql -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${PROD_DB}'")"
if [[ "${PROD_TABLES}" -lt 10 ]]; then
  echo "正式站只有 ${PROD_TABLES} 張表，看起來不對，中止同步"; exit 1
fi

# ---------------------------------------------------------------- 確認
say "即將用正式站的資料覆蓋測試站"
echo "    來源（只讀）：${PROD_DB}　${PROD_TABLES} 張表"
echo "    目標（會被覆蓋）：${STAGING_DB}"
echo "    測試站現有的資料會消失，但會先備份。"
read -r -p "    確定要繼續嗎？請輸入 yes： " answer
[[ "${answer}" == "yes" ]] || { echo "已取消，什麼都沒動。"; exit 1; }

STAMP="$(date +%Y%m%d-%H%M%S)"
mkdir -p "${BACKUP_DIR}"

# ---------------------------------------------------------------- 先備份測試站
say "備份測試站現況"
BACKUP_FILE="${BACKUP_DIR}/staging-before-sync-${STAMP}.sql.gz"
mysqldump --single-transaction --quick "${STAGING_DB}" | gzip > "${BACKUP_FILE}"
ok "$(du -h "${BACKUP_FILE}" | cut -f1) → ${BACKUP_FILE}"
echo "    要還原：zcat ${BACKUP_FILE} | mysql ${STAGING_DB}"

# ---------------------------------------------------------------- 複製資料
say "複製資料"
mysqldump --single-transaction --quick --routines "${PROD_DB}" | mysql "${STAGING_DB}"
STAGING_TABLES="$(mysql -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${STAGING_DB}'")"
ok "測試站現在有 ${STAGING_TABLES} 張表"

# ---------------------------------------------------------------- 商品圖
if [[ "${WITH_UPLOADS}" == "yes" ]]; then
  say "複製商品圖"
  rsync -a "${PROD_DIR}/public/uploads/" "${STAGING_DIR}/public/uploads/"
  chown -R "${APP_USER}:${APP_USER}" "${STAGING_DIR}/public/uploads"
  ok "$(du -sh "${STAGING_DIR}/public/uploads" | cut -f1)"
else
  ok "略過商品圖（要複製請加 --with-uploads）"
fi

# ---------------------------------------------------------------- 補跑 migration
say "補跑測試站的 migration"
sudo -u "${APP_USER}" bash -c "cd ${STAGING_DIR} && php spark migrate"
sudo -u "${APP_USER}" bash -c "cd ${STAGING_DIR} && php spark cache:clear" >/dev/null
ok "資料表結構已對齊測試站的程式碼"

# ---------------------------------------------------------------- 驗一下
say "測試站資料稽核"
sudo -u "${APP_USER}" bash -c "cd ${STAGING_DIR} && php spark erp:audit" | tail -5

cat <<EOF

完成。提醒：
  · 測試站現在是正式站資料的複本，登入帳密與正式站相同。
  · 在測試站做的任何事都不會影響正式站。

EOF
