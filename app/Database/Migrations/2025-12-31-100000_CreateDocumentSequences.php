<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 單號防重複機制。
 *
 * 原本 8 支 generateNo() 都是「先查最大號 → 再寫入」，中間沒有任何鎖：
 * 單人使用永遠不會出事，但兩個人同一秒儲存就會拿到同一個單號，
 * 而且單號欄位沒有 UNIQUE 索引，會安靜地產生重複單號（重複的發票號碼尤其嚴重）。
 *
 * 本 migration 做兩件事：
 *  1. 建立 document_sequences 計數表，取號改為單一 SQL 原子操作（見 App\Libraries\DocumentNumber）。
 *  2. 在 8 個單號欄位加上 UNIQUE 索引，作為最後一道防線 —— 就算日後邏輯再出包，
 *     資料庫也會直接擋下來，不會讓髒資料落地。
 *
 * 建表後會依現有資料回填計數器，避免新號碼與既有單號相撞。
 */
class CreateDocumentSequences extends Migration
{
    /** [表, 單號欄位, 取號範圍(scope) 的解析方式] */
    private const TARGETS = [
        ['purchase_orders',       'po_no'],
        ['journal_vouchers',      'jv_no'],
        ['payables',              'ap_no'],
        ['receivables',           'ar_no'],
        ['settlements',           'st_no'],
        ['work_orders',           'wo_no'],
        ['purchase_requisitions', 'pr_no'],
        ['invoices',              'inv_number'],
    ];

    public function up()
    {
        // ---------- 1. 計數表 ----------
        $this->forge->addField([
            'ds_scope'      => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => '單別：PO/JV/AP/AR/PAY/REC/WO/PR/INV'],
            'ds_period'     => ['type' => 'VARCHAR', 'constraint' => 10, 'comment' => '期別：日單號為 Ymd，不分期者為空字串'],
            'ds_last_no'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0, 'comment' => '已配發到第幾號'],
            'ds_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['ds_scope', 'ds_period'], true);
        $this->forge->createTable('document_sequences', false, ['ENGINE' => 'InnoDB']);

        // ---------- 2. 依現有單號回填計數器 ----------
        $seq = [];   // [scope][period] = max
        foreach (self::TARGETS as [$table, $col]) {
            if (!$this->db->tableExists($table)) continue;
            foreach ($this->db->table($table)->select($col)->get()->getResultArray() as $r) {
                $no = (string) ($r[$col] ?? '');
                if ($no === '') continue;

                // 日單號：PREFIX + Ymd + '-' + nnn
                if (preg_match('/^([A-Z]+)(\d{8})-(\d+)$/', $no, $m)) {
                    $scope = $m[1]; $period = $m[2]; $n = (int) $m[3];
                }
                // 發票：AA + 8 碼流水（不分期）
                elseif (preg_match('/^([A-Z]{2})(\d+)$/', $no, $m)) {
                    $scope = 'INV'; $period = ''; $n = (int) $m[2];
                } else {
                    continue;
                }
                $seq[$scope][$period] = max($seq[$scope][$period] ?? 0, $n);
            }
        }

        $now = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($seq as $scope => $periods) {
            foreach ($periods as $period => $max) {
                $rows[] = ['ds_scope' => $scope, 'ds_period' => (string) $period, 'ds_last_no' => $max, 'ds_updated_at' => $now];
            }
        }
        if ($rows) $this->db->table('document_sequences')->insertBatch($rows);

        // ---------- 3. 單號欄位加 UNIQUE ----------
        foreach (self::TARGETS as [$table, $col]) {
            if (!$this->db->tableExists($table)) continue;
            $idx = 'uniq_' . $col;
            // 已存在同名索引就跳過，讓 migration 可重複執行
            $exists = $this->db->query(
                "SELECT COUNT(*) c FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
                [$table, $idx]
            )->getRow()->c;
            if ($exists) continue;
            $this->db->query("ALTER TABLE `{$table}` ADD UNIQUE INDEX `{$idx}` (`{$col}`)");
        }
    }

    public function down()
    {
        foreach (self::TARGETS as [$table, $col]) {
            if (!$this->db->tableExists($table)) continue;
            $idx = 'uniq_' . $col;
            $exists = $this->db->query(
                "SELECT COUNT(*) c FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
                [$table, $idx]
            )->getRow()->c;
            if ($exists) $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$idx}`");
        }
        $this->forge->dropTable('document_sequences', true);
    }
}
