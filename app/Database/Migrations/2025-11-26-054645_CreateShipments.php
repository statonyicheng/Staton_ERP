<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateShipments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            's_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
                'comment' => '出貨單ID',
            ],
            's_o_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => '訂單ID',
            ],
            's_number' => [
                'type' => 'VARCHAR',
                'constraint' => 12,
                'comment' => '出貨單號',
            ],
            's_date' => [
                'type' => 'DATE',
                'comment' => '出貨日期',
            ],
            's_status' => [
                'type' => 'ENUM',
                'constraint' => ['preparing', 'partial', 'completed'],
                'default' => 'preparing',
                'comment' => '出貨狀態',
            ],
            's_notes' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '備註',
            ],
            's_created_at' => [
                'type' => 'DATETIME',
                'comment' => '建立時間',
            ],
            's_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '更新時間',
            ],
        ]);

        $this->forge->addKey('s_id', true);
        $this->forge->addForeignKey('s_o_id', 'orders', 'o_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('shipments',false,['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('shipments');
    }
}
