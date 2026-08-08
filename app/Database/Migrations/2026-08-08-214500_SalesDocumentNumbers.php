<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 把訂單／報價／出貨單也納入單號防重複機制。
 *
 * `CreateDocumentSequences` 當初只處理了 8 種單據，銷售這條線（訂單/報價/出貨）
 * 被漏掉了 —— 它們仍是「SELECT 最大號 → PHP 加一 → INSERT」，中間沒有鎖，
 * 兩人同一秒開單就會拿到同一個號，而且欄位連 UNIQUE 索引都沒有，會安靜地重號。
 *
 * 本 migration：
 *  1. 單號欄位放寬到 VARCHAR(20)（新格式 O20260808-001 是 13 碼，舊的 12 碼裝不下）
 *  2. 依現有單號回填計數器（新舊兩種格式都認），避免新號撞舊號
 *  3. 補上 UNIQUE 索引當最後一道防線
 */
class SalesDocumentNumbers extends Migration
{
    /** [表, 單號欄位] */
    private const TARGETS = [
        ['orders',    'o_number'],
        ['quotes',    'q_number'],
        ['shipments', 's_number'],
    ];

    public function up()
    {
        // ---------- 1. 欄位放寬 ----------
        foreach (self::TARGETS as [$table, $col]) {
            if (! $this->db->tableExists($table)) continue;
            $this->forge->modifyColumn($table, [
                $col => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            ]);
        }

        // ---------- 2. 依現有單號回填計數器 ----------
        $seq = [];
        foreach (self::TARGETS as [$table, $col]) {
            if (! $this->db->tableExists($table)) continue;
            foreach ($this->db->table($table)->select($col)->get()->getResultArray() as $r) {
                $no = (string) ($r[$col] ?? '');
                if ($no === '') continue;

                // 新格式：PREFIX + Ymd + '-' + nnn
                if (preg_match('/^([A-Z]+)(\d{8})-(\d+)$/', $no, $m)) {
                    [$scope, $period, $n] = [$m[1], $m[2], (int) $m[3]];
                }
                // 舊格式：PREFIX + Ymd + nnn（沒有連字號）
                elseif (preg_match('/^([A-Z]+)(\d{8})(\d{3})$/', $no, $m)) {
                    [$scope, $period, $n] = [$m[1], $m[2], (int) $m[3]];
                } else {
                    continue;
                }

                $seq[$scope][$period] = max($seq[$scope][$period] ?? 0, $n);
            }
        }

        $now = date('Y-m-d H:i:s');
        foreach ($seq as $scope => $periods) {
            foreach ($periods as $period => $max) {
                // 已有計數器就只在落後時往上補，不可倒退
                $this->db->query(
                    'INSERT INTO document_sequences (ds_scope, ds_period, ds_last_no, ds_updated_at)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        ds_last_no = GREATEST(ds_last_no, VALUES(ds_last_no)),
                        ds_updated_at = VALUES(ds_updated_at)',
                    [$scope, (string) $period, $max, $now]
                );
            }
        }

        // ---------- 3. UNIQUE 索引 ----------
        foreach (self::TARGETS as [$table, $col]) {
            if (! $this->db->tableExists($table) || $this->indexExists($table, 'uniq_' . $col)) continue;
            $this->db->query("ALTER TABLE `{$table}` ADD UNIQUE INDEX `uniq_{$col}` (`{$col}`)");
        }
    }

    public function down()
    {
        foreach (self::TARGETS as [$table, $col]) {
            if (! $this->db->tableExists($table) || ! $this->indexExists($table, 'uniq_' . $col)) continue;
            $this->db->query("ALTER TABLE `{$table}` DROP INDEX `uniq_{$col}`");
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) c FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
            [$table, $index]
        )->getRow()->c > 0;
    }
}
