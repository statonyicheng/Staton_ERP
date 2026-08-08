<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRentalOrdersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ro_id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ro_type' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'ro_ma_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'ro_l_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'ro_date' => [
                'type'       => 'DATE',
            ]
        ]);
        $this->forge->addPrimaryKey('ro_id');
        $this->forge->createTable('rental_orders');
    }

    public function down()
    {
        $this->forge->dropTable('rental_orders');
    }
}