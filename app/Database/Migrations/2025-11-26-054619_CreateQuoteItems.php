<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuoteItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'qi_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'qi_q_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => '報價單ID',
            ],
            'qi_p_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => '商品ID',
            ],
            'qi_quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '數量',
            ],
            'qi_unit_price' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '單價',
            ],
            'qi_discount' => [
                'type' => 'FLOAT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '折扣',
            ],
            'qi_amount' => [
                'type' => 'FLOAT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '金額',
            ],
            'qi_created_at' => [
                'type' => 'DATETIME',
                'comment' => '建立時間',
            ],
            'qi_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '更新時間',
            ],
        ]);

        $this->forge->addKey('qi_id', true);
        $this->forge->addForeignKey('qi_q_id', 'quotes', 'q_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('qi_p_id', 'products', 'p_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('quote_items',false,['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('quote_items');
    }
}
