<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 版面/檢視檔渲染檢查：不需登入即可確認樣板本身不會噴 PHP 錯誤。
 *
 *   php spark erp:view-check
 */
class ViewCheck extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:view-check';
    protected $description = '渲染共用版面與元件，檢查樣板是否有 PHP 錯誤';

    public function run(array $params)
    {
        $ok = 0; $fail = 0;

        // 匯出登錄表
        try {
            $keys = \App\Controllers\ExportController::exportableKeys();
            CLI::write('  [PASS] 匯出登錄表可讀取，共 ' . count($keys) . ' 個項目', 'green');
            $ok++;
        } catch (\Throwable $e) {
            CLI::write('  [FAIL] 匯出登錄表：' . $e->getMessage(), 'red');
            $fail++;
        }

        // 匯出按鈕元件
        foreach (['customer', 'pnl', 'fs-balance'] as $k) {
            try {
                $html = view('components/export_buttons', ['key' => $k]);
                $hasBoth = str_contains($html, 'export/xlsx/' . $k) && str_contains($html, 'export/pdf/' . $k);
                if ($hasBoth) { CLI::write("  [PASS] 匯出按鈕元件（{$k}）", 'green'); $ok++; }
                else { CLI::write("  [FAIL] 匯出按鈕元件（{$k}）缺少連結", 'red'); $fail++; }
            } catch (\Throwable $e) {
                CLI::write("  [FAIL] 匯出按鈕元件（{$k}）：" . $e->getMessage(), 'red');
                $fail++;
            }
        }

        // 主版面（含側邊欄與匯出列）
        try {
            $html = view('_layout', []);
            $checks = [
                '版面標題' => str_contains($html, '仕坦登 ERP'),
                'favicon'  => str_contains($html, 'favicon.ico'),
                '側邊欄'    => str_contains($html, 'sidebar-wrapper'),
                'CSS 連結' => str_contains($html, 'css/custom.css'),
            ];
            // 註：?v=filemtime 破快取只有在網頁請求下才會有值。
            // spark 把 FCPATH 寫死成 public/（本專案的 front controller 在根目錄），
            // 所以 CLI 下 filemtime() 取不到值，這不是網頁端的問題。
            foreach ($checks as $name => $pass) {
                CLI::write(($pass ? '  [PASS] ' : '  [FAIL] ') . '主版面：' . $name, $pass ? 'green' : 'red');
                $pass ? $ok++ : $fail++;
            }
        } catch (\Throwable $e) {
            CLI::write('  [FAIL] 主版面渲染：' . get_class($e) . ' ' . $e->getMessage(), 'red');
            $fail++;
        }

        // 訂單／報價的商品項目列：明細以「商品」為單位，欄位名稱錯了單據就會存不進明細
        foreach ([
            ['order_item_row', 'oi_p_id', 'oi_pi_id'],
            ['quote_item_row', 'qi_p_id', 'qi_pi_id'],
        ] as [$component, $field, $removed]) {
            try {
                $html = view('components/' . $component, [
                    'index' => 0,
                    'item' => [],
                    'products' => [['p_id' => 1, 'p_name' => '測試商品', 'p_pc_id' => 1,
                                    'p_standard_price' => 100, 'p_size' => 'A、B']],
                    'productCategories' => [['pc_id' => 1, 'pc_name' => '測試分類']],
                    'isTemplate' => true,
                ]);

                $checks = [
                    "有送出 {$field}（品項＝商品）" => str_contains($html, "items[0][{$field}]"),
                    "不再送出已移除的 {$removed}" => ! str_contains($html, $removed),
                ];
                foreach ($checks as $name => $pass) {
                    CLI::write(($pass ? '  [PASS] ' : '  [FAIL] ') . "{$component}：{$name}", $pass ? 'green' : 'red');
                    $pass ? $ok++ : $fail++;
                }
            } catch (\Throwable $e) {
                CLI::write("  [FAIL] {$component} 渲染：" . get_class($e) . ' ' . $e->getMessage(), 'red');
                $fail++;
            }
        }

        CLI::newLine();
        CLI::write("檢查結果：通過 {$ok}　失敗 {$fail}", $fail ? 'red' : 'green');
    }
}
