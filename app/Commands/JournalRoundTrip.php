<?php

namespace App\Commands;

use App\Libraries\JournalGlPoster;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 反向橋接的往返驗證。
 *
 * 目前資料庫裡有一組現成的對照組：收付交易（gl_transactions）經 GlJournalPoster
 * 正向產生的傳票。把這些傳票**反推**回收付交易，結果必須跟原始交易逐欄一致 ——
 * 一致才代表 JournalGlPoster 的推導規則正好是正向範本的逆運算，
 * 之後日常只開傳票時，四階損益與資金餘額表才會拿到跟現在同樣正確的數字。
 *
 *   php spark erp:journal-roundtrip
 *   php spark erp:journal-roundtrip --limit 20   # 只看前 20 筆差異細節
 *
 * 這支指令**完全不寫入資料庫**，純比對。
 */
class JournalRoundTrip extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:journal-roundtrip';
    protected $description = '驗證「傳票 → 收付交易」的反向推導與原始資料一致（唯讀）';
    protected $usage       = 'erp:journal-roundtrip [--limit <n>]';
    protected $options     = ['--limit' => '最多列出幾筆差異細節（預設 10）'];

    /** 要逐欄比對的項目 */
    private const FIELDS = [
        't_ac_id'         => '會計科目',
        't_direction'     => '收付方向',
        't_amount'        => '未稅金額',
        't_tax'           => '稅額',
        't_settle_status' => '收付狀態',
        't_segment'       => '商業模式',
        't_date'          => '日期',
    ];

    public function run(array $params)
    {
        $limit  = (int) (CLI::getOption('limit') ?: 10);
        $db     = \Config\Database::connect();
        $poster = new JournalGlPoster();

        $vouchers = $db->table('journal_vouchers')
            ->where('jv_source_type', 'gl')
            ->orderBy('jv_id', 'ASC')
            ->get()->getResultArray();

        if ($vouchers === []) {
            CLI::error('找不到由收付交易正向產生的傳票，無法做往返驗證');
            return;
        }

        CLI::write('對照組：' . count($vouchers) . ' 張由收付交易產生的傳票', 'dark_gray');
        CLI::newLine();

        $match = 0;
        $countMismatch = [];
        $fieldMismatch = [];

        foreach ($vouchers as $v) {
            $origin = $db->table('gl_transactions')
                ->where('t_id', (int) $v['jv_source_id'])->get()->getRowArray();

            if (! $origin) {
                continue;   // 來源交易已被刪除，不列入比對
            }

            $derived = $poster->derive($v);

            if (count($derived) !== 1) {
                $countMismatch[] = sprintf(
                    '%s（%s）推導出 %d 筆，應為 1 筆',
                    $v['jv_no'], mb_substr((string) $v['jv_summary'], 0, 24), count($derived)
                );
                continue;
            }

            $diffs = [];
            foreach (self::FIELDS as $field => $label) {
                $a = (string) ($origin[$field] ?? '');
                $b = (string) ($derived[0][$field] ?? '');
                if ($a !== $b) {
                    $diffs[] = "{$label} 原始「{$a}」→ 推導「{$b}」";
                }
            }

            if ($diffs === []) {
                $match++;
            } else {
                $fieldMismatch[] = sprintf(
                    '%s（%s）：%s',
                    $v['jv_no'], mb_substr((string) $v['jv_summary'], 0, 24), implode('；', $diffs)
                );
            }
        }

        $this->report('欄位不一致', $fieldMismatch, $limit);
        $this->report('推導筆數不對', $countMismatch, $limit);

        $failed = count($fieldMismatch) + count($countMismatch);

        CLI::newLine();
        CLI::write(str_repeat('=', 58), 'dark_gray');
        CLI::write(
            "往返驗證：完全一致 {$match}　不一致 {$failed}",
            $failed ? 'red' : 'green'
        );

        if ($failed === 0) {
            CLI::write('反向推導與原始收付交易完全相同 —— 日常改用傳票輸入不會影響四階損益與資金餘額表。', 'green');
        }
    }

    private function report(string $title, array $items, int $limit): void
    {
        if ($items === []) {
            return;
        }

        CLI::write("{$title}（" . count($items) . '）', 'red');
        foreach (array_slice($items, 0, $limit) as $item) {
            CLI::write('    ' . $item, 'red');
        }
        if (count($items) > $limit) {
            CLI::write('    …另有 ' . (count($items) - $limit) . ' 筆，用 --limit 調整顯示數量', 'dark_gray');
        }
        CLI::newLine();
    }
}
