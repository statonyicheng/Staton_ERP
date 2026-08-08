<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductCategories extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'pc_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'pc_name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => '分類名稱',
            ],
            'pc_created_at' => [
                'type' => 'DATETIME',
                'comment' => '建立時間',
            ],
            'pc_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '更新時間',
            ],
        ]);

        $this->forge->addKey('pc_id', true);
        $this->forge->createTable('product_categories', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('product_categories');
    }
}
