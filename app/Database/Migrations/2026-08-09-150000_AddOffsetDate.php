<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 立沖帳記錄「沖銷日期」。
 *
 * 原本 `je_offset` 只記沖了多少錢，沒有記哪一天沖的。金額夠用於立沖帳餘額表，
 * 但**收付制報表需要日期**：賒銷 8 月立帳、10 月才收到錢，資金餘額表要把這筆
 * 算在 10 月，不是 8 月。沒有日期就只能拿立帳日湊數，月份會錯。
 *
 * 有了它，JournalGlPoster 才能把「應收已全額沖銷」的傳票推導成
 * 「已收付、收付日＝沖銷日」，資金餘額表與四階損益的期間才對得起來。
 */
class AddOffsetDate extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('je_offset_date', 'journal_entries')) {
            return;
        }

        $this->forge->addColumn('journal_entries', [
            'je_offset_date' => [
                'type' => 'DATE', 'null' => true,
                'comment' => '最後一次沖銷的日期（收付制報表用它認列收付月份）',
                'after' => 'je_offset',
            ],
        ]);

        // 既有已沖銷的項目沒有日期可考，用它所屬傳票的日期回填，
        // 至少讓資料完整；日後新沖銷的都會有正確日期。
        $this->db->query(
            'UPDATE journal_entries e
               JOIN journal_vouchers v ON v.jv_id = e.je_jv_id
                SET e.je_offset_date = v.jv_date
              WHERE e.je_offset > 0 AND e.je_offset_date IS NULL'
        );
    }

    public function down()
    {
        if ($this->db->fieldExists('je_offset_date', 'journal_entries')) {
            $this->forge->dropColumn('journal_entries', 'je_offset_date');
        }
    }
}
