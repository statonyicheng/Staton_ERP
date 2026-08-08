<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyCustomersCNameNullable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('customers', [
            'c_name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => '客戶名稱',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('customers', [
            'c_name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => '客戶名稱',
            ],
        ]);
    }
}
