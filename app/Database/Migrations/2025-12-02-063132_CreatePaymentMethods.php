<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentMethods extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'pm_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
                'comment' => '結帳方式ID',
            ],
            'pm_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'unique' => true,
                'comment' => '結帳方式名稱',
            ],
            'pm_created_at' => [
                'type' => 'DATETIME',
                'comment' => '建立時間',
            ],
            'pm_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '更新時間',
            ],
        ]);

        $this->forge->addKey('pm_id', true);
        $this->forge->createTable('payment_methods', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('payment_methods');
    }
}
