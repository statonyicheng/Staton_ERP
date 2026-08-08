<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSuppliers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            's_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            's_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'comment' => '廠商編號'],
            's_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'comment' => '廠商名稱'],
            's_tax_id' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => '統一編號'],
            's_contact' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => '聯絡人'],
            's_phone' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => '電話'],
            's_fax' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => '傳真'],
            's_email' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => '電子郵件'],
            's_address' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => '地址'],
            's_pm_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => '付款條件(結帳方式)'],
            's_note' => ['type' => 'TEXT', 'null' => true, 'comment' => '備註'],
            's_created_at' => ['type' => 'DATETIME', 'null' => true],
            's_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('s_id', true);
        $this->forge->addKey('s_code');
        $this->forge->createTable('suppliers', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('suppliers');
    }
}
