<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerContacts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'cc_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
                'comment' => '聯絡人ID',
            ],
            'cc_c_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => '客戶ID',
            ],
            'cc_name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => '聯絡人姓名',
            ],
            'cc_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => '聯絡人手機',
            ],
            'cc_email' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '聯絡人Email',
            ],
            'cc_created_at' => [
                'type' => 'DATETIME',
                'comment' => '建立時間',
            ],
            'cc_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '更新時間',
            ],
        ]);

        $this->forge->addKey('cc_id', true);
        $this->forge->addForeignKey('cc_c_id', 'customers', 'c_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('customer_contacts', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('customer_contacts', true);
    }
}
