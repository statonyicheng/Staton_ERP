<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvoices extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'inv_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'inv_number' => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => '發票號碼'],
            'inv_date' => ['type' => 'DATE', 'comment' => '開立日期'],
            'inv_c_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => '買受客戶'],
            'inv_buyer' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => '買方名稱'],
            'inv_buyer_tax' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => '買方統編'],
            'inv_ref_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'comment' => '來源單號'],
            'inv_amount' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '未稅金額'],
            'inv_tax' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '營業稅'],
            'inv_total' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '含稅總計'],
            'inv_status' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => '已開立', 'comment' => '狀態'],
            'inv_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'inv_created_at' => ['type' => 'DATETIME', 'null' => true],
            'inv_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('inv_id', true);
        $this->forge->addKey('inv_number');
        $this->forge->createTable('invoices', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('invoices');
    }
}
