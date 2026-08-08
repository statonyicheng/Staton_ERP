<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyProductsTable extends Migration
{
    public function up()
    {
        // 新增欄位
        $this->forge->addColumn('products', [
            'p_pc_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'p_id',
                'comment' => '產品分類外鍵',
            ],
            'p_supplier' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'p_name',
                'comment' => '供應商',
            ],
            'p_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'p_supplier',
                'comment' => '種類',
            ],
            'p_style' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'p_type',
                'comment' => '款式',
            ],
            'p_color' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'p_style',
                'comment' => '顏色/花色',
            ],
            'p_size' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'p_color',
                'comment' => '尺寸',
            ],
            'p_cost_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
                'after' => 'p_standard_price',
                'comment' => '進貨成本',
            ],
        ]);

        // 移除條碼欄位
        $this->forge->dropColumn('products', 'p_barcode');

        // 設定外鍵約束
        $this->db->query('ALTER TABLE products ADD CONSTRAINT fk_products_category FOREIGN KEY (p_pc_id) REFERENCES product_categories(pc_id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        // 移除外鍵約束
        $this->db->query('ALTER TABLE products DROP FOREIGN KEY fk_products_category');

        // 移除新增的欄位
        $this->forge->dropColumn('products', [
            'p_pc_id',
            'p_supplier',
            'p_type',
            'p_style',
            'p_color',
            'p_size',
            'p_cost_price'
        ]);

        // 恢復條碼欄位
        $this->forge->addColumn('products', [
            'p_barcode' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'p_code',
                'comment' => '條碼',
            ],
        ]);
    }
}
