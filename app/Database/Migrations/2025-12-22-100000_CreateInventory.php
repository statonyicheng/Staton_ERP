<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 庫存骨架：品號庫存（各倉在庫量）＋ 庫存異動明細。
 * 所有進出庫皆寫一筆 stock_movements，並即時更新 product_stock。
 */
class CreateInventory extends Migration
{
    public function up()
    {
        // 品號庫存
        $this->forge->addField([
            'ps_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ps_p_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '商品'],
            'ps_w_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '倉庫'],
            'ps_qty' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '在庫量'],
            'ps_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('ps_id', true);
        $this->forge->addUniqueKey(['ps_p_id', 'ps_w_id']);
        $this->forge->createTable('product_stock', false, ['ENGINE' => 'InnoDB']);

        // 庫存異動
        $this->forge->addField([
            'sm_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'sm_date' => ['type' => 'DATE', 'comment' => '異動日期'],
            'sm_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => '異動類別'],
            'sm_direction' => ['type' => 'VARCHAR', 'constraint' => 4, 'comment' => '入/出'],
            'sm_p_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '商品'],
            'sm_w_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '倉庫'],
            'sm_qty' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '數量(正值)'],
            'sm_ref_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => '來源單別'],
            'sm_ref_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => '來源單ID'],
            'sm_ref_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'comment' => '來源單號'],
            'sm_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sm_created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('sm_id', true);
        $this->forge->addKey('sm_p_id');
        $this->forge->addKey(['sm_ref_type', 'sm_ref_id']);
        $this->forge->createTable('stock_movements', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('stock_movements');
        $this->forge->dropTable('product_stock');
    }
}
