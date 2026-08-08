<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * 資產負債表科目（供傳統借貸分錄使用）。代號 1xxx 資產、2xxx 負債、3xxx 權益。
 */
class BalanceSheetAccountSeeder extends Seeder
{
    public function run()
    {
        // [代號, 名稱, 類別]
        $rows = [
            ['1101', '現金', '資產'],
            ['1102', '銀行存款', '資產'],
            ['1103', '應收帳款', '資產'],
            ['1104', '應收票據', '資產'],
            ['1105', '存貨', '資產'],
            ['1106', '預付款項', '資產'],
            ['1201', '固定資產', '資產'],
            ['1202', '累計折舊', '資產'],
            ['2101', '應付帳款', '負債'],
            ['2102', '應付票據', '負債'],
            ['2103', '應付薪資', '負債'],
            ['2104', '應付稅款', '負債'],
            ['2105', '短期借款', '負債'],
            ['2201', '長期借款', '負債'],
            ['3101', '股本', '權益'],
            ['3102', '保留盈餘', '權益'],
            ['3103', '本期損益', '權益'],
        ];

        $db = \Config\Database::connect();
        $existing = array_column($db->table('accounts')->select('ac_name')->get()->getResultArray(), 'ac_name');
        $now = date('Y-m-d H:i:s');
        $data = [];
        foreach ($rows as $r) {
            if (in_array($r[1], $existing, true)) continue;
            $data[] = [
                'ac_code' => $r[0], 'ac_name' => $r[1], 'ac_category' => $r[2],
                'ac_tier' => '資產負債表', 'ac_is_pl' => 0,
                'ac_sort' => (int) $r[0], 'ac_created_at' => $now, 'ac_updated_at' => $now,
            ];
        }
        if (!empty($data)) $db->table('accounts')->insertBatch($data);
    }
}
