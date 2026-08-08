<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'o_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'o_type' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'o_from_location' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'o_to_location' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'o_date' => [
                'type' => 'DATE',
            ],
            'o_car_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'o_driver_phone' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'o_loading_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'o_unloading_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'o_g_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'o_oxygen' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'o_acetylene' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'o_remark' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'o_driver_signature' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'o_from_signature' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'o_to_signature' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'o_img_car_head' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'o_img_car_tail' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'o_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'o_create_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
            'o_update_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
            'o_create_by' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'o_update_by' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'o_status' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
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

        $this->forge->addPrimaryKey('o_id');
        $this->forge->addForeignKey('o_from_location', 'locations', 'l_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('o_to_location', 'locations', 'l_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('o_g_id', 'gps', 'g_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('o_create_by', 'users', 'u_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('o_update_by', 'users', 'u_id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('orders');
    }

    public function down()
    {
        $this->forge->dropTable('orders');
    }
} 