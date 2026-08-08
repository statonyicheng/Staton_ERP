<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * 範例交易資料（2026-07），供四階損益分析 / 資金餘額表 / 總帳展示之用。
 * 數字取自【嵐石】財務架構 7 月實際資料之代表值；正式上線可全部刪除。
 */
class TransactionSampleSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // 建 科目名稱 => ac_id 對照
        $accs = $db->table('accounts')->select('ac_id, ac_name')->get()->getResultArray();
        $map = [];
        foreach ($accs as $a) $map[$a['ac_name']] = $a['ac_id'];

        // [日期, 摘要, 對象, 方向, 業務別, 科目名, 未稅, 稅]
        $rows = [
            ['2026-07-24', '洞石訂單', '佳昶', '收', 'M-1', '營-銷貨收入', 297200, 14860],
            ['2026-07-16', '洞石訂單', '習自然', '收', 'M-1', '營-銷貨收入', 122857, 6143],
            ['2026-07-10', '石材進貨', '巨峰石材', '付', 'M-1', '製-進貨', 80000, 4000],
            ['2026-07-12', '順豐運費', '順豐', '付', 'M-1', '製-運費', 8000, 0],
            ['2026-07-05', 'AI 廣告投放', '', '付', 'M-0', '銷-廣告費', 1563, 0],
            ['2026-07-20', '顧問勞務費', '記帳事務所', '付', 'M-0', '管-勞務費', 195000, 0],
            ['2026-07-18', '型錄印刷', '', '付', 'M-0', '管-文具費用', 7014, 0],
            ['2026-07-15', '停車 / 交通費', '', '付', 'M-0', '管-交通費', 3870, 0],
        ];

        // 若已有 2026-07 交易則不重複建立
        $exists = $db->table('gl_transactions')->where('t_ym', '2026-07')->countAllResults();
        if ($exists > 0) return;

        $now = date('Y-m-d H:i:s');
        $data = [];
        foreach ($rows as $r) {
            if (!isset($map[$r[5]])) continue;
            $data[] = [
                't_date' => $r[0], 't_ym' => substr($r[0], 0, 7),
                't_summary' => $r[1], 't_partner' => $r[2], 't_direction' => $r[3],
                't_segment' => $r[4], 't_ac_id' => $map[$r[5]],
                't_amount' => $r[6], 't_tax' => $r[7],
                't_settle_status' => '已收付', 't_settle_date' => $r[0],
                't_created_at' => $now, 't_updated_at' => $now,
            ];
        }
        if (!empty($data)) $db->table('gl_transactions')->insertBatch($data);
    }
}
