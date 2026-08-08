<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class GenerateCustomerCodesForExistingCustomers extends Migration
{
    public function up()
    {
        // 取得所有沒有編號的客戶
        $customers = $this->db->table('customers')
            ->where('c_code IS NULL OR c_code = ""')
            ->orderBy('c_id', 'ASC')
            ->get()
            ->getResultArray();

        $codeNumber = 1;

        foreach ($customers as $customer) {
            // 產生編號
            $code = 'C' . str_pad($codeNumber, 5, '0', STR_PAD_LEFT);

            // 更新客戶編號
            $this->db->table('customers')
                ->where('c_id', $customer['c_id'])
                ->update(['c_code' => $code]);

            $codeNumber++;
        }
    }

    public function down()
    {
        // 清除所有客戶編號（用於 rollback）
        $this->db->table('customers')
            ->update(['c_code' => null]);
    }
}
