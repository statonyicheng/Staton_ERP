<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCityColumnToCustomers extends Migration
{
    public function up()
    {
        // 新增縣市欄位
        $this->forge->addColumn('customers', [
            'c_city' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
                'comment' => '縣市',
                'after' => 'c_email',
            ],
        ]);

        // 修改地址欄位註釋
        $this->forge->modifyColumn('customers', [
            'c_address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '詳細地址',
            ],
        ]);
    }

    public function down()
    {
        // 移除縣市欄位
        $this->forge->dropColumn('customers', 'c_city');

        // 還原地址欄位註釋
        $this->forge->modifyColumn('customers', [
            'c_address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '地址',
            ],
        ]);
    }
}
