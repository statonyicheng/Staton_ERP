<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Permission;

/**
 * 角色權限驗證：用實際網址跑一遍 Permission::allows()，確認該擋的有擋、該放的有放。
 *
 *   php spark erp:perm-check            跑斷言
 *   php spark erp:perm-check --matrix   另外印出完整權限矩陣
 *
 * 權限是安全性程式碼，改過 Config\Permission 後請重跑這支。
 */
class PermCheck extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:perm-check';
    protected $description = '驗證角色權限規則（該擋的有擋、該放的有放）';
    protected $usage       = 'erp:perm-check [--matrix]';

    /** [角色, 網址, HTTP方法, 期望是否放行, 說明] */
    private const CASES = [
        // --- 管理者：全開 ---
        ['admin', 'journal/delete/1', 'GET', true,  '管理者可刪傳票'],
        ['admin', 'user', 'GET', true,  '管理者可進使用者管理'],
        ['admin', 'account/update/1', 'POST', true, '管理者可改會計科目'],

        // --- 唯讀：任何寫入都要擋 ---
        ['readonly', 'customer', 'GET', true,  '唯讀可看客戶'],
        ['readonly', 'pnl', 'GET', true,  '唯讀可看四階損益'],
        ['readonly', 'customer/create', 'GET', false, '唯讀不可開新增表單'],
        ['readonly', 'customer/store', 'POST', false, '唯讀不可新增'],
        ['readonly', 'journal/delete/1', 'GET', false, '唯讀不可刪傳票（GET 刪除也要擋）'],
        ['readonly', 'auto-journal/generate-gl', 'GET', false, '唯讀不可整批過帳'],
        ['readonly', 'open-item/offset/1', 'POST', false, '唯讀不可沖銷'],
        ['readonly', 'user', 'GET', false, '唯讀不可看使用者清單（帳號管理屬系統管理）'],

        // --- 業務：銷售流程可寫，帳務完全進不去 ---
        ['sales', 'order/create', 'GET', true,  '業務可開訂單'],
        ['sales', 'customer/store', 'POST', true,  '業務可新增客戶'],
        ['sales', 'quote/delete/3', 'GET', true,  '業務可刪自己的報價單'],
        ['sales', 'journal', 'GET', false, '業務不可看分錄傳票'],
        ['sales', 'account', 'GET', false, '業務不可看會計科目'],
        ['sales', 'transaction/create', 'GET', false, '業務不可登錄收付交易'],
        ['sales', 'payable', 'GET', false, '業務不可看應付帳款'],
        ['sales', 'product/store', 'POST', false, '業務對商品只有唯讀'],
        ['sales', 'product', 'GET', true,  '業務可查商品'],
        ['sales', 'receivable', 'GET', true,  '業務可看應收'],
        ['sales', 'receivable/pay/1', 'GET', false, '業務不可收款'],

        // --- 會計：帳務可寫，營運單據唯讀 ---
        ['accounting', 'journal/save', 'POST', true,  '會計可存傳票'],
        ['accounting', 'auto-journal/generate-gl', 'GET', true,  '會計可整批過帳'],
        ['accounting', 'fs/balance', 'GET', true,  '會計可看資產負債表'],
        ['accounting', 'settlement', 'GET', true,  '會計可進收付款'],
        ['accounting', 'order', 'GET', true,  '會計可查訂單（對帳用）'],
        ['accounting', 'order/update/1', 'POST', false, '會計不可改訂單'],
        ['accounting', 'order/delete/1', 'GET', false, '會計不可刪訂單'],
        ['accounting', 'user', 'GET', false, '會計不可進使用者管理'],
        ['accounting', 'stock-movement/save', 'POST', false, '會計不可做庫存異動'],

        // --- 採購倉管 ---
        ['purchasing', 'purchase-order/save', 'POST', true,  '採購可存採購單'],
        ['purchasing', 'goods-receipt/receive/1', 'GET', true,  '倉管可進貨'],
        ['purchasing', 'work-order/complete/1', 'GET', true,  '倉管可製令完工'],
        ['purchasing', 'payable', 'GET', true,  '採購可看應付'],
        ['purchasing', 'payable/pay/1', 'GET', false, '採購不可付款'],
        ['purchasing', 'journal', 'GET', false, '採購不可看傳票'],
        ['purchasing', 'settlement', 'GET', false, '採購不可進收付款'],

        // --- 匯出網址要對應回資料來源模組 ---
        ['sales', 'export/xlsx/fs-balance', 'GET', false, '業務不可匯出資產負債表'],
        ['sales', 'export/xlsx/journal-entry', 'GET', false, '業務不可匯出分錄明細'],
        ['sales', 'export/xlsx/customer', 'GET', true,  '業務可匯出客戶名單'],
        ['accounting', 'export/pdf/fs-balance', 'GET', true,  '會計可匯出資產負債表'],
        ['accounting', 'export/pdf/open-item-balance', 'GET', true,  '會計可匯出立沖帳餘額表'],
        ['readonly', 'export/xlsx/pnl', 'GET', true,  '唯讀可匯出報表（匯出屬讀取）'],

        // --- 一律放行 ---
        ['readonly', '', 'GET', true, '所有人可進儀表板'],
        ['sales', 'profile', 'GET', true, '所有人可進個人資料'],
        ['purchasing', 'profile/update', 'POST', true, '所有人可改自己的個人資料'],
    ];

    public function run(array $params)
    {
        if (array_key_exists('matrix', CLI::getOptions())) {
            $this->printMatrix();
            CLI::newLine();
        }

        $pass = 0; $fail = 0;
        foreach (self::CASES as [$role, $uri, $method, $expect, $desc]) {
            $got = Permission::allows($role, $uri, $method);
            if ($got === $expect) {
                $pass++;
                continue;
            }
            $fail++;
            CLI::write(sprintf('  [FAIL] %-11s %-34s %-5s 期望%s實得%s  %s',
                $role, $uri, $method,
                $expect ? '放行' : '攔阻',
                $got ? '放行' : '攔阻',
                $desc), 'red');
        }

        CLI::newLine();
        CLI::write(sprintf('權限驗證：通過 %d／%d　失敗 %d', $pass, count(self::CASES), $fail),
            $fail ? 'red' : 'green');
    }

    private function printMatrix(): void
    {
        $roles = array_keys(Permission::ROLES);
        CLI::write('權限矩陣（rw=可讀可寫、r=唯讀、·=不可存取）', 'light_blue');
        CLI::write(sprintf('%-22s %s', '模組',
            implode(' ', array_map(fn($r) => str_pad(Permission::ROLES[$r], 10), $roles))), 'dark_gray');

        $perms = [];
        foreach ($roles as $r) $perms[$r] = Permission::permissions($r);

        foreach (Permission::MODULES as $code => $label) {
            $cells = [];
            foreach ($roles as $r) {
                $v = $perms[$r][$code] ?? '·';
                $cells[] = str_pad($v, 10);
            }
            CLI::write(sprintf('%-22s %s', $label, implode(' ', $cells)));
        }
    }
}
