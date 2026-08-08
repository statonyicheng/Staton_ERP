<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInventoriesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'i_id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'i_pr_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'i_l_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'i_initial' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'i_qty' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'i_create_by' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'i_update_by' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'i_update_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
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
        $this->forge->addPrimaryKey('i_id');
        $this->forge->addForeignKey('i_pr_id', 'products', 'pr_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('i_l_id', 'locations', 'l_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('inventories');
    }

    public function down()
    {
        $this->forge->dropTable('inventories');
    }
}