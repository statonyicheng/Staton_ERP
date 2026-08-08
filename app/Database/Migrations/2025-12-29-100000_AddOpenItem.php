<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 立沖帳(開放項目)：
 *  accounts.ac_open_item      — 此科目是否需逐筆立沖帳
 *  journal_entries.je_offset  — 該分錄已沖銷金額(未沖餘額 = 借或貸金額 − je_offset)
 */
class AddOpenItem extends Migration
{
    public function up()
    {
        $this->forge->addColumn('accounts', [
            'ac_open_item' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'comment' => '需立沖帳', 'after' => 'ac_is_pl'],
        ]);
        $this->forge->addColumn('journal_entries', [
            'je_offset' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '已沖銷金額', 'after' => 'je_credit'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('accounts', 'ac_open_item');
        $this->forge->dropColumn('journal_entries', 'je_offset');
    }
}
