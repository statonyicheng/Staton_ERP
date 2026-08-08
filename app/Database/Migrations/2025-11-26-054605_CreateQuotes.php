<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuotes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'q_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'q_number' => [
                'type' => 'VARCHAR',
                'constraint' => 12,
                'comment' => '報價單號',
            ],
            'q_date' => [
                'type' => 'DATE',
                'comment' => '報價日期',
            ],
            'q_valid_date' => [
                'type' => 'DATE',
                'null' => true,
                'comment' => '有效日期',
            ],
            'q_c_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => '客戶ID',
            ],
            'q_subtotal' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '小計(未稅)',
            ],
            'q_discount' => [
                'type' => 'FLOAT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '折扣',
            ],
            'q_tax_rate' => [
                'type' => 'FLOAT',
                'constraint' => 11,
                'default' => 0.05,
                'comment' => '稅率',
            ],
            'q_tax_amount' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '稅額',
            ],
            'q_total_amount' => [
                'type' => 'FLOAT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '總金額',
            ],
            'q_notes' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '備註',
            ],
            'q_o_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => '訂單ID',
            ],
            'q_created_at' => [
                'type' => 'DATETIME',
                'comment' => '建立時間',
            ],
            'q_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '更新時間',
            ],
        ]);

        $this->forge->addKey('q_id', true);
        $this->forge->addForeignKey('q_c_id', 'customers', 'c_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('quotes',false,['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('quotes');
    }
}
