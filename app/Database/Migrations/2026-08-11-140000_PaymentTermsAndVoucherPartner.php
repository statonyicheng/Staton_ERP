<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 帳齡／周轉分析的兩個前提。
 *
 * 1. **付款條件要有結構**：`payment_methods` 原本只有一個名稱（「預收款」「每月1號」），
 *    人看得懂但程式算不出到期日。加上「類型 + 天數」之後才能推算
 *    「這筆應該哪天收到／付掉」，進而算逾期幾天。
 *
 * 2. **傳票要記對象**：分錄傳票只有摘要，沒有記是哪個客戶或廠商。
 *    沒有對象就無法「依客戶的付款條件」判斷逾期 —— 這是做帳齡分析的關鍵欄位。
 *    （欄位可留空：內部調整分錄本來就沒有對象。）
 */
class PaymentTermsAndVoucherPartner extends Migration
{
    public function up()
    {
        // ---------- 付款條件：類型 + 天數 ----------
        if (! $this->db->fieldExists('pm_type', 'payment_methods')) {
            $this->forge->addColumn('payment_methods', [
                'pm_type' => [
                    'type' => 'VARCHAR', 'constraint' => 12, 'default' => 'net',
                    'comment' => 'immediate=即期/預收付、net=發票日起算N天、eom=月結次月N日',
                    'after' => 'pm_name',
                ],
                'pm_days' => [
                    'type' => 'INT', 'constraint' => 4, 'default' => 0,
                    'comment' => 'net：天數；eom：次月的第幾日',
                    'after' => 'pm_type',
                ],
            ]);
        }

        $this->db->resetDataCache();

        // 既有兩筆依名稱推定，之後可在結帳方式管理調整
        $this->db->table('payment_methods')->where('pm_name', '預收款')
            ->update(['pm_type' => 'immediate', 'pm_days' => 0]);
        $this->db->table('payment_methods')->where('pm_name', '每月1號')
            ->update(['pm_type' => 'eom', 'pm_days' => 1]);

        // ---------- 傳票的對象 ----------
        if (! $this->db->fieldExists('jv_partner_type', 'journal_vouchers')) {
            $this->forge->addColumn('journal_vouchers', [
                'jv_partner_type' => [
                    'type' => 'VARCHAR', 'constraint' => 10, 'null' => true,
                    'comment' => 'customer=客戶、supplier=廠商、空=無對象（內部調整分錄）',
                    'after' => 'jv_segment',
                ],
                'jv_partner_id' => [
                    'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true,
                    'comment' => '對象 id（客戶 c_id 或廠商 s_id）',
                    'after' => 'jv_partner_type',
                ],
            ]);
        }

        $this->db->resetDataCache();

        if (! $this->indexExists('journal_vouchers', 'jv_partner')) {
            $this->db->query('ALTER TABLE `journal_vouchers` ADD INDEX `jv_partner` (`jv_partner_type`, `jv_partner_id`)');
        }
    }

    public function down()
    {
        if ($this->indexExists('journal_vouchers', 'jv_partner')) {
            $this->db->query('ALTER TABLE `journal_vouchers` DROP INDEX `jv_partner`');
        }
        foreach (['jv_partner_type', 'jv_partner_id'] as $f) {
            if ($this->db->fieldExists($f, 'journal_vouchers')) $this->forge->dropColumn('journal_vouchers', $f);
        }
        foreach (['pm_type', 'pm_days'] as $f) {
            if ($this->db->fieldExists($f, 'payment_methods')) $this->forge->dropColumn('payment_methods', $f);
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
