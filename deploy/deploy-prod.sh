#!/usr/bin/env bash
#
# 把「已在測試站驗證過」的程式碼部署到正式站
#
# 用法（在伺服器上）：
#   sudo bash /var/www/staton-erp/deploy/deploy-prod.sh
#
# 順序刻意設計成：先備份 → 更新程式碼 → 相依套件 → 資料表結構 → 清快取 → 全套驗證。
# 任何一步失敗就停下來（set -e），不會留下改到一半的狀態。
#
# ⚠ 這支腳本只更新程式碼與資料表結構，不會匯入或覆蓋任何業務資料。
set -euo pipefail

PROD_DIR="/var/www/staton-erp"
STAGING_DIR="/var/www/staton-erp-staging"
APP_USER="www-data"

say() { echo -e "\n\033[1;34m==> $*\033[0m"; }
ok()  { echo -e "    \033[0;32m✓\033[0m $*"; }

# 程式碼歸 www-data，git 一律以該身分執行（用 root 會被 git 的 safe.directory 擋下）
pgit() { sudo -u "${APP_USER}" git -C "${PROD_DIR}" "$@"; }
sgit() { sudo -u "${APP_USER}" git -C "${STAGING_DIR}" "$@"; }

if [[ $EUID -ne 0 ]]; then echo "請用 sudo 執行"; exit 1; fi

# ---------------------------------------------------------------- 出發點
say "目前版本"
pgit log --oneline -1
BEFORE="$(pgit rev-parse HEAD)"

if [[ -d "${STAGING_DIR}/.git" ]]; then
  STAGING_REV="$(sgit rev-parse HEAD)"
  echo "    測試站版本：$(sgit log --oneline -1)"
  pgit fetch --quiet origin
  REMOTE="$(pgit rev-parse origin/main)"
  if [[ "${STAGING_REV}" != "${REMOTE}" ]]; then
    echo -e "\n\033[1;33m注意：測試站的版本和 origin/main 不一樣。\033[0m"
    echo "    正式站即將部署的是 origin/main，也就是沒有在測試站驗證過的版本。"
    read -r -p "    還要繼續嗎？(yes/no) " answer
    [[ "${answer}" == "yes" ]] || { echo "已取消"; exit 1; }
  fi
else
  echo "    （尚未建立測試站，這次部署沒有經過測試站驗證）"
fi

# ---------------------------------------------------------------- 備份
say "部署前先備份資料庫"
/usr/local/bin/staton-backup.sh
ok "備份完成"

# ---------------------------------------------------------------- 程式碼
say "更新程式碼"
pgit pull --ff-only
pgit log --oneline -1
ok "已更新"

# ---------------------------------------------------------------- 相依套件
say "更新相依套件"
sudo -u "${APP_USER}" bash -c "cd ${PROD_DIR} && php composer.phar install --no-dev --no-interaction --quiet"
ok "vendor/ 完成"

# ---------------------------------------------------------------- 資料表結構
say "更新資料表結構"
sudo -u "${APP_USER}" bash -c "cd ${PROD_DIR} && php spark migrate"
sudo -u "${APP_USER}" bash -c "cd ${PROD_DIR} && php spark cache:clear" >/dev/null
ok "migration 與快取完成"

# ---------------------------------------------------------------- 驗證
say "上線後驗證"
FAILED=0
for cmd in "erp:audit" "erp:schema-drift" "erp:perm-check" "erp:view-check" "erp:lock-selftest" "erp:audit-selftest"; do
  echo -e "\n    --- ${cmd} ---"
  if sudo -u "${APP_USER}" bash -c "cd ${PROD_DIR} && php spark ${cmd}" | tail -3; then
    :
  else
    FAILED=1
    echo -e "    \033[0;31m✗ ${cmd} 沒有正常結束\033[0m"
  fi
done

echo -e "\n    --- 網站回應 ---"
curl -sS -o /dev/null -w "    https://erp.staton.com.tw/login → %{http_code}\n" https://erp.staton.com.tw/login

if [[ "${FAILED}" -eq 1 ]]; then
  cat <<EOF

\033[1;31m有驗證項目沒過。\033[0m 要退回上一版：
  sudo -u ${APP_USER} git -C ${PROD_DIR} reset --hard ${BEFORE}
  （資料表結構若已變更，請一併確認是否需要 spark migrate:rollback）

EOF
  exit 1
fi

say "部署完成"
echo "    上一版是 ${BEFORE}，需要退回時可用它。"
