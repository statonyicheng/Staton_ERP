<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerDeliveryAddresses extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'cda_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
                'comment' => '送貨地址ID',
            ],
            'cda_c_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => '客戶 ID',
            ],
            'cda_name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => '地址名稱（如：總公司、分公司A）',
            ],
            'cda_contact_person' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => '收件人',
            ],
            'cda_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => '收件電話',
            ],
            'cda_address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => '送貨地址',
            ],
            'cda_is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '是否為預設地址',
            ],
            'cda_notes' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '備註',
            ],
            'cda_created_at' => [
                'type' => 'DATETIME',
                'comment' => '建立時間',
            ],
            'cda_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '更新時間',
            ],
        ]);

        $this->forge->addKey('cda_id', true);
        $this->forge->addForeignKey('cda_c_id', 'customers', 'c_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('customer_delivery_addresses',false,['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('customer_delivery_addresses');
    }
}

