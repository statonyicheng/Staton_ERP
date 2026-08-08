<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWarehouses extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'w_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'w_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'comment' => '倉庫代號'],
            'w_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'comment' => '倉庫名稱'],
            'w_location' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => '存放位置'],
            'w_manager' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => '倉管人員'],
            'w_is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => '是否啟用'],
            'w_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => '備註'],
            'w_created_at' => ['type' => 'DATETIME', 'null' => true],
            'w_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('w_id', true);
        $this->forge->addKey('w_code');
        $this->forge->createTable('warehouses', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('warehouses');
    }
}
