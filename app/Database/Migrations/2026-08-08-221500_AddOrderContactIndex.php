<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 補上 orders.o_cc_id 的索引。
 *
 * 這個索引本來寫在 `FixSalesLineSchema` 裡，但沒有生效：
 * CI4 會把資料表的欄位清單快取在連線上，同一支 migration 裡「先問 fieldExists、
 * 後加欄位」時，第二次問到的還是加欄位之前的舊快取，於是判斷式整段被跳過
 * （不會報錯，只是索引沒建）。原檔已補上 resetDataCache()，全新安裝不會再遇到；
 * 已經跑過那支 migration 的資料庫則由本檔補建。
 *
 * o_cc_id 會被 OrderModel::getOrderWithItems() JOIN customer_contacts。
 */
class AddOrderContactIndex extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('o_cc_id', 'orders') || $this->indexExists('orders', 'orders_o_cc_id')) {
            return;
        }

        $this->db->query('ALTER TABLE `orders` ADD INDEX `orders_o_cc_id` (`o_cc_id`)');
    }

    public function down()
    {
        if ($this->indexExists('orders', 'orders_o_cc_id')) {
            $this->db->query('ALTER TABLE `orders` DROP INDEX `orders_o_cc_id`');
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) c FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
            [$table, $index]
        )->getRow()->c > 0;
    }
}
