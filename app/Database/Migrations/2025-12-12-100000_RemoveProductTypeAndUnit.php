<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveProductTypeAndUnit extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('products', ['p_type', 'p_unit']);
    }

    public function down()
    {
        $this->forge->addColumn('products', [
            'p_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'p_supplier',
                'comment' => '種類',
            ],
            'p_unit' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
                'comment' => '單位',
            ],
        ]);
    }
}

