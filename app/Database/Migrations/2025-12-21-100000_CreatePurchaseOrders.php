<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePurchaseOrders extends Migration
{
    public function up()
    {
        // 採購單 單頭
        $this->forge->addField([
            'po_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'po_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'comment' => '採購單號'],
            'po_s_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => '廠商'],
            'po_date' => ['type' => 'DATE', 'comment' => '採購日期'],
            'po_expected_date' => ['type' => 'DATE', 'null' => true, 'comment' => '預計到貨日'],
            'po_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '未結案', 'comment' => '狀態'],
            'po_subtotal' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '未稅小計'],
            'po_tax' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '營業稅'],
            'po_total' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '含稅總計'],
            'po_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => '備註'],
            'po_created_at' => ['type' => 'DATETIME', 'null' => true],
            'po_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('po_id', true);
        $this->forge->addKey('po_no');
        $this->forge->createTable('purchase_orders', false, ['ENGINE' => 'InnoDB']);

        // 採購單 明細
        $this->forge->addField([
            'poi_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'poi_po_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '採購單'],
            'poi_p_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => '商品'],
            'poi_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'comment' => '品名'],
            'poi_spec' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'comment' => '規格'],
            'poi_qty' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '數量'],
            'poi_unit' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => '單位'],
            'poi_price' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '單價'],
            'poi_amount' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '金額'],
            'poi_sort' => ['type' => 'INT', 'constraint' => 6, 'default' => 0],
        ]);
        $this->forge->addKey('poi_id', true);
        $this->forge->addKey('poi_po_id');
        $this->forge->createTable('purchase_order_items', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('purchase_order_items');
        $this->forge->dropTable('purchase_orders');
    }
}
