<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMinorCategoriesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'mic_id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'mic_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'mic_mac_id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'null'           => true,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addPrimaryKey('mic_id');
        $this->forge->addForeignKey('mic_mac_id', 'major_categories', 'mac_id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('minor_categories');
    }

    public function down()
    {
        $this->forge->dropTable('minor_categories');
    }
} 