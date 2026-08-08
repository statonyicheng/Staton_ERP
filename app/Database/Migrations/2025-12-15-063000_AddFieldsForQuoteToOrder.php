<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldsForQuoteToOrder extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'o_cc_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => '聯絡人ID',
                'after' => 'o_c_id',
            ],
            'o_delivery_city' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => '送貨縣市',
                'after' => 'o_cc_id',
            ],
            'o_delivery_address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '送貨地址',
                'after' => 'o_delivery_city',
            ],
            'o_subtotal' => [
                'type' => 'FLOAT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '小計',
                'after' => 'o_delivery_address',
            ],
            'o_discount' => [
                'type' => 'FLOAT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '折扣',
                'after' => 'o_subtotal',
            ],
            'o_tax_rate' => [
                'type' => 'FLOAT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '稅率',
                'after' => 'o_discount',
            ],
            'o_shipping_fee' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '運費',
                'after' => 'o_tax_rate',
            ],
            'o_tax_amount' => [
                'type' => 'FLOAT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '稅額',
                'after' => 'o_shipping_fee',
            ],
            'o_total_amount' => [
                'type' => 'FLOAT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '總金額',
                'after' => 'o_tax_amount',
            ],
        ]);

        $this->forge->addForeignKey('o_cc_id', 'customer_contacts', 'cc_id', 'SET NULL', 'CASCADE');
    }

    public function down()
    {
        $this->forge->dropForeignKey('o_cc_id', 'customer_contacts', 'cc_id', 'SET NULL', 'CASCADE');
        $this->forge->dropColumn('orders', ['o_cc_id', 'o_delivery_city', 'o_delivery_address', 'o_subtotal', 'o_discount', 'o_tax_rate', 'o_shipping_fee', 'o_tax_amount', 'o_total_amount']);
    }
}
