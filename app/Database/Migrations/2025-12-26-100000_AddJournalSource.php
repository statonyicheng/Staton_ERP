<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 分錄傳票加來源追蹤欄位，供「自動分錄」防止同一單據重複過帳。
 */
class AddJournalSource extends Migration
{
    public function up()
    {
        $this->forge->addColumn('journal_vouchers', [
            'jv_source_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => '來源單別', 'after' => 'jv_type'],
            'jv_source_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => '來源單ID', 'after' => 'jv_source_type'],
        ]);
        $this->forge->addKey(['jv_source_type', 'jv_source_id']);
    }

    public function down()
    {
        $this->forge->dropColumn('journal_vouchers', ['jv_source_type', 'jv_source_id']);
    }
}
