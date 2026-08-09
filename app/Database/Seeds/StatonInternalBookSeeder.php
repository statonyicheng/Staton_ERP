<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;

/**
 * 匯入【仕坦登】公司內帳 → gl_transactions（收付實現制）
 *
 * 來源檔：writable/imports/staton_internal_book.xlsx
 *   工作表「收入明細」「費用明細」欄位：A 日期 / B 性質 / C 未稅 / D 稅 / E 含稅 / F 項目
 *   費用另有 G 支付方 / H 是否已匯款
 *
 * 特性：
 *  - 以 t_source = 'internal_book' 標記，重跑會先清掉上次匯入的資料，
 *    不會動到使用者在畫面上手動登錄的交易。
 *  - 金額四捨五入到元（本系統不使用小數）。
 *  - 依項目關鍵字自動歸屬會計科目與業務別（M-1~M-5），對不到的落在「其他營業收入 / 其他費用」並列入報告。
 */
class StatonInternalBookSeeder extends Seeder
{
    private const SOURCE = 'internal_book';

    /** 收入：關鍵字 → [科目代碼, 業務別]。由上而下第一個命中者勝出，順序有意義。 */
    private array $incomeRules = [
        ['keys' => ['企業管家'],                                              'code' => '4201', 'seg' => 'M-1'],
        ['keys' => ['代墊', '代繳', '二代健保', '請款收入', '高鐵票'],          'code' => '4206', 'seg' => 'M-5'],
        ['keys' => ['分潤', '介紹案件'],                                      'code' => '4205', 'seg' => 'M-5'],
        ['keys' => ['記帳費', '帳務費', '稅務外帳', '整帳', '所得稅', '申報'],  'code' => '4202', 'seg' => 'M-2'],
        ['keys' => ['設立', '變更', '移轉股權', '登記', '商標'],               'code' => '4203', 'seg' => 'M-3'],
        ['keys' => ['專案', '內控', '服務時數', '補助案', '募資', '開辦費', '契約'], 'code' => '4204', 'seg' => 'M-4'],
    ];

    /** 費用：內帳「性質」→ 科目代碼 */
    private array $expenseMap = [
        '薪資' => '6305',   // 三階-顧問人員用人成本
        '分潤' => '6204',   // 銷-分潤費（二階）
        '行銷' => '6202',   // 銷-廣告費（二階）
        '租金' => '6403',   // 管-租金
        '郵電' => '6404',   // 管-郵電費
        '交際' => '6405',   // 管-交際費
        '交通' => '6406',   // 管-交通費
        'IT'   => '6408',   // 管-網路平台費
        '文書' => '6409',   // 管-文具費用
        '財務' => '6416',   // 管-稅捐及帳務費
        'HR'   => '6417',   // 管-人力招募費
        '住宿' => '6418',   // 管-差旅住宿費
    ];

    public function run()
    {
        $path = WRITEPATH . 'imports/staton_internal_book.xlsx';
        if (!is_file($path)) {
            echo "找不到來源檔：{$path}\n";
            return;
        }

        // 科目代碼 → ac_id
        $acc = [];
        foreach ($this->db->table('accounts')->select('ac_id, ac_code')->get()->getResultArray() as $a) {
            $acc[$a['ac_code']] = (int) $a['ac_id'];
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $ss = $reader->load($path);

        $batch = [];
        $report = ['income' => 0, 'expense' => 0, 'fallback' => [], 'skipped' => []];
        $now = date('Y-m-d H:i:s');

        // ---------- 收入明細 ----------
        foreach ($this->rowsOf($ss, '收入明細') as $r) {
            $date = $this->normDate($r[0] ?? null);
            $item = trim((string) ($r[5] ?? ''));
            if (!$date || $item === '' || trim((string) ($r[1] ?? '')) === '') {
                if ($item !== '') $report['skipped'][] = "收入：{$item}（日期無法解析）";
                continue;
            }
            [$code, $seg] = $this->classifyIncome($item, $report);
            [$partner, $summary] = $this->splitItem($item);

            $batch[] = [
                't_date' => $date,
                't_ym' => substr($date, 0, 7),
                't_summary' => mb_substr($summary, 0, 255),
                't_partner' => $partner === null ? null : mb_substr($partner, 0, 100),
                't_direction' => '收',
                't_segment' => $seg,
                't_ac_id' => $acc[$code] ?? $acc['4105'],
                't_amount' => (int) round((float) ($r[2] ?? 0)),
                't_tax' => (int) round((float) ($r[3] ?? 0)),
                't_settle_status' => '已收付',
                't_settle_date' => $date,
                't_note' => null,
                't_source' => self::SOURCE,
                't_created_at' => $now,
                't_updated_at' => $now,
            ];
            $report['income']++;
        }

        // ---------- 費用明細 ----------
        foreach ($this->rowsOf($ss, '費用明細') as $r) {
            $date = $this->normDate($r[0] ?? null);
            $nature = trim((string) ($r[1] ?? ''));
            $item = trim((string) ($r[5] ?? ''));
            if (!$date || $item === '' || $nature === '') {
                if ($item !== '') $report['skipped'][] = "費用：{$item}（日期無法解析）";
                continue;
            }
            $code = $this->expenseMap[$nature] ?? null;
            if ($code === null) {
                $code = '6415'; // 管-其他費用
                $report['fallback'][] = "費用性質「{$nature}」無對應科目 → 管-其他費用";
            }
            $payer = trim((string) ($r[6] ?? ''));
            $paid = trim((string) ($r[7] ?? ''));

            $batch[] = [
                't_date' => $date,
                't_ym' => substr($date, 0, 7),
                't_summary' => mb_substr($item, 0, 255),
                't_partner' => $payer !== '' ? mb_substr($payer, 0, 100) : null,
                't_direction' => '付',
                't_segment' => 'M-0',
                't_ac_id' => $acc[$code] ?? $acc['6415'],
                't_amount' => (int) round((float) ($r[2] ?? 0)),
                't_tax' => (int) round((float) ($r[3] ?? 0)),
                't_settle_status' => $paid === '未撥款' ? '未收付' : '已收付',
                't_settle_date' => $paid === '未撥款' ? null : $date,
                't_note' => trim("性質:{$nature}｜支付方:{$payer}｜撥款:{$paid}", '｜'),
                't_source' => self::SOURCE,
                't_created_at' => $now,
                't_updated_at' => $now,
            ];
            $report['expense']++;
        }

        // ---------- 寫入（先清上次匯入） ----------
        $this->db->transStart();
        $removed = $this->db->table('gl_transactions')->where('t_source', self::SOURCE)->countAllResults(false);
        $this->db->table('gl_transactions')->where('t_source', self::SOURCE)->delete();

        // 【嵐可】示範資料不能跟真實內帳並存 —— 四階損益、資金餘額表、總帳、四大財務報表
        // 都是直接加總 gl_transactions，兩套帳留在同一個資料庫，數字會全部疊在一起。
        // （LankeFinanceSeeder 反過來也會清掉 internal_book，兩支是對稱的。）
        $lankeCount = $this->db->table('gl_transactions')->where('t_source', 'lanke_book')->countAllResults(false);
        $this->db->table('gl_transactions')->where('t_source', 'lanke_book')->delete();

        // 一次性清掉舊的【嵐石】石材樣本（TransactionSampleSeeder 灌的）。
        // 只比對這批樣本的固定摘要，不會誤刪使用者自己登錄的交易。
        $legacy = ['洞石訂單', '石材進貨', '順豐運費', 'AI 廣告投放', '顧問勞務費', '型錄印刷', '停車 / 交通費'];
        $legacyCount = $this->db->table('gl_transactions')
            ->where('t_source', null)->whereIn('t_summary', $legacy)->countAllResults(false);
        $this->db->table('gl_transactions')->where('t_source', null)->whereIn('t_summary', $legacy)->delete();
        foreach (array_chunk($batch, 200) as $chunk) {
            $this->db->table('gl_transactions')->insertBatch($chunk);
        }
        $this->db->transComplete();

        // ---------- 報告 ----------
        $inNet = $inTax = $exNet = $exTax = 0;
        foreach ($batch as $b) {
            if ($b['t_direction'] === '收') { $inNet += $b['t_amount']; $inTax += $b['t_tax']; }
            else { $exNet += $b['t_amount']; $exTax += $b['t_tax']; }
        }
        echo "── 【仕坦登】公司內帳匯入完成 ──\n";
        echo "清除上次匯入：{$removed} 筆　清除【嵐可】示範資料：{$lankeCount} 筆　清除嵐石舊樣本：{$legacyCount} 筆\n";
        echo "收入 {$report['income']} 筆　未稅 " . number_format($inNet) . "　稅 " . number_format($inTax) . "　含稅 " . number_format($inNet + $inTax) . "\n";
        echo "費用 {$report['expense']} 筆　未稅 " . number_format($exNet) . "　稅 " . number_format($exTax) . "　含稅 " . number_format($exNet + $exTax) . "\n";
        echo "淨利（內帳口徑：收入含稅 − 費用未稅）= " . number_format(($inNet + $inTax) - $exNet) . "\n";
        foreach (array_unique($report['fallback']) as $f) echo "  ⚠ {$f}\n";
        foreach ($report['skipped'] as $s) echo "  ⚠ 略過 {$s}\n";
    }

    /** 取得工作表的資料列（跳過標題列） */
    private function rowsOf($ss, string $sheet): array
    {
        $sh = $ss->getSheetByName($sheet);
        if (!$sh) return [];
        $rows = $sh->toArray(null, true, false, false);
        array_shift($rows);
        return $rows;
    }

    /**
     * Excel 日期 → Y-m-d。
     * 內帳中有「2026/4/31」這種不存在的日期（4 月只有 30 天），自動修正為當月最後一日。
     */
    private function normDate($v): ?string
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) {
            return XlDate::excelToDateTimeObject((float) $v)->format('Y-m-d');
        }
        $s = str_replace(['年', '月'], '-', trim((string) $v));
        $s = rtrim(str_replace('日', '', $s), '-');
        if (preg_match('#^(\d{4})[/\-](\d{1,2})[/\-](\d{1,2})$#', $s, $m)) {
            $y = (int) $m[1]; $mo = (int) $m[2]; $d = (int) $m[3];
            if ($mo < 1 || $mo > 12) return null;
            $last = (int) date('t', mktime(0, 0, 0, $mo, 1, $y));
            $d = max(1, min($d, $last));
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }
        $ts = strtotime($s);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    /** 依項目關鍵字歸屬收入科目與業務別 */
    private function classifyIncome(string $item, array &$report): array
    {
        foreach ($this->incomeRules as $rule) {
            foreach ($rule['keys'] as $k) {
                if (mb_strpos($item, $k) !== false) {
                    return [$rule['code'], $rule['seg']];
                }
            }
        }
        $report['fallback'][] = "收入項目「{$item}」無法歸類 → 營-其他營業收入";
        return ['4105', 'M-0'];
    }

    /** 「客戶/服務內容」→ [客戶, 摘要] */
    private function splitItem(string $item): array
    {
        $pos = mb_strpos($item, '/');
        if ($pos === false) return [null, $item];

        $partner = mb_substr($item, 0, $pos);
        $summary = mb_substr($item, $pos + 1);
        // 「速達分潤費/介紹案件」→ 客戶應為「速達」
        foreach (['分潤費', '分潤', '收入', '費用'] as $suffix) {
            if (mb_substr($partner, -mb_strlen($suffix)) === $suffix && mb_strlen($partner) > mb_strlen($suffix)) {
                $partner = mb_substr($partner, 0, -mb_strlen($suffix));
                break;
            }
        }
        return [$partner !== '' ? $partner : null, $summary !== '' ? $summary : $item];
    }
}
