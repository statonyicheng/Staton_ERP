<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldOrderItem extends Migration
{
    public function up()
    {
        $this->forge->addColumn('order_items', [
            'oi_supplier' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '供應商',
                'after' => 'oi_p_id',
            ],
            'oi_style' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '款式',
                'after' => 'oi_supplier',
            ],
            'oi_color' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '顏色/花色',
                'after' => 'oi_style',
            ],
            'oi_size' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '尺寸',
                'after' => 'oi_color',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('order_items', ['oi_supplier', 'oi_style', 'oi_color', 'oi_size']);
    }
}
