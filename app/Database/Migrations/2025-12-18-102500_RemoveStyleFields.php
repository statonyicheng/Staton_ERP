<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveStyleFields extends Migration
{
    public function up()
    {
        // Remove p_style from products table
        $this->forge->dropColumn('products', 'p_style');

        // Remove qi_style from quote_items table
        $this->forge->dropColumn('quote_items', 'qi_style');

        // Remove oi_style from order_items table
        $this->forge->dropColumn('order_items', 'oi_style');
    }

    public function down()
    {
        // Add p_style back to products table
        $this->forge->addColumn('products', [
            'p_style' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'p_supplier'
            ]
        ]);

        // Add qi_style back to quote_items table
        $this->forge->addColumn('quote_items', [
            'qi_style' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'qi_supplier'
            ]
        ]);

        // Add oi_style back to order_items table
        $this->forge->addColumn('order_items', [
            'oi_style' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'oi_supplier'
            ]
        ]);
    }
}
