# 仕坦登 ERP — AWS 部署步驟

給第一次部署的人照著做。**AWS 主控台的部分要你自己點**（那些會產生費用、也牽涉你的帳號憑證）；
伺服器裡面的指令可以直接複製貼上。

---

## 步驟 0 — 先確認區域（很重要）

主控台右上角必須是 **亞太地區（台北）`ap-east-2`**。

- 台北：資料留在台灣境內，延遲最低
- 雪梨 `ap-southeast-2`：離台灣 130–150ms，明顯卡頓
- **資源建立後不能跨區域搬移**，只能砍掉重開，所以務必先切換

> **注意**：Lightsail 目前不支援台北區域，所以台北只能用 **EC2**。
> EC2 是按量計費，帳單風險比 Lightsail 高，步驟 3 的預算警示請務必設定。
> 若之後改以「省事、有 3 個月免費」為優先，可改用東京 `ap-northeast-1` + Lightsail $12 方案。

---

## 步驟 1 — 建立 EC2 執行個體

主控台搜尋 **EC2** → 啟動執行個體：

| 項目 | 選擇 |
|---|---|
| 名稱 | `staton-erp` |
| AMI | **Ubuntu Server 24.04 LTS**（x86_64）<br>⚠ 見下方「PHP 版本」說明 |
| 執行個體類型 | **t3.small**（2 vCPU / 2GB）<br>想再省可選 `t4g.small`（ARM，約便宜 20%，本系統相容） |
| 金鑰對 | 新建一組，`.pem` 檔**下載後妥善保管**（遺失就進不去機器） |
| 儲存體 | **30 GB gp3**（程式碼 + 商品圖 537MB + 備份綽綽有餘） |

**安全群組**（等同防火牆）新增三條輸入規則：

| 類型 | 連接埠 | 來源 |
|---|---|---|
| SSH | 22 | **我的 IP**（不要用 0.0.0.0/0） |
| HTTP | 80 | Anywhere 0.0.0.0/0 |
| HTTPS | 443 | Anywhere 0.0.0.0/0 |

---

## 步驟 2 — 固定 IP（Elastic IP）

EC2 → 網路與安全性 → **彈性 IP** → 配置 → 關聯到 `staton-erp`。

不做的話機器重開 IP 就會變，網域會失效。
⚠ 彈性 IP 若**配置了卻沒關聯到執行中的機器**會產生費用，不用時記得釋放。

---

## 步驟 3 — 設定預算警示（EC2 按量計費，這步別跳過）

主控台搜尋 **Billing** → Budgets → 建立預算：
- 類型：Cost budget
- 金額：**USD 1**
- 通知：實際費用達 100% 時寄信給你

免費 credits 用完或有資源忘了關，你會第一時間知道。

---

### ⚠ 關於 PHP 版本（2026-08-08 實際踩到）

本專案目前跑 **CodeIgniter 4.7.4**，需要 **PHP 8.1 以上**。

各 Ubuntu 版本預設提供的 PHP：

| Ubuntu | 預設 PHP | 可用嗎 |
|---|---|---|
| 24.04 LTS | 8.3 | ✅ 最穩 |
| 26.04 LTS | 8.5 | ✅ 可以（CI4 4.7.4 起支援） |

**注意**：如果專案還停在 CI4 4.6.x，**不能**用 Ubuntu 26.04
——4.6 的相依 `laminas-escaper` 限制 PHP ≤ 8.4，composer 會直接擋下安裝，
而 `ppa:ondrej/php` 當時尚未支援 26.04（resolute），無法退裝舊版 PHP。

**升級框架後務必補齊 Config 屬性**：CI4 大版本升級時，框架新增的設定屬性
不會自動出現在專案的 `app/Config/*.php`，缺少時**不會**在啟動時報錯，
而是執行期存取到才爆。本次症狀是「所有 404 都變成 500」
（錯誤處理要輸出 JSON 時撞到 `Config\Format::$jsonEncodeDepth` 未定義）。
比對方式：把專案的 `app/Config/X.php` 與
`vendor/codeigniter4/framework/app/Config/X.php` 的屬性清單對照，缺的補上。

---

## 步驟 4 — 佈建伺服器

用 EC2 主控台的 **連線 → EC2 Instance Connect**（瀏覽器內終端機，不必自己設定金鑰），
或用下載的 `.pem` 從本機連：

```bash
ssh -i staton-erp.pem ubuntu@你的彈性IP
```

```bash
# 取得部署腳本（擇一）
# a) 已把程式碼推到私有 Git：
git clone <你的私有repo網址> /tmp/erp && cd /tmp/erp/deploy
# b) 或用 scp 從本機上傳：
#    scp -i staton-erp.pem -r deploy ubuntu@你的彈性IP:/tmp/

sudo bash provision.sh
```

腳本會裝好 Nginx + PHP + MariaDB、設定 swap、防火牆、每日備份與 HTTPS 工具。
**跑完會顯示資料庫密碼的存放位置（`/root/.staton_db_password`），等一下要用。**

---

## 步驟 5 — 上傳程式碼

```bash
sudo mkdir -p /var/www/staton-erp
sudo chown -R $USER:$USER /var/www/staton-erp

# 從你的私有 Git 拉下來
git clone <你的私有repo網址> /var/www/staton-erp

cd /var/www/staton-erp
php composer.phar install --no-dev --optimize-autoloader
```

> `vendor/` 沒有進版控，所以一定要跑 `composer install`。
> `composer.phar` 也沒進版控，若缺少可執行：
> `php -r "copy('https://getcomposer.org/composer-stable.phar','composer.phar');"`

---

## 步驟 6 — 建立 .env

```bash
cd /var/www/staton-erp
cp env.example .env
sudo cat /root/.staton_db_password     # 複製這串密碼
nano .env
```

至少要改這幾行：

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://你的網域/'        # 還沒有網域就先填 http://你的靜態IP/
database.default.username = staton_erp
database.default.password = '剛剛複製的密碼'
app.forceGlobalSecureRequests = true    # 還沒設定 HTTPS 前先設 false
```

---

## 步驟 7 — 建立資料表並匯入資料

```bash
cd /var/www/staton-erp
php spark migrate
```

**把本機資料搬上去**，先在你的 Windows 電腦執行（PowerShell）：

```powershell
& "C:\Users\a0210\xampp\mysql\bin\mysqldump.exe" -u root --default-character-set=utf8mb4 --single-transaction land_stone_quote > "$env:USERPROFILE\Desktop\staton_erp.sql"
```

把產生的 `staton_erp.sql` 上傳到伺服器後：

```bash
mysql -u staton_erp -p staton_erp --default-character-set=utf8mb4 < staton_erp.sql
```

**商品圖**（537MB，不在 Git 裡）用 scp 或 rsync 上傳到
`/var/www/staton-erp/public/uploads/`。

---

## 步驟 8 — 權限與啟動

```bash
sudo chown -R www-data:www-data /var/www/staton-erp
sudo chmod -R 775 /var/www/staton-erp/writable
sudo systemctl reload nginx
```

用瀏覽器開 `http://你的靜態IP/`，應該會導向登入頁。

---

## 步驟 9 — 綁網域與 HTTPS

先到你的網域 DNS 新增一筆 A 記錄指向靜態 IP，等生效後：

```bash
sudo certbot --nginx -d erp.你的網域
```

憑證會自動續期。完成後把 `.env` 的 `app.baseURL` 改成 `https://…`、
`app.forceGlobalSecureRequests` 設為 `true`。

---

## 步驟 10 — 建立同事的帳號

用 admin 登入 → 側邊欄 **系統管理 → 使用者管理 → 新增**。

每個帳號都要選角色：

| 角色 | 能做什麼 |
|---|---|
| 管理者 | 全部，含使用者管理與操作紀錄 |
| 會計 | 帳務、報表、應收應付、發票可異動；營運單據唯讀 |
| 業務 | 客戶、報價、訂單、出貨可異動；進不去帳務 |
| 採購倉管 | 採購、進貨、庫存、生產可異動；應付唯讀，不能付款 |
| 唯讀 | 全部只能看（適合老闆、會計師、外部稽核） |

完整對照可在伺服器執行 `php spark erp:perm-check --matrix` 查看。

---

## 上線後的例行檢查

程式碼歸 `www-data` 所有、`.env` 是 600 權限，所以 CLI 指令要用 `sudo -u www-data` 執行；
直接用 ubuntu 身分跑會在讀 `.env` 時失敗（DotEnv 例外）。

```bash
cd /var/www/staton-erp

sudo -u www-data php spark erp:audit          # 資料一致性（庫存、單號、報表勾稽）
sudo -u www-data php spark erp:perm-check     # 角色權限規則
sudo -u www-data php spark erp:view-check     # 樣板渲染
sudo -u www-data php spark erp:lock-selftest  # 樂觀鎖
sudo -u www-data php spark erp:audit-selftest # 稽核軌跡

sudo tail -f /var/log/nginx/staton-erp.error.log   # 出錯時看這裡
sudo tail -50 writable/logs/log-$(date +%Y-%m-%d).log   # 應用程式錯誤
```

**備份**：每天 03:15 自動備份到 `/var/backups/staton-erp`。
請務必再設定異地同步（`deploy/backup.sh` 最後一行有 S3 的範例，把註解拿掉即可），
並**每季實際還原一次驗證** —— 沒驗過的備份等於沒有備份。

---

## 遇到「Whoops!」錯誤頁

```bash
cd /var/www/staton-erp
sed -i 's/^CI_ENVIRONMENT.*/CI_ENVIRONMENT = development/' .env   # 看完整堆疊
# 修好之後一定要改回來：
sed -i 's/^CI_ENVIRONMENT.*/CI_ENVIRONMENT = production/' .env
```

正式機**不可以**長期停留在 `development`，那會把程式碼路徑和資料庫結構顯示給訪客看。
