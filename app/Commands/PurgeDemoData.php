<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 清除建置期留下的測試資料。
 *
 * 系統開發過程中為了驗證各模組，灌了一批石材業的假單據（商品 A001/A002/F001、
 * 採購單 PO20260807-001、製令、應付、付款、幾張手開傳票）。仕坦登現在拿這套系統
 * 記自己的顧問業內帳，這些假資料混在裡面會讓**四大財務報表與庫存報表都是錯的**
 * —— 例如資產負債表的存貨 52,500 和應付 57,500 全是測試留下的。
 *
 * 用法：
 *   php spark erp:purge-demo-data --dry-run   # 先看會刪什麼（強烈建議先跑這個）
 *   php spark erp:purge-demo-data
 *
 * 設計原則：**用明確清單鎖定**，不用「日期早於某天」「來源為空」這類模糊條件 ——
 * 模糊條件會在日後誤傷使用者自己開的單據。清單外的任何資料都不會被碰到。
 *
 * 不刪的東西：
 *   · 倉庫「主倉庫」—— 出貨扣庫存需要至少一個啟用中的倉庫，刪掉會讓銷貨出庫
 *     靜靜地不扣帳（ShipmentModel::postSalesStock 找不到倉庫就直接 return）
 *   · 會計科目、客戶、內帳交易（t_source='internal_book'）
 */
class PurgeDemoData extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:purge-demo-data';
    protected $description = '清除建置期的測試單據與商品（用明確清單，不會誤傷真實資料）';
    protected $usage       = 'erp:purge-demo-data [--dry-run]';
    protected $options     = ['--dry-run' => '只列出會刪什麼，不實際刪除'];

    /** 要清掉的測試傳票（含由測試採購單自動產生的那張） */
    private const VOUCHER_NOS = [
        'JV20260807-001',   // 銷貨收現測試
        'JV20260807-002',   // 進貨 PO20260807-001（測試採購單的自動分錄）
        'JVOI-001',         // 立沖帳示範：A客戶賒銷立帳
        'JVOI-002',         // 立沖帳示範：A客戶部分收款
        'JV20260808-001',   // 租金支出測試
    ];

    private const PRODUCT_CODES  = ['A001', 'A002', 'F001'];
    private const PO_NOS         = ['PO20260807-001'];
    private const AP_NOS         = ['AP20260807-001'];
    private const SETTLEMENT_NOS = ['PAY20260807-001'];
    private const WO_NOS         = ['WO20260807-001'];
    private const SUPPLIER_CODES = ['SUP0001'];   // 巨峰石材有限公司（石材業測試廠商）

    private $db;
    private bool $dryRun = false;
    private array $log = [];

    public function run(array $params)
    {
        $this->dryRun = (bool) CLI::getOption('dry-run');
        $this->db = \Config\Database::connect();

        $before = $this->snapshot();

        // 刪除順序必須由「被參照的一方最後刪」往回推，否則外鍵會擋住整筆交易 ——
        // 而且 CI4 的 transComplete() 遇到失敗是整批回滾，畫面卻什麼都刪不掉。
        $this->db->transStart();

        // ---- 1. 傳票（連同分錄） ----
        $jvIds = $this->idsOf('journal_vouchers', 'jv_id', 'jv_no', self::VOUCHER_NOS);
        $this->purge('journal_entries', 'je_jv_id', $jvIds, '傳票分錄');
        $this->purge('journal_vouchers', 'jv_id', $jvIds, '分錄傳票');

        // ---- 2. 收付款 → 應付（收付款單參照應付） ----
        $this->purge('settlements', 'st_id', $this->idsOf('settlements', 'st_id', 'st_no', self::SETTLEMENT_NOS), '收付款單');
        $this->purge('payables', 'ap_id', $this->idsOf('payables', 'ap_id', 'ap_no', self::AP_NOS), '應付帳款');

        // ---- 3. 採購單明細 → 採購單（明細參照採購單與商品） ----
        $poIds = $this->idsOf('purchase_orders', 'po_id', 'po_no', self::PO_NOS);
        $this->purge('purchase_order_items', 'poi_po_id', $poIds, '採購單明細');
        $this->purge('purchase_orders', 'po_id', $poIds, '採購單');

        // ---- 4. 製令（參照商品） ----
        $this->purge('work_orders', 'wo_id', $this->idsOf('work_orders', 'wo_id', 'wo_no', self::WO_NOS), '製令');

        // ---- 5. 商品的庫存足跡與 BOM，最後才刪商品本身 ----
        $pIds = $this->idsOf('products', 'p_id', 'p_code', self::PRODUCT_CODES);
        // 商品若已被人從畫面刪掉（系統沒擋），庫存與異動會變成孤兒，一併清掉
        $orphanStock = array_column($this->db->query(
            'SELECT ps_id FROM product_stock s LEFT JOIN products p ON p.p_id = s.ps_p_id WHERE p.p_id IS NULL'
        )->getResultArray(), 'ps_id');
        $orphanMoves = array_column($this->db->query(
            'SELECT sm_id FROM stock_movements m LEFT JOIN products p ON p.p_id = m.sm_p_id WHERE p.p_id IS NULL'
        )->getResultArray(), 'sm_id');

        $this->purge('stock_movements', 'sm_p_id', $pIds, '庫存異動');
        $this->purge('product_stock', 'ps_p_id', $pIds, '在庫量');
        $this->purge('stock_movements', 'sm_id', $orphanMoves, '庫存異動（商品已不存在）');
        $this->purge('product_stock', 'ps_id', $orphanStock, '在庫量（商品已不存在）');
        $this->purge('bom_items', 'bi_parent_p_id', $pIds, 'BOM 母件');
        $this->purge('bom_items', 'bi_child_p_id', $pIds, 'BOM 子件');
        $this->purge('products', 'p_id', $pIds, '商品');

        // ---- 6. 測試廠商（採購單已刪，這時才能刪） ----
        $this->purge('suppliers', 's_id', $this->idsOf('suppliers', 's_id', 's_code', self::SUPPLIER_CODES), '廠商');

        $committed = true;
        if ($this->dryRun) {
            $this->db->transRollback();
        } else {
            $this->db->transComplete();
            $committed = $this->db->transStatus() !== false;
        }

        // 交易失敗時一定要講出來 —— 否則會印出「已刪除」但其實整批被回滾
        if (! $committed) {
            CLI::error('刪除失敗，資料已全部回復（通常是外鍵擋住：某張單據還參照著要刪的資料）');
            CLI::write('原本打算刪除：', 'yellow');
            foreach ($this->log as $line) CLI::write('    ' . $line, 'yellow');
            return;
        }

        // ---- 報告 ----
        CLI::write($this->dryRun ? '【試算】以下資料將被刪除：' : '已刪除的資料：', 'light_blue');
        if ($this->log === []) {
            CLI::write('    （沒有找到任何測試資料，可能已經清過了）', 'dark_gray');
        }
        foreach ($this->log as $line) {
            CLI::write('    ' . $line, $this->dryRun ? 'yellow' : 'green');
        }

        $after = $this->dryRun ? $before : $this->snapshot();

        // 真的刪掉了嗎：直接回頭數一次，不靠「指令有沒有報錯」
        if (! $this->dryRun) {
            $left = (int) $this->db->table('journal_vouchers')->whereIn('jv_no', self::VOUCHER_NOS)->countAllResults()
                  + (int) $this->db->table('products')->whereIn('p_code', self::PRODUCT_CODES)->countAllResults();
            CLI::newLine();
            CLI::write($left === 0 ? '複查：測試傳票與測試商品皆已不存在' : "⚠ 複查：仍有 {$left} 筆測試資料存在，請重跑或人工確認",
                $left === 0 ? 'green' : 'red');
        }

        CLI::newLine();
        CLI::write('內帳資料（不受影響）', 'light_blue');
        CLI::write("    收付交易 internal_book：{$before['internal']} → {$after['internal']} 筆", 'dark_gray');
        CLI::write("    由內帳過帳的傳票：{$before['glVouchers']} → {$after['glVouchers']} 張", 'dark_gray');
        CLI::write("    客戶：{$before['customers']} → {$after['customers']} 筆", 'dark_gray');

        CLI::newLine();
        CLI::write(str_repeat('=', 58), 'dark_gray');
        CLI::write(
            $this->dryRun
                ? '試算完成，尚未寫入。確認無誤後拿掉 --dry-run 再跑一次。'
                : '清除完成。請接著跑 php spark erp:audit 確認報表勾稽。',
            $this->dryRun ? 'yellow' : 'green'
        );
    }

    /** 依代號查 id */
    private function idsOf(string $table, string $pk, string $col, array $values): array
    {
        if (! $this->db->tableExists($table)) return [];

        return array_map('intval', array_column(
            $this->db->table($table)->select($pk)->whereIn($col, $values)->get()->getResultArray(),
            $pk
        ));
    }

    /** 刪除並記錄筆數（id 清單為空就跳過，不會下無條件 delete） */
    private function purge(string $table, string $col, array $ids, string $label): void
    {
        if ($ids === [] || ! $this->db->tableExists($table)) return;

        // 欄位名稱打錯會讓整筆交易失敗、全部回滾，而且只有本機剛好跳過那段時才不會發作
        if (! $this->db->fieldExists($col, $table)) {
            CLI::error("設定錯誤：資料表 {$table} 沒有欄位 {$col}（{$label}）");
            throw new \RuntimeException("欄位不存在：{$table}.{$col}");
        }

        $n = $this->db->table($table)->whereIn($col, $ids)->countAllResults(false);
        if ($n === 0) return;

        $this->db->table($table)->whereIn($col, $ids)->delete();
        $this->log[] = sprintf('%s：%d 筆', $label, $n);
    }

    private function snapshot(): array
    {
        return [
            'internal'   => (int) $this->db->table('gl_transactions')->where('t_source', 'internal_book')->countAllResults(),
            'glVouchers' => (int) $this->db->table('journal_vouchers')->where('jv_source_type', 'gl')->countAllResults(),
            'customers'  => (int) $this->db->table('customers')->countAllResults(),
        ];
    }
}
