<?php

namespace App\Commands;

use App\Models\CustomerModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 稽核軌跡自我測試：實際新增→修改→刪除一筆測試資料，確認 audit_logs 有正確留下紀錄。
 * 測試資料會在結束時清乾淨（含它自己產生的稽核紀錄）。
 *
 *   php spark erp:audit-selftest
 */
class AuditSelfTest extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:audit-selftest';
    protected $description = '實測稽核軌跡是否正確記錄新增/修改/刪除';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $model = new CustomerModel();
        $marker = '__AUDIT_SELFTEST__';

        $pass = 0; $fail = 0;
        $check = function (string $name, bool $ok, string $detail = '') use (&$pass, &$fail) {
            if ($ok) { $pass++; CLI::write("  [PASS] {$name}", 'green'); }
            else { $fail++; CLI::write("  [FAIL] {$name} {$detail}", 'red'); }
        };

        $before = (int) $db->table('audit_logs')->countAllResults();

        // ---- 新增 ----
        $model->insert(['c_code' => 'ZZTEST', 'c_name' => $marker, 'c_manager' => '測試員']);
        $id = (int) $model->getInsertID();
        $check('新增後有 id', $id > 0, "id={$id}");

        $log = $db->table('audit_logs')->where('al_table', 'customers')->where('al_row_id', (string) $id)
            ->where('al_action', '新增')->orderBy('al_id', 'DESC')->get()->getRowArray();
        $check('新增有寫入稽核紀錄', (bool) $log);
        if ($log) {
            $ch = json_decode($log['al_changes'] ?? '[]', true);
            $check('新增紀錄含 c_name', isset($ch['c_name']) && $ch['c_name'][1] === $marker);
        }

        // ---- 修改 ----
        $model->update($id, ['c_manager' => '改過的負責人']);
        $log = $db->table('audit_logs')->where('al_table', 'customers')->where('al_row_id', (string) $id)
            ->where('al_action', '修改')->orderBy('al_id', 'DESC')->get()->getRowArray();
        $check('修改有寫入稽核紀錄', (bool) $log);
        if ($log) {
            $ch = json_decode($log['al_changes'] ?? '[]', true);
            $ok = isset($ch['c_manager']) && $ch['c_manager'][0] === '測試員' && $ch['c_manager'][1] === '改過的負責人';
            $check('修改紀錄含前後值（測試員 → 改過的負責人）', $ok,
                $ok ? '' : json_encode($ch, JSON_UNESCAPED_UNICODE));
            $check('修改紀錄只記變動欄位', !isset($ch['c_name']),
                isset($ch['c_name']) ? '未變動的 c_name 也被記錄了' : '');
        }

        // ---- 刪除 ----
        $model->delete($id);
        $log = $db->table('audit_logs')->where('al_table', 'customers')->where('al_row_id', (string) $id)
            ->where('al_action', '刪除')->orderBy('al_id', 'DESC')->get()->getRowArray();
        $check('刪除有寫入稽核紀錄', (bool) $log);

        $after = (int) $db->table('audit_logs')->countAllResults();
        $check('共產生 3 筆稽核紀錄', $after - $before === 3, '實得 ' . ($after - $before));

        // ---- 清理 ----
        $db->table('audit_logs')->where('al_table', 'customers')->where('al_row_id', (string) $id)->delete();
        $db->table('customers')->where('c_id', $id)->delete();
        CLI::write('  (測試資料與其稽核紀錄已清除)', 'dark_gray');

        CLI::newLine();
        CLI::write("稽核軌跡自我測試：通過 {$pass}　失敗 {$fail}", $fail ? 'red' : 'green');
    }
}
