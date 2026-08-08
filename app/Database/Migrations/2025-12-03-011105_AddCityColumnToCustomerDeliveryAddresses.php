<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCityColumnToCustomerDeliveryAddresses extends Migration
{
    public function up()
    {
        // 新增縣市欄位
        $this->forge->addColumn('customer_delivery_addresses', [
            'cda_city' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
                'comment' => '縣市',
                'after' => 'cda_phone',
            ],
        ]);

        // 修改地址欄位註釋
        $this->forge->modifyColumn('customer_delivery_addresses', [
            'cda_address' => [
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
        $this->forge->dropColumn('customer_delivery_addresses', 'cda_city');

        // 還原地址欄位註釋
        $this->forge->modifyColumn('customer_delivery_addresses', [
            'cda_address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '送貨地址',
            ],
        ]);
    }
}
