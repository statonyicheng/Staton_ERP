<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrderItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'oi_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
                'comment' => '訂單明細ID',
            ],
            'oi_o_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => '訂單ID',
            ],
            'oi_p_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => '商品ID',
            ],
            'oi_quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '訂購數量',
            ],
            'oi_unit_price' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '單價',
            ],
            'oi_discount' => [
                'type' => 'FLOAT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '折扣(%)',
            ],
            'oi_amount' => [
                'type' => 'FLOAT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '金額',
            ],
            'oi_shipped_quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '已出貨數量',
            ],
            'oi_created_at' => [
                'type' => 'DATETIME',
                'comment' => '建立時間',
            ],
            'oi_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '更新時間',
            ],
        ]);

        $this->forge->addKey('oi_id', true);
        $this->forge->addForeignKey('oi_o_id', 'orders', 'o_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('oi_p_id', 'products', 'p_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('order_items',false,['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('order_items');
    }
}
