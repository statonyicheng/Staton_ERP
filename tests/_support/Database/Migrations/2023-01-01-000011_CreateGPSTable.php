<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGPSTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'g_id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'g_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ]
        ]);
        $this->forge->addPrimaryKey('g_id');
        $this->forge->createTable('gps');
    }

    public function down()
    {
        $this->forge->dropTable('gps');
    }
}