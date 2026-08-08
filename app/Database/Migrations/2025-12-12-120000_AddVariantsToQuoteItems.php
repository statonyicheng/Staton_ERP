<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVariantsToQuoteItems extends Migration
{
    public function up()
    {
        $fields = [
            'qi_supplier' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '供應商',
                'after' => 'qi_p_id',
            ],
            'qi_style' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '款式',
                'after' => 'qi_supplier',
            ],
            'qi_color' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '顏色/花色',
                'after' => 'qi_style',
            ],
            'qi_size' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '尺寸',
                'after' => 'qi_color',
            ],
        ];

        $this->forge->addColumn('quote_items', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('quote_items', ['qi_supplier', 'qi_style', 'qi_color', 'qi_size']);
    }
}

