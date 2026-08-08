<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePurchaseRequisitions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'pr_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'pr_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'comment' => '請購單號'],
            'pr_date' => ['type' => 'DATE', 'comment' => '請購日期'],
            'pr_dept' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => '請購單位'],
            'pr_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'comment' => '品名'],
            'pr_spec' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'comment' => '規格'],
            'pr_qty' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '數量'],
            'pr_unit' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => '單位'],
            'pr_need_date' => ['type' => 'DATE', 'null' => true, 'comment' => '需求日'],
            'pr_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '待處理', 'comment' => '狀態'],
            'pr_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => '備註'],
            'pr_created_at' => ['type' => 'DATETIME', 'null' => true],
            'pr_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('pr_id', true);
        $this->forge->addKey('pr_no');
        $this->forge->createTable('purchase_requisitions', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('purchase_requisitions');
    }
}
