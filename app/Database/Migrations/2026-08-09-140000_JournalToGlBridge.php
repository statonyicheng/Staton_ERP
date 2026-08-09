<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 反向橋接：讓「分錄傳票」也能餵養收付制報表。
 *
 * 原本資料只從 gl_transactions 單向流到 journal_vouchers（GlJournalPoster）。
 * 現在要把分錄傳票變成唯一的輸入口，就必須反過來：存傳票時同步產生對應的
 * 收付交易，否則四階損益分析與資金餘額表會**靜靜地變成 0**（它們讀的是
 * gl_transactions，不是傳票）—— 不會報錯，是最難發現的那種錯。
 *
 * 本 migration 加兩個欄位：
 *   journal_vouchers.jv_segment  業務別（M-0~M-5）。四階損益要按業務別分欄，
 *                                傳票上沒有這個資訊就分不出來，只能全歸共用。
 *   gl_transactions.t_jv_id      來源傳票 id。有了它，傳票被修改或刪除時
 *                                才找得到要一起更新／回收的那筆收付交易，
 *                                也才不會重複產生。
 *
 * 既有由 gl 過帳產生的傳票，會把來源交易的業務別回填到 jv_segment，
 * 這樣新舊資料的口徑一致，round-trip 驗證才有意義。
 */
class JournalToGlBridge extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('jv_segment', 'journal_vouchers')) {
            $this->forge->addColumn('journal_vouchers', [
                'jv_segment' => [
                    'type' => 'VARCHAR', 'constraint' => 12, 'null' => false, 'default' => 'M-0',
                    'comment' => '業務別（對應 gl_transactions.t_segment）', 'after' => 'jv_type',
                ],
            ]);
        }

        if (! $this->db->fieldExists('t_jv_id', 'gl_transactions')) {
            $this->forge->addColumn('gl_transactions', [
                't_jv_id' => [
                    'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true,
                    'comment' => '來源分錄傳票 id（由傳票反向產生時填入）', 'after' => 't_source',
                ],
            ]);
        }

        $this->db->resetDataCache();

        if (! $this->indexExists('gl_transactions', 'gl_transactions_t_jv_id')) {
            $this->db->query('ALTER TABLE `gl_transactions` ADD INDEX `gl_transactions_t_jv_id` (`t_jv_id`)');
        }

        // 既有 gl 過帳產生的傳票：把來源交易的業務別回填回去
        $this->db->query(
            'UPDATE journal_vouchers v
               JOIN gl_transactions t ON t.t_id = v.jv_source_id
                SET v.jv_segment = t.t_segment
              WHERE v.jv_source_type = ?',
            ['gl']
        );
    }

    public function down()
    {
        if ($this->indexExists('gl_transactions', 'gl_transactions_t_jv_id')) {
            $this->db->query('ALTER TABLE `gl_transactions` DROP INDEX `gl_transactions_t_jv_id`');
        }
        if ($this->db->fieldExists('t_jv_id', 'gl_transactions')) {
            $this->forge->dropColumn('gl_transactions', 't_jv_id');
        }
        if ($this->db->fieldExists('jv_segment', 'journal_vouchers')) {
            $this->forge->dropColumn('journal_vouchers', 'jv_segment');
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
