<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProducts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'p_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'p_code' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => '產品編號',
            ],
            'p_barcode' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => '條碼',
            ],
            'p_name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => '產品名稱',
            ],
            'p_image' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '產品圖片',
            ],
            'p_specifications' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '規格',
            ],
            'p_standard_price' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => '標準價格',
            ],
            'p_unit' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
                'comment' => '單位',
            ],
            'p_created_at' => [
                'type' => 'DATETIME',
                'comment' => '建立時間',
            ],
            'p_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '更新時間',
            ],
        ]);

        $this->forge->addKey('p_id', true);
        $this->forge->createTable('products',false,['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('products');
    }
}
