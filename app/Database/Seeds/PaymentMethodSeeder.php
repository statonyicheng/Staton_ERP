<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'pm_name' => '現金',
                'pm_created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'pm_name' => '匯款',
                'pm_created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'pm_name' => '月結30天',
                'pm_created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($data as $paymentMethod) {
            // 檢查是否已存在，避免重複插入
            $exists = $this->db->table('payment_methods')
                ->where('pm_name', $paymentMethod['pm_name'])
                ->countAllResults();

            if (!$exists) {
                $this->db->table('payment_methods')->insert($paymentMethod);
            }
        }
    }
}
