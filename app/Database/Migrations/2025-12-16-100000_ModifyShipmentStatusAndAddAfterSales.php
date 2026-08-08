<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyShipmentStatusAndAddAfterSales extends Migration
{
    public function up()
    {
        // 1. Rename s_status to s_status_old
        $this->forge->modifyColumn('shipments', [
            's_status' => [
                'name' => 's_status_old',
                'type' => 'ENUM',
                'constraint' => ['preparing', 'partial', 'completed'],
                'default' => 'preparing',
                'comment' => '出貨狀態(舊)',
            ],
        ]);

        // 2. Create new s_status column (INT)
        $this->forge->addColumn('shipments', [
            's_status' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
                'comment' => '出貨狀態',
                'after' => 's_date'
            ],
            's_after_sales_status' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
                'comment' => '售後狀態',
                'after' => 's_status' // Add after the new s_status
            ]
        ]);

        // 3. Migrate data
        // preparing (1) -> 1 (Factory Ordered) - *Wait, User map was: preparing -> 1*
        // partial (2)   -> 6 (Shipped)
        // completed (3) -> 6 (Shipped)

        $db = \Config\Database::connect();
        $db->query("UPDATE shipments SET s_status = 1 WHERE s_status_old = 'preparing'");
        $db->query("UPDATE shipments SET s_status = 6 WHERE s_status_old = 'partial'");
        $db->query("UPDATE shipments SET s_status = 6 WHERE s_status_old = 'completed'");

        // 4. Drop old column
        $this->forge->dropColumn('shipments', 's_status_old');
    }

    public function down()
    {
        // Revert: recreate s_status as ENUM, map back, drop new columns

        // 1. Create s_status_old (ENUM)
        $this->forge->addColumn('shipments', [
            's_status_old' => [
                'type' => 'ENUM',
                'constraint' => ['preparing', 'partial', 'completed'],
                'default' => 'preparing',
                'comment' => '出貨狀態(舊)'
            ]
        ]);

        // 2. Map data back (Approximate)
        // 1 (Factory Ordered) -> preparing
        // 6 (Shipped) -> completed
        // Others -> preparing
        $db = \Config\Database::connect();
        $db->query("UPDATE shipments SET s_status_old = 'preparing' WHERE s_status = 1");
        $db->query("UPDATE shipments SET s_status_old = 'completed' WHERE s_status = 6");
        // Remaining values default to preparing or handle as needed, simple revert here.

        // 3. Drop new columns
        $this->forge->dropColumn('shipments', 's_status');
        $this->forge->dropColumn('shipments', 's_after_sales_status');

        // 4. Rename old to s_status
        $this->forge->modifyColumn('shipments', [
            's_status_old' => [
                'name' => 's_status',
                'type' => 'ENUM',
                'constraint' => ['preparing', 'partial', 'completed'],
                'default' => 'preparing',
                'comment' => '出貨狀態',
            ],
        ]);
    }
}
