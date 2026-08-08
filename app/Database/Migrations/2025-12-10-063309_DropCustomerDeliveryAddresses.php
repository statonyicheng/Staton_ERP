<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropCustomerDeliveryAddresses extends Migration
{
    public function up()
    {
        // 先移除外鍵
        $this->forge->dropForeignKey('quotes', 'fk_quotes_delivery_address');
        $this->forge->dropForeignKey('orders', 'fk_orders_delivery_address');

        // 移除引用欄位
        $this->forge->dropColumn('quotes', 'q_cda_id');
        $this->forge->dropColumn('orders', 'o_cda_id');

        // 最後移除送貨地址表
        $this->forge->dropTable('customer_delivery_addresses', true);
    }

    public function down()
    {
        // 還原送貨地址表
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
            'cda_city' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
                'comment' => '縣市',
            ],
            'cda_address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '詳細地址',
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
        $this->forge->createTable('customer_delivery_addresses', false, ['ENGINE' => 'InnoDB']);

        // 還原 quotes / orders 欄位與外鍵
        $this->forge->addColumn('quotes', [
            'q_cda_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => '送貨地址ID',
                'after' => 'q_c_id',
            ],
        ]);

        $this->forge->addColumn('orders', [
            'o_cda_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => '送貨地址ID',
                'after' => 'o_c_id',
            ],
        ]);

        $this->db->query('ALTER TABLE quotes ADD CONSTRAINT fk_quotes_delivery_address FOREIGN KEY (q_cda_id) REFERENCES customer_delivery_addresses(cda_id) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE orders ADD CONSTRAINT fk_orders_delivery_address FOREIGN KEY (o_cda_id) REFERENCES customer_delivery_addresses(cda_id) ON DELETE SET NULL ON UPDATE CASCADE');
    }
}
