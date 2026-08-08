<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyCustomersPaymentMethod extends Migration
{
    public function up()
    {
        // 建立外鍵欄位
        $this->forge->addColumn('customers', [
            'c_pm_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => '結帳方式ID',
                'after' => 'c_tax_id',
            ],
        ]);

        // 資料遷移：將現有文字結帳方式轉換為對應的 pm_id
        $this->migratePaymentMethods();

        // 建立外鍵約束
        $this->forge->addForeignKey('c_pm_id', 'payment_methods', 'pm_id', 'CASCADE', 'SET NULL');

        // 移除舊欄位
        $this->forge->dropColumn('customers', 'c_payment_method');
    }

    public function down()
    {
        // 先移除外鍵約束
        $this->forge->dropForeignKey('customers', 'customers_c_pm_id_foreign');

        // 還原舊欄位
        $this->forge->addColumn('customers', [
            'c_payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '結帳方式',
                'after' => 'c_tax_id',
            ],
        ]);

        // 資料遷移：將 pm_id 轉換回文字
        $this->reverseMigratePaymentMethods();

        // 移除新欄位
        $this->forge->dropColumn('customers', 'c_pm_id');
    }

    private function migratePaymentMethods()
    {
        // 取得現有的結帳方式資料
        $existingPayments = $this->db->table('customers')
            ->select('c_payment_method')
            ->where('c_payment_method IS NOT NULL')
            ->where('c_payment_method !=', '')
            ->distinct()
            ->get()
            ->getResultArray();

        $paymentMethodMap = [];

        foreach ($existingPayments as $payment) {
            $method = trim($payment['c_payment_method']);

            // 檢查是否已存在於 payment_methods 表
            $existing = $this->db->table('payment_methods')
                ->where('pm_name', $method)
                ->get()
                ->getRow();

            if (!$existing) {
                // 不存在則新增
                $this->db->table('payment_methods')->insert([
                    'pm_name' => $method,
                    'pm_created_at' => date('Y-m-d H:i:s'),
                ]);
                $pmId = $this->db->connID->insert_id;
            } else {
                $pmId = $existing->pm_id;
            }

            $paymentMethodMap[$method] = $pmId;
        }

        // 更新客戶資料
        foreach ($paymentMethodMap as $method => $pmId) {
            $this->db->table('customers')
                ->where('c_payment_method', $method)
                ->update(['c_pm_id' => $pmId]);
        }
    }

    private function reverseMigratePaymentMethods()
    {
        // 將 pm_id 轉換回文字結帳方式
        $customers = $this->db->table('customers')
            ->select('c_id, c_pm_id')
            ->where('c_pm_id IS NOT NULL')
            ->get()
            ->getResultArray();

        foreach ($customers as $customer) {
            $paymentMethod = $this->db->table('payment_methods')
                ->select('pm_name')
                ->where('pm_id', $customer['c_pm_id'])
                ->get()
                ->getRow();

            if ($paymentMethod) {
                $this->db->table('customers')
                    ->where('c_id', $customer['c_id'])
                    ->update(['c_payment_method' => $paymentMethod->pm_name]);
            }
        }
    }
}
