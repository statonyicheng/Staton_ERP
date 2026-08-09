<?php

namespace App\Commands;

use App\Models\CustomerModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 從 CSV 匯入客戶主檔（統一編號 + 公司名稱）。
 *
 * 來源是使用者 Google Drive 的 CRM 資料夾，每個客戶一個資料夾，
 * 命名規則「【統編】公司名稱」，例如【00009694】喜蝦有限公司。
 * 資料夾名稱在本機抽成 CSV 後，用本指令匯進各環境（本機/測試站/正式站）。
 *
 * CSV 格式（UTF-8，第一列是標題）：
 *   tax_id,name
 *   27368090,荷旺企業有限公司
 *
 * 用法：
 *   php spark erp:import-customers                          # 讀 writable/imports/crm_customers.csv
 *   php spark erp:import-customers --file /path/to/x.csv
 *   php spark erp:import-customers --dry-run                # 只試算，不寫入
 *
 * 可重複執行：已存在的客戶（統編相同，或無統編時名稱相同）會被跳過，不會產生重複。
 */
class ImportCustomers extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:import-customers';
    protected $description = '從 CSV 匯入客戶主檔（統編＋名稱），可重複執行不會重複建立';
    protected $usage       = 'erp:import-customers [--file <csv>] [--dry-run]';
    protected $options     = [
        '--file'    => '來源 CSV 路徑（預設 writable/imports/crm_customers.csv）',
        '--dry-run' => '只顯示將會發生什麼，不實際寫入',
    ];

    public function run(array $params)
    {
        $file   = CLI::getOption('file') ?: WRITEPATH . 'imports/crm_customers.csv';
        $dryRun = (bool) CLI::getOption('dry-run');

        if (! is_file($file)) {
            CLI::error("找不到來源檔：{$file}");
            return;
        }

        $rows = $this->readCsv($file);
        if ($rows === []) {
            CLI::error('CSV 沒有可用資料（需要 tax_id 與 name 兩欄）');
            return;
        }

        $model = new CustomerModel();
        $created = [];
        $skipped = [];
        $noTaxId = [];
        $failed  = [];

        CLI::write('來源：' . $file, 'dark_gray');
        CLI::write('共 ' . count($rows) . ' 筆' . ($dryRun ? '（試算模式，不會寫入）' : ''), 'dark_gray');
        CLI::newLine();

        foreach ($rows as $row) {
            $name  = $row['name'];
            $taxId = $row['tax_id'];

            // 已存在就跳過：有統編比統編，沒統編比名稱
            $existing = $taxId !== null
                ? $model->where('c_tax_id', $taxId)->first()
                : $model->where('c_name', $name)->first();

            if ($existing) {
                $skipped[] = ($existing['c_code'] ?? '') . ' ' . $name;
                continue;
            }

            if ($taxId === null) {
                $noTaxId[] = $name;
            }

            if ($dryRun) {
                $created[] = '(試算) ' . $name;
                continue;
            }

            $data = [
                'c_code'   => $model->generateCustomerCode(),
                'c_name'   => $name,
                'c_tax_id' => $taxId,
            ];

            if ($model->insert($data)) {
                $created[] = $data['c_code'] . ' ' . $name;
            } else {
                $failed[] = $name . '：' . implode('、', $model->errors());
            }
        }

        $this->section('新增', $created, 'green');
        $this->section('已存在，略過', $skipped, 'dark_gray');
        $this->section('失敗', $failed, 'red');

        if ($noTaxId !== []) {
            CLI::newLine();
            CLI::write('⚠ 下列客戶的資料夾統編是佔位值（00000000），已存成空白，請日後補上：', 'yellow');
            foreach ($noTaxId as $n) {
                CLI::write('    ' . $n, 'yellow');
            }
        }

        CLI::newLine();
        CLI::write(str_repeat('=', 58), 'dark_gray');
        CLI::write(sprintf(
            '客戶匯入：新增 %d　略過 %d　失敗 %d　（資料庫現有 %d 筆）',
            count($created), count($skipped), count($failed), $model->countAllResults()
        ), $failed ? 'red' : 'green');
    }

    /** 讀 CSV（處理 UTF-8 BOM 與欄位順序），回傳 [['tax_id'=>?string,'name'=>string], ...] */
    private function readCsv(string $file): array
    {
        $handle = fopen($file, 'r');
        if ($handle === false) {
            return [];
        }

        // Windows 的 Export-Csv 會寫 UTF-8 BOM，且 BOM 在引號之前 ——
        // 不先跳過位元組的話，fgetcsv 會把第一個欄名讀成 「BOM"tax_id"」（連引號一起）
        if (fread($handle, 3) !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return [];
        }

        $header = array_map(fn($h) => strtolower(trim((string) $h, " \t\n\r\0\x0B\"'")), $header);

        $iTax  = array_search('tax_id', $header, true);
        $iName = array_search('name', $header, true);
        if ($iTax === false || $iName === false) {
            fclose($handle);
            return [];
        }

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            $name = trim((string) ($line[$iName] ?? ''));
            if ($name === '') {
                continue;
            }

            $taxId = trim((string) ($line[$iTax] ?? ''));
            // 00000000 是資料夾裡的佔位值，不是真統編；只接受 8 位數字
            if (! preg_match('/^\d{8}$/', $taxId) || $taxId === '00000000') {
                $taxId = null;
            }

            $rows[] = ['tax_id' => $taxId, 'name' => mb_substr($name, 0, 50)];
        }
        fclose($handle);

        return $rows;
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
