<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 補齊銷售這條線（訂單／報價）缺少的欄位。
 *
 * 背景：原始程式包的 schema 與 migration 歷史本來就不一致 ——
 * `AddFieldsForQuoteToOrder` 在 `migrations` 表記錄為 batch 1 已執行，
 * 但 `orders` 上根本沒有那些欄位；OrderModel 另有 4 個從未被任何 migration
 * 定義過的廠商欄位（表單一直在送，存檔時被 allowedFields 擋掉而靜默遺失）。
 *
 * 因為那筆 migration 已被記錄為執行過，不可能再跑一次，故以本檔補齊。
 * 每個欄位都先檢查存在與否，已上線的資料庫可安全執行。
 */
class FixSalesLineSchema extends Migration
{
    /** orders 應有但可能缺少的欄位（依 AddFieldsForQuoteToOrder 的原定義） */
    private array $orderFields = [
        'o_cc_id' => [
            'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true,
            'comment' => '聯絡人ID', 'after' => 'o_c_id',
        ],
        'o_delivery_city' => [
            'type' => 'VARCHAR', 'constraint' => 20, 'null' => true,
            'comment' => '送貨縣市', 'after' => 'o_cc_id',
        ],
        'o_delivery_address' => [
            'type' => 'VARCHAR', 'constraint' => 255, 'null' => true,
            'comment' => '送貨地址', 'after' => 'o_delivery_city',
        ],
        'o_subtotal' => [
            'type' => 'FLOAT', 'constraint' => 11, 'default' => 0,
            'comment' => '小計', 'after' => 'o_delivery_address',
        ],
        'o_discount' => [
            'type' => 'FLOAT', 'constraint' => 11, 'default' => 0,
            'comment' => '整單折扣(%)', 'after' => 'o_subtotal',
        ],
        'o_tax_rate' => [
            'type' => 'FLOAT', 'constraint' => 11, 'default' => 5,
            'comment' => '稅率(%)', 'after' => 'o_discount',
        ],
        'o_shipping_fee' => [
            'type' => 'INT', 'constraint' => 11, 'default' => 0,
            'comment' => '運費', 'after' => 'o_tax_rate',
        ],
        'o_tax_amount' => [
            'type' => 'FLOAT', 'constraint' => 11, 'default' => 0,
            'comment' => '稅額', 'after' => 'o_shipping_fee',
        ],
        // 以下 4 個欄位表單一直在送，卻從未被任何 migration 建立過
        'o_vendor' => [
            'type' => 'VARCHAR', 'constraint' => 100, 'null' => true,
            'comment' => '供應商', 'after' => 'o_invoice_number',
        ],
        'o_vendor_contect' => [
            'type' => 'VARCHAR', 'constraint' => 100, 'null' => true,
            'comment' => '廠商聯絡人', 'after' => 'o_vendor',
        ],
        'o_vendor_address' => [
            'type' => 'VARCHAR', 'constraint' => 255, 'null' => true,
            'comment' => '廠商地址', 'after' => 'o_vendor_contect',
        ],
        'o_shipping_address' => [
            'type' => 'VARCHAR', 'constraint' => 255, 'null' => true,
            'comment' => '廠商出貨地址', 'after' => 'o_vendor_address',
        ],
    ];

    /** quotes 缺少的欄位（QuoteModel 與報價表單都在用） */
    private array $quoteFields = [
        'q_vendor' => [
            'type' => 'VARCHAR', 'constraint' => 100, 'null' => true,
            'comment' => '供應商', 'after' => 'q_notes',
        ],
    ];

    public function up()
    {
        $this->addMissing('orders', $this->orderFields);
        $this->addMissing('quotes', $this->quoteFields);

        // CI4 會把欄位清單快取在連線上，剛加的欄位不清快取就查不到（fieldExists 會回傳舊答案）
        $this->db->resetDataCache();

        // 聯絡人欄位會被 JOIN，補一個索引（不加外鍵：聯絡人被刪時由程式面容忍 null）
        if ($this->db->fieldExists('o_cc_id', 'orders') && ! $this->indexExists('orders', 'orders_o_cc_id')) {
            $this->db->query('ALTER TABLE `orders` ADD INDEX `orders_o_cc_id` (`o_cc_id`)');
        }
    }

    public function down()
    {
        if ($this->indexExists('orders', 'orders_o_cc_id')) {
            $this->db->query('ALTER TABLE `orders` DROP INDEX `orders_o_cc_id`');
        }

        $this->dropExisting('orders', array_keys($this->orderFields));
        $this->dropExisting('quotes', array_keys($this->quoteFields));
    }

    /** 只新增資料表上還沒有的欄位 */
    private function addMissing(string $table, array $fields): void
    {
        $missing = [];
        foreach ($fields as $name => $definition) {
            if (! $this->db->fieldExists($name, $table)) {
                $missing[$name] = $definition;
            }
        }

        if ($missing !== []) {
            $this->forge->addColumn($table, $missing);
        }
    }

    /** 只刪除資料表上真的存在的欄位 */
    private function dropExisting(string $table, array $names): void
    {
        $existing = array_values(array_filter(
            $names,
            fn ($name) => $this->db->fieldExists($name, $table)
        ));

        if ($existing !== []) {
            $this->forge->dropColumn($table, $existing);
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        foreach ($this->db->getIndexData($table) as $data) {
            if ($data->name === $index) {
                return true;
            }
        }

        return false;
    }
}
