<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $customers = [
            [
                'c_name' => '台灣科技股份有限公司',
                'c_manager' => '王經理',
                'c_contact_person' => '李小明',
                'c_phone' => '02-12345678',
                'c_fax' => '02-12345679',
                'c_email' => 'contact@taiwantech.com',
                'c_address' => '台北市中山區中山北路一段123號',
                'c_tax_id' => '12345678',
                'c_payment_method' => '月結30天',
                'c_notes' => '主要客戶，優先處理訂單',
                'c_created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'c_name' => '創新電子有限公司',
                'c_manager' => '陳總經理',
                'c_contact_person' => '張小華',
                'c_phone' => '04-87654321',
                'c_fax' => '04-87654322',
                'c_email' => 'sales@innovate.com',
                'c_address' => '台中市西屯區台灣大道二段456號',
                'c_tax_id' => '87654321',
                'c_payment_method' => '現金',
                'c_notes' => '新客戶，需要密切關注',
                'c_created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('customers')->insertBatch($customers);
    }
}
