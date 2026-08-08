<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddShippingFeeToQuotesOrders extends Migration
{
    public function up()
    {
        // 為 quotes 表添加運費欄位
        $fields = [
            'q_shipping_fee' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '運費',
                'after' => 'q_tax_rate',
            ],
            'q_delivery_city' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => '送貨縣市',
                'after' => 'q_c_id', // 放在客戶ID之後
            ],
            'q_delivery_address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '送貨地址',
                'after' => 'q_delivery_city',
            ],
        ];
        $this->forge->addColumn('quotes', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('quotes', ['q_delivery_city', 'q_delivery_address', 'q_shipping_fee']);
    }
}

