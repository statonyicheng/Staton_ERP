<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 生產:產品結構 BOM（母件→子件用量）＋ 製令。
 * 製令完工時依 BOM 領料出庫、成品入庫（透過 stock_movements）。
 */
class CreateProduction extends Migration
{
    public function up()
    {
        // BOM 用量
        $this->forge->addField([
            'bi_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'bi_parent_p_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '母件商品'],
            'bi_child_p_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '子件商品'],
            'bi_qty' => ['type' => 'INT', 'constraint' => 11, 'default' => 1, 'comment' => '單位用量'],
            'bi_unit' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => '單位'],
            'bi_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'bi_created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('bi_id', true);
        $this->forge->addKey('bi_parent_p_id');
        $this->forge->createTable('bom_items', false, ['ENGINE' => 'InnoDB']);

        // 製令
        $this->forge->addField([
            'wo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'wo_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'comment' => '製令單號'],
            'wo_p_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '生產母件'],
            'wo_qty' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '生產數量'],
            'wo_date' => ['type' => 'DATE', 'comment' => '製令日期'],
            'wo_due_date' => ['type' => 'DATE', 'null' => true, 'comment' => '預計完工'],
            'wo_w_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => '領料/入庫倉'],
            'wo_status' => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => '未完工', 'comment' => '狀態'],
            'wo_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'wo_created_at' => ['type' => 'DATETIME', 'null' => true],
            'wo_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('wo_id', true);
        $this->forge->addKey('wo_no');
        $this->forge->createTable('work_orders', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('work_orders');
        $this->forge->dropTable('bom_items');
    }
}
