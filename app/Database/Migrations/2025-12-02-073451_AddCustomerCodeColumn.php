<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomerCodeColumn extends Migration
{
    public function up()
    {
        $this->forge->addColumn('customers', [
            'c_code' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'unique' => true,
                'comment' => '客戶編號',
                'after' => 'c_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('customers', 'c_code');
    }
}
