<?php

namespace App\Commands;

use App\Libraries\EditGuard;
use App\Models\WarehouseModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 樂觀鎖自我測試：模擬兩個人同時編輯同一筆資料，確認後存的會被擋下。
 * 測試資料結束時清除。
 *
 *   php spark erp:lock-selftest
 */
class LockSelfTest extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:lock-selftest';
    protected $description = '實測樂觀鎖：兩人同時編輯時，後存的是否被擋下';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $model = new WarehouseModel();

        $pass = 0; $fail = 0;
        $check = function (string $name, bool $ok, string $detail = '') use (&$pass, &$fail) {
            if ($ok) { $pass++; CLI::write("  [PASS] {$name}", 'green'); }
            else { $fail++; CLI::write("  [FAIL] {$name} {$detail}", 'red'); }
        };

        // 建立測試倉庫
        $model->insert(['w_code' => 'ZZLK', 'w_name' => '__LOCK_TEST__', 'w_is_active' => 1]);
        $id = (int) $model->getInsertID();
        $row = $model->find($id);
        $versionA = $row['w_updated_at'];          // A 打開表單時看到的版本
        $versionB = $row['w_updated_at'];          // B 也同時打開，看到同一個版本

        $check('建立測試資料', $id > 0 && $versionA !== null, "id={$id}");

        // --- 情境 1：沒人改過，A 直接存 → 應該放行 ---
        $msg = EditGuard::check('warehouses', 'w_id', $id, 'w_updated_at', $versionA);
        $check('無人修改時可正常儲存', $msg === null, (string) $msg);

        // --- 情境 2：B 先存檔（版本前進）---
        sleep(1);   // 確保 updated_at 有變化（欄位精度到秒）
        $model->update($id, ['w_name' => '__LOCK_TEST__ B改過']);
        $after = $model->find($id);
        $check('B 存檔後版本已改變', $after['w_updated_at'] !== $versionB,
            "{$versionB} → {$after['w_updated_at']}");

        // --- 情境 3：A 拿著舊版本存檔 → 應該被擋 ---
        $msg = EditGuard::check('warehouses', 'w_id', $id, 'w_updated_at', $versionA);
        $check('A 拿舊版本儲存會被擋下', $msg !== null);
        if ($msg) {
            CLI::write('         提示訊息：' . $msg, 'dark_gray');
            $check('提示訊息有指出是誰修改的', str_contains($msg, '「') || str_contains($msg, '他人'));
        }

        // --- 情境 4：A 重新載入後用新版本存 → 應該放行 ---
        $msg = EditGuard::check('warehouses', 'w_id', $id, 'w_updated_at', $after['w_updated_at']);
        $check('A 重新載入後可正常儲存', $msg === null, (string) $msg);

        // --- 情境 5：新增（沒有 id）不應被擋 ---
        $msg = EditGuard::check('warehouses', 'w_id', null, 'w_updated_at', null);
        $check('新增資料不受樂觀鎖影響', $msg === null);

        // --- 情境 6：資料已被刪除 ---
        $model->delete($id);
        $msg = EditGuard::check('warehouses', 'w_id', $id, 'w_updated_at', $versionA);
        $check('資料已被刪除時給出明確訊息', $msg !== null && str_contains($msg, '刪除'), (string) $msg);

        // 清理
        $db->table('warehouses')->where('w_id', $id)->delete();
        $db->table('audit_logs')->where('al_table', 'warehouses')->where('al_row_id', (string) $id)->delete();
        CLI::write('  (測試資料已清除)', 'dark_gray');

        CLI::newLine();
        CLI::write("樂觀鎖自我測試：通過 {$pass}　失敗 {$fail}", $fail ? 'red' : 'green');
    }
}
