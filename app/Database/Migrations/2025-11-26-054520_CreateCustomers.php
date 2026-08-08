<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'c_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'c_name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => '客戶名稱',
            ],
            'c_manager' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => '負責人',
            ],
            'c_contact_person' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => '聯絡人',
            ],
            'c_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => '電話',
            ],
            'c_fax' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => '傳真號碼',
            ],
            'c_email' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Email',
            ],
            'c_address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '地址',
            ],
            'c_tax_id' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
                'comment' => '統一編號',
            ],
            'c_payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
                'comment' => '結帳方式',
            ],
            'c_notes' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '備註',
            ],
            'c_created_at' => [
                'type' => 'DATETIME',
                'comment' => '建立時間',
            ],
            'c_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '更新時間',
            ],
        ]);

        $this->forge->addKey('c_id', true);
        $this->forge->createTable('customers',false,['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('customers');
    }
}
