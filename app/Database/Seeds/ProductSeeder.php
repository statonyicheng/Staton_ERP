<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            [
                'p_code' => 'PROD-001',
                'p_barcode' => '1234567890123',
                'p_name' => '工業級電源供應器',
                'p_image' => null,
                'p_specifications' => '輸入: 100-240VAC, 輸出: 24VDC/5A, 效率: 85%',
                'p_standard_price' => 2500,
                'p_unit' => '個',
                'p_created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'p_code' => 'PROD-002',
                'p_barcode' => '9876543210987',
                'p_name' => '高速網路交換器',
                'p_image' => null,
                'p_specifications' => '24埠 Gigabit Ethernet, 管理型, 支援 VLAN',
                'p_standard_price' => 8500,
                'p_unit' => '台',
                'p_created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('products')->insertBatch($products);
    }
}
