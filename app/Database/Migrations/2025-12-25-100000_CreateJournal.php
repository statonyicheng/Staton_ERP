<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 傳統借貸分錄傳票（複式簿記）。
 * journal_vouchers = 傳票單頭；journal_entries = 借/貸分錄明細。
 * 每張傳票借方合計必須等於貸方合計（借貸平衡）。
 */
class CreateJournal extends Migration
{
    public function up()
    {
        // 分錄傳票 單頭
        $this->forge->addField([
            'jv_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'jv_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'comment' => '傳票號'],
            'jv_date' => ['type' => 'DATE', 'comment' => '傳票日期'],
            'jv_type' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => '轉帳', 'comment' => '傳票類別'],
            'jv_summary' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => '摘要'],
            'jv_amount' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '借貸方合計'],
            'jv_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'jv_created_at' => ['type' => 'DATETIME', 'null' => true],
            'jv_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('jv_id', true);
        $this->forge->addKey('jv_no');
        $this->forge->createTable('journal_vouchers', false, ['ENGINE' => 'InnoDB']);

        // 分錄明細
        $this->forge->addField([
            'je_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'je_jv_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '傳票'],
            'je_ac_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '會計科目'],
            'je_summary' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => '子摘要'],
            'je_debit' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '借方金額'],
            'je_credit' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '貸方金額'],
            'je_sort' => ['type' => 'INT', 'constraint' => 6, 'default' => 0],
        ]);
        $this->forge->addKey('je_id', true);
        $this->forge->addKey('je_jv_id');
        $this->forge->createTable('journal_entries', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('journal_entries');
        $this->forge->dropTable('journal_vouchers');
    }
}
