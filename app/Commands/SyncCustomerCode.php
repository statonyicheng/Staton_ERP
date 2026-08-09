<?php

namespace App\Commands;

use App\Models\CustomerModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 把既有客戶的「客戶編號」對齊成「統一編號」。
 *
 * 制度改成「有統編就用統編當客戶編號」（開單、查詢直接打統編），
 * 但在那之前建立的客戶拿的是流水號 C00001…，本指令負責補正。
 *
 * 用法：
 *   php spark erp:sync-customer-code --dry-run   # 先看會改哪些
 *   php spark erp:sync-customer-code
 *
 * 可重複執行。以下狀況會跳過並列出原因：
 *   · 沒有統編（含 CRM 佔位值 00000000）→ 保留原本的流水號
 *   · 統編不是 8 位數字 → 資料可能有誤，不動它
 *   · 該統編已被別的客戶當成編號 → 可能重複建檔，需人工確認
 *
 * 客戶編號只是給人看的識別碼，單據關聯走的是 c_id，改編號不會影響既有單據。
 */
class SyncCustomerCode extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:sync-customer-code';
    protected $description = '把既有客戶的客戶編號改成統一編號（沒統編的保留流水號）';
    protected $usage       = 'erp:sync-customer-code [--dry-run]';
    protected $options     = ['--dry-run' => '只顯示會改什麼，不實際寫入'];

    public function run(array $params)
    {
        $dryRun = (bool) CLI::getOption('dry-run');
        $model  = new CustomerModel();

        $customers = $model->orderBy('c_id', 'ASC')->findAll();
        if ($customers === []) {
            CLI::write('沒有客戶資料', 'yellow');
            return;
        }

        // 先建立「編號 → 客戶」對照，判斷目標編號會不會撞到別人
        $codeOwner = [];
        foreach ($customers as $c) {
            if (! empty($c['c_code'])) {
                $codeOwner[$c['c_code']] = (int) $c['c_id'];
            }
        }

        $changed = $noTaxId = $conflict = $already = $cleared = [];

        foreach ($customers as $c) {
            $id    = (int) $c['c_id'];
            $code  = (string) ($c['c_code'] ?? '');
            $taxId = trim((string) ($c['c_tax_id'] ?? ''));
            $label = $c['c_name'];

            if (! preg_match('/^\d{8}$/', $taxId) || $taxId === '00000000') {
                // 還沒有統編：編號留空，不要憑空掛一組永遠不會用到的流水號
                if ($code !== '') {
                    $cleared[] = "{$code} → （留空）　{$label}";
                    if (! $dryRun) {
                        $model->update($id, ['c_code' => null]);
                        unset($codeOwner[$code]);
                    }
                } else {
                    $noTaxId[] = "（留空）　{$label}";
                }
                continue;
            }

            if ($code === $taxId) {
                $already[] = "{$code}　{$label}";
                continue;
            }

            if (isset($codeOwner[$taxId]) && $codeOwner[$taxId] !== $id) {
                $conflict[] = "{$label}：統編 {$taxId} 已被客戶編號相同的另一筆資料使用";
                continue;
            }

            $changed[] = "{$code} → {$taxId}　{$label}";

            if (! $dryRun) {
                $model->update($id, ['c_code' => $taxId]);
                unset($codeOwner[$code]);
                $codeOwner[$taxId] = $id;
            }
        }

        $this->section($dryRun ? '將改為統編' : '已改為統編', $changed, 'green');
        $this->section($dryRun ? '將清空編號（無統編）' : '已清空編號（無統編）', $cleared, 'yellow');
        $this->section('無統編、編號本來就是空的', $noTaxId, 'dark_gray');
        $this->section('編號衝突，請人工確認', $conflict, 'red');

        CLI::newLine();
        CLI::write(str_repeat('=', 58), 'dark_gray');
        CLI::write(sprintf(
            '客戶編號對齊：改為統編 %d　已相同 %d　清空 %d　無統編 %d　衝突 %d%s',
            count($changed), count($already), count($cleared), count($noTaxId), count($conflict),
            $dryRun ? '（試算模式，尚未寫入）' : ''
        ), $conflict ? 'yellow' : 'green');
    }

    private function section(string $title, array $items, string $color): void
    {
        if ($items === []) {
            return;
        }
        CLI::write("{$title}（" . count($items) . '）', $color);
        foreach ($items as $item) {
            CLI::write('    ' . $item, $color);
        }
        CLI::newLine();
    }
}
