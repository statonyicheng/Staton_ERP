<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 交易明細加上資料來源標記。
 * 讓「匯入的公司內帳」可以重複匯入 / 單獨清除，而不會誤刪使用者在畫面上手動登錄的交易。
 * 金額仍維持整數（依需求不使用小數，匯入時四捨五入到元）。
 */
class AlterGlTransactionAmounts extends Migration
{
    public function up()
    {
        $this->forge->addColumn('gl_transactions', [
            't_source' => [
                'type' => 'VARCHAR', 'constraint' => 30, 'null' => true,
                'comment' => '資料來源：null=手動登錄 / internal_book=內帳匯入',
                'after' => 't_note',
            ],
        ]);
        $this->db->query('CREATE INDEX idx_gl_source ON gl_transactions (t_source)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_gl_source ON gl_transactions');
        $this->forge->dropColumn('gl_transactions', 't_source');
    }
}
