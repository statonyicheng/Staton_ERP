<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'u_id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'u_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'u_account' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'unique'     => true,
            ],
            'u_password' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'u_level' => [
                'type'       => 'INT',
                'constraint' => 5,
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
        $this->forge->addPrimaryKey('u_id');
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
} 