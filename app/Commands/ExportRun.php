<?php

namespace App\Commands;

use App\Controllers\ExportController;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 由命令列產生匯出檔（也可用於排程自動產報表）。
 *
 *   php spark erp:export --list
 *   php spark erp:export pnl --format=pdf --out=writable/exports
 *   php spark erp:export transaction --format=xlsx --filter="ym=2026-07"
 *   php spark erp:export --all --out=writable/exports      （每個項目各產一份，用於煙霧測試）
 */
class ExportRun extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:export';
    protected $description = '產生 Excel / PDF 匯出檔（可用於排程或測試）';
    protected $usage       = 'erp:export [key] [--list] [--all] [--format=xlsx|pdf] [--out=dir] [--filter="k=v&k2=v2"]';

    public function run(array $params)
    {
        // CI4 的 CLI 只認 "--opt value"；這裡額外支援 "--opt=value" 寫法
        $opts = CLI::getOptions();
        foreach ($opts as $k => $v) {
            if (str_contains($k, '=')) {                 // "--format=pdf" 被解析成鍵 "format=pdf"
                [$k2, $v2] = explode('=', $k, 2);
                $opts[$k2] = $v2;
                unset($opts[$k]);
            }
        }
        $opt = fn(string $k, $d = null) => (isset($opts[$k]) && $opts[$k] !== null && $opts[$k] !== true) ? $opts[$k] : $d;

        $ctl = new ExportController();

        if (array_key_exists('list', $opts)) {
            foreach ($ctl->keys() as $k) CLI::write('  ' . $k);
            return;
        }

        $format = $opt('format', 'xlsx');
        if (!in_array($format, ['xlsx', 'pdf'], true)) { CLI::write('format 只能是 xlsx 或 pdf', 'red'); return; }

        $outDir = $opt('out', WRITEPATH . 'exports');
        if (!is_dir($outDir)) @mkdir($outDir, 0777, true);

        $filters = [];
        if ($f = $opt('filter')) parse_str($f, $filters);

        $keys = array_key_exists('all', $opts) ? $ctl->keys() : array_filter([$params[0] ?? null]);
        if (!$keys) { CLI::write('請指定匯出項目，或用 --list 查看可用項目', 'yellow'); return; }

        $ok = 0; $fail = 0;
        foreach ($keys as $key) {
            $path = rtrim($outDir, '/\\') . DIRECTORY_SEPARATOR . $key . '.' . $format;
            try {
                (new ExportController())->setFilters($filters)->render($key, $format, $path);
                $size = is_file($path) ? round(filesize($path) / 1024) : 0;
                CLI::write(sprintf('  [OK]   %-22s %s (%d KB)', $key, $path, $size), 'green');
                $ok++;
            } catch (\Throwable $e) {
                CLI::write(sprintf('  [FAIL] %-22s %s: %s', $key, get_class($e), $e->getMessage()), 'red');
                $fail++;
            }
        }
        CLI::newLine();
        CLI::write("完成：成功 {$ok}　失敗 {$fail}", $fail ? 'red' : 'green');
    }
}
