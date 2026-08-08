<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductImages extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'pi_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'pi_p_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => '產品ID',
            ],
            'pi_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => '圖片檔名',
            ],
            'pi_created_at' => [
                'type' => 'DATETIME',
                'comment' => '建立時間',
            ],
        ]);

        $this->forge->addKey('pi_id', true);
        $this->forge->addKey('pi_p_id');
        $this->forge->createTable('product_images', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('product_images');
    }
}
