<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRentalOrderDetailsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'rod_id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'rod_ro_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'rod_pr_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'rod_qty' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'rod_length' => [
                'type'       => 'DOUBLE',
                'constraint' => 10,
                'default'    => 0,
            ],
            'rod_weight' => [
                'type'       => 'DOUBLE',
                'constraint' => 10,
                'default'    => 0,
            ]
        ]);
        $this->forge->addPrimaryKey('rod_id');
        $this->forge->createTable('rental_order_details');
    }

    public function down()
    {
        $this->forge->dropTable('rental_order_details');
    }
}