<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 會計交易明細（收付實現制）
 * 綜合【嵐石】財務架構之「銷項總表（收入）＋進項總表（支出）」為單一交易表，
 * 每筆掛一個會計科目與業務別；四階損益分析、資金餘額表、總帳皆由本表計算。
 */
class CreateGlTransactions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            't_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            't_date' => ['type' => 'DATE', 'comment' => '交易日期'],
            't_ym' => ['type' => 'VARCHAR', 'constraint' => 7, 'comment' => '年月 yyyy-mm'],
            't_summary' => ['type' => 'VARCHAR', 'constraint' => 255, 'comment' => '摘要/品名'],
            't_partner' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => '對象(客戶/廠商)'],
            't_direction' => ['type' => 'VARCHAR', 'constraint' => 4, 'default' => '收', 'comment' => '收付方向: 收/付'],
            't_segment' => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'M-0', 'comment' => '業務別 M-0~M-5/非營業'],
            't_ac_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '會計科目'],
            't_amount' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '未稅金額'],
            't_tax' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '營業稅'],
            't_settle_status' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => '已收付', 'comment' => '收付狀態'],
            't_settle_date' => ['type' => 'DATE', 'null' => true, 'comment' => '收付日期'],
            't_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => '備註'],
            't_created_at' => ['type' => 'DATETIME', 'null' => true],
            't_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('t_id', true);
        $this->forge->addKey('t_ym');
        $this->forge->addKey('t_ac_id');
        $this->forge->createTable('gl_transactions', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('gl_transactions');
    }
}
