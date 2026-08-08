<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateManufacturersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ma_id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ma_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ]
        ]);
        $this->forge->addPrimaryKey('ma_id');
        $this->forge->createTable('manufacturers');
    }

    public function down()
    {
        $this->forge->dropTable('manufacturers');
    }
}