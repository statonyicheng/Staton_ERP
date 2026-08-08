<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * 【仕坦登】顧問服務業會計科目
 *
 * 原始科目表沿用【嵐石】石材買賣的四階模型（製-進貨／檢驗費／加工費…），
 * 本 seeder 補上顧問服務業所需的收入與費用科目，讓仕坦登公司內帳能正確歸屬。
 * 以 ac_code 為準做 upsert，可重複執行；不刪除既有科目（自動分錄與既有傳票仍依賴它們）。
 */
class StatonAccountSeeder extends Seeder
{
    public function run()
    {
        $rows = [
            // ===== 營業收入：顧問服務六大營收線 =====
            ['4201', '營-企業管家服務收入', '收入', '營業收入', 1, 0, 421],
            ['4202', '營-記帳與稅務服務收入', '收入', '營業收入', 1, 0, 422],
            ['4203', '營-工商登記服務收入', '收入', '營業收入', 1, 0, 423],
            ['4204', '營-財務顧問專案收入', '收入', '營業收入', 1, 0, 424],
            ['4205', '營-分潤與介紹收入', '收入', '營業收入', 1, 0, 425],
            ['4206', '營-代墊代收收入', '收入', '營業收入', 1, 0, 426],

            // ===== 一階成本：服務業的直接交付成本 =====
            ['5201', '服-外包專業服務費', '支出', '一階成本', 1, 0, 521],
            ['5202', '服-代辦規費及公費', '支出', '一階成本', 1, 0, 522],
            ['5203', '服-專案直接費用', '支出', '一階成本', 1, 0, 523],

            // ===== 三階費用：人事 =====
            ['6305', '三階-顧問人員用人成本', '支出', '三階費用', 1, 0, 635],

            // ===== 四階費用：管理 =====
            ['6416', '管-稅捐及帳務費', '支出', '四階費用', 1, 0, 646],
            ['6417', '管-人力招募費', '支出', '四階費用', 1, 0, 647],
            ['6418', '管-差旅住宿費', '支出', '四階費用', 1, 0, 648],
        ];

        $db = $this->db;
        $added = 0;
        $updated = 0;

        foreach ($rows as [$code, $name, $cat, $tier, $isPl, $openItem, $sort]) {
            $exists = $db->table('accounts')->where('ac_code', $code)->get()->getRowArray();
            $payload = [
                'ac_code' => $code,
                'ac_name' => $name,
                'ac_category' => $cat,
                'ac_tier' => $tier,
                'ac_is_pl' => $isPl,
                'ac_open_item' => $openItem,
                'ac_sort' => $sort,
                'ac_updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($exists) {
                $db->table('accounts')->where('ac_code', $code)->update($payload);
                $updated++;
            } else {
                $payload['ac_created_at'] = date('Y-m-d H:i:s');
                $db->table('accounts')->insert($payload);
                $added++;
            }
        }

        echo "仕坦登顧問業科目：新增 {$added} 筆、更新 {$updated} 筆。\n";
    }
}
