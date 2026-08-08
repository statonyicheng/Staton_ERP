<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * 庫存補平：為「在庫量」與「異動淨額」的差額補一筆期初存量異動。
 *
 *   php spark erp:stock-reconcile          （只列出差異，不寫入）
 *   php spark erp:stock-reconcile --apply  （實際補上期初存量異動）
 *
 * 用途：
 *  1. 舊資料是直接寫進 product_stock（沒走異動）造成庫存無法追溯時。
 *  2. 客戶導入本系統時，把既有庫存以「期初存量」正式入帳。
 */
class StockReconcile extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:stock-reconcile';
    protected $description = '比對在庫量與異動淨額，用「期初存量」異動補平差額';
    protected $usage       = 'erp:stock-reconcile [--apply] [--date=YYYY-MM-DD]';

    public function run(array $params)
    {
        $db = Database::connect();
        $apply = array_key_exists('apply', CLI::getOptions());
        $date = CLI::getOption('date') ?: date('Y-m-d');

        $rows = $db->query("
            SELECT ps.ps_p_id p, ps.ps_w_id w, ps.ps_qty q,
                   COALESCE((SELECT SUM(CASE WHEN m.sm_direction = '出' THEN -m.sm_qty ELSE m.sm_qty END)
                             FROM stock_movements m
                             WHERE m.sm_p_id = ps.ps_p_id AND m.sm_w_id = ps.ps_w_id), 0) net
            FROM product_stock ps
        ")->getResultArray();

        $diffs = [];
        foreach ($rows as $r) {
            $d = (int) $r['q'] - (int) $r['net'];
            if ($d !== 0) $diffs[] = ['p' => (int) $r['p'], 'w' => (int) $r['w'], 'diff' => $d, 'qty' => (int) $r['q'], 'net' => (int) $r['net']];
        }

        if (!$diffs) {
            CLI::write('庫存已完全一致，無需補平。', 'green');
            return;
        }

        CLI::write('發現 ' . count($diffs) . ' 組在庫量與異動紀錄不符：', 'yellow');
        foreach ($diffs as $d) {
            CLI::write(sprintf('  品號 %d / 倉庫 %d：在庫 %d、異動淨額 %d、差額 %+d',
                $d['p'], $d['w'], $d['qty'], $d['net'], $d['diff']));
        }

        if (!$apply) {
            CLI::newLine();
            CLI::write('這是預覽模式。要實際補上「期初存量」異動請加 --apply', 'light_blue');
            return;
        }

        // 直接寫 stock_movements（不經 apply()），因為在庫量已是正確值，只是缺紀錄
        $now = date('Y-m-d H:i:s');
        $db->transStart();
        foreach ($diffs as $d) {
            $db->table('stock_movements')->insert([
                'sm_date' => $date,
                'sm_type' => '期初存量',
                'sm_direction' => $d['diff'] > 0 ? '入' : '出',
                'sm_p_id' => $d['p'], 'sm_w_id' => $d['w'], 'sm_qty' => abs($d['diff']),
                'sm_ref_type' => '期初', 'sm_ref_id' => null, 'sm_ref_no' => null,
                'sm_note' => '系統補平：導入前既有庫存',
                'sm_created_at' => $now,
            ]);
        }
        $db->transComplete();

        CLI::newLine();
        CLI::write('已補上 ' . count($diffs) . ' 筆期初存量異動，在庫量未變動。', 'green');
    }
}
