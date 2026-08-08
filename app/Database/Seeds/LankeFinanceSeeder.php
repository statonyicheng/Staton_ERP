<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;

/**
 * 匯入【嵐可】財務架構（銷項總表 ＋ 進項總表）→ gl_transactions
 *
 * 來源檔：writable/imports/lanke_finance.xlsx
 *
 * 用途：作為**給同仁除錯／教育訓練用的示範資料**，取代仕坦登的真實公司內帳。
 * 這份檔案正是本系統四階損益模型的原始設計來源，欄位與 gl_transactions 幾乎一對一，
 * 科目名稱也與 AccountSeeder 建立的科目表一致。
 *
 * 執行時會同時清掉：
 *   t_source = 'lanke_book'      （本 seeder 上次匯入的）
 *   t_source = 'internal_book'   （仕坦登真實內帳 —— 不應留在給同仁用的環境）
 *
 *   php spark db:seed LankeFinanceSeeder
 */
class LankeFinanceSeeder extends Seeder
{
    private const SOURCE = 'lanke_book';

    /** 兩張表的欄位位置（0-based）；標題在第 4 列，資料從第 5 列開始 */
    private const HEADER_ROWS = 4;

    public function run()
    {
        $path = WRITEPATH . 'imports/lanke_finance.xlsx';
        if (!is_file($path)) {
            echo "找不到來源檔：{$path}\n";
            return;
        }

        // 科目名稱 → ac_id
        $acc = [];
        foreach ($this->db->table('accounts')->select('ac_id, ac_name')->get()->getResultArray() as $a) {
            $acc[trim($a['ac_name'])] = (int) $a['ac_id'];
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $ss = $reader->load($path);

        $now = date('Y-m-d H:i:s');
        $batch = [];
        $missingAcc = [];
        $skipped = 0;

        // ---------- 銷項總表（收） ----------
        foreach ($this->rowsOf($ss, '銷項總表') as $r) {
            $date = $this->normDate($r[2] ?? null);
            $name = trim((string) ($r[11] ?? ''));      // L 會計科目
            $item = trim((string) ($r[6] ?? ''));       // G 品名
            if (!$date || $name === '') { $skipped++; continue; }

            if (!isset($acc[$name])) { $missingAcc[$name] = ($missingAcc[$name] ?? 0) + 1; $skipped++; continue; }

            // 非營業列（股東往來、押金…）金額只填在 J「含稅總計（收款金額）」，
            // H 不含稅與 I 稅額都是 0 —— 這類是純資金往來，沒有損益金額。
            $net = (int) round((float) ($r[7] ?? 0));    // H 不含稅
            $tax = (int) round((float) ($r[8] ?? 0));    // I 營業稅
            if ($net === 0 && $tax === 0) {
                $net = (int) round((float) ($r[9] ?? 0)); // J 含稅總計
            }

            $settled = mb_strpos((string) ($r[13] ?? ''), '已') === 0;   // N 收現狀態
            $batch[] = [
                't_date' => $date,
                't_ym' => substr($date, 0, 7),
                't_summary' => mb_substr($item !== '' ? $item : $name, 0, 255),
                't_partner' => $this->clip($r[4] ?? null, 100),           // E 對象名稱
                't_direction' => '收',
                't_segment' => $this->segment($r[10] ?? null),            // K 業務類別
                't_ac_id' => $acc[$name],
                't_amount' => $net,
                't_tax' => $tax,
                't_settle_status' => $settled ? '已收付' : '未收付',
                't_settle_date' => $settled ? ($this->normDate($r[14] ?? null) ?: $date) : null,
                't_note' => $this->clip($r[16] ?? null, 255),             // Q 備註
                't_source' => self::SOURCE,
                't_created_at' => $now, 't_updated_at' => $now,
            ];
        }

        // ---------- 進項總表（付） ----------
        foreach ($this->rowsOf($ss, '進項總表') as $r) {
            $date = $this->normDate($r[2] ?? null);
            $name = trim((string) ($r[4] ?? ''));       // E 會計科目
            $item = trim((string) ($r[7] ?? ''));       // H 摘要
            if (!$date || $name === '') { $skipped++; continue; }

            if (!isset($acc[$name])) { $missingAcc[$name] = ($missingAcc[$name] ?? 0) + 1; $skipped++; continue; }

            $settled = mb_strpos((string) ($r[14] ?? ''), '已') === 0;   // O 付現狀態
            $payer = trim((string) ($r[17] ?? ''));                       // R 代墊人
            $note  = trim((string) ($r[20] ?? ''));                       // U 備註
            if ($payer !== '') $note = trim("代墊人:{$payer}｜{$note}", '｜');

            $batch[] = [
                't_date' => $date,
                't_ym' => substr($date, 0, 7),
                't_summary' => mb_substr($item !== '' ? $item : $name, 0, 255),
                't_partner' => $this->clip($r[5] ?? null, 100),           // F 對象名稱
                't_direction' => '付',
                't_segment' => $this->segment($r[10] ?? null),            // K 業務類別
                't_ac_id' => $acc[$name],
                't_amount' => (int) round((float) ($r[9] ?? 0)),          // J 費用（不含稅）
                't_tax' => (int) round((float) ($r[12] ?? 0)),            // M 進項稅額
                't_settle_status' => $settled ? '已收付' : '未收付',
                't_settle_date' => $settled ? ($this->normDate($r[15] ?? null) ?: $date) : null,
                't_note' => $note !== '' ? mb_substr($note, 0, 255) : null,
                't_source' => self::SOURCE,
                't_created_at' => $now, 't_updated_at' => $now,
            ];
        }

        // ---------- 寫入 ----------
        $this->db->transStart();
        $oldLanke = $this->db->table('gl_transactions')->where('t_source', self::SOURCE)->countAllResults(false);
        $this->db->table('gl_transactions')->where('t_source', self::SOURCE)->delete();

        // 仕坦登真實內帳不應留在給同仁除錯的環境
        $oldStaton = $this->db->table('gl_transactions')->where('t_source', 'internal_book')->countAllResults(false);
        $this->db->table('gl_transactions')->where('t_source', 'internal_book')->delete();

        foreach (array_chunk($batch, 300) as $chunk) {
            $this->db->table('gl_transactions')->insertBatch($chunk);
        }
        $this->db->transComplete();

        // ---------- 報告 ----------
        $in = $out = $inTax = $outTax = 0;
        foreach ($batch as $b) {
            if ($b['t_direction'] === '收') { $in += $b['t_amount']; $inTax += $b['t_tax']; }
            else { $out += $b['t_amount']; $outTax += $b['t_tax']; }
        }
        echo "── 【嵐可】財務架構匯入完成 ──\n";
        echo "清除：嵐可舊資料 {$oldLanke} 筆、仕坦登內帳 {$oldStaton} 筆\n";
        echo "匯入：共 " . count($batch) . " 筆（略過 {$skipped} 筆無日期或無科目）\n";
        echo "  銷項 未稅 " . number_format($in) . "　稅 " . number_format($inTax) . "　含稅 " . number_format($in + $inTax) . "\n";
        echo "  進項 未稅 " . number_format($out) . "　稅 " . number_format($outTax) . "　含稅 " . number_format($out + $outTax) . "\n";
        echo "  未稅淨額（收−付）= " . number_format($in - $out) . "\n";
        foreach ($missingAcc as $n => $c) echo "  ⚠ 科目「{$n}」不存在，{$c} 筆未匯入\n";
    }

    /** 取工作表資料列（工作表名稱含 emoji，用包含比對） */
    private function rowsOf($ss, string $keyword): array
    {
        foreach ($ss->getSheetNames() as $n) {
            if (mb_strpos($n, $keyword) === false) continue;
            $rows = $ss->getSheetByName($n)->toArray(null, true, false, false);
            return array_slice($rows, self::HEADER_ROWS);
        }
        return [];
    }

    private function segment($v): string
    {
        $s = trim((string) $v);
        return isset(\App\Models\TransactionModel::SEGMENTS[$s]) ? $s : 'M-0';
    }

    private function clip($v, int $len): ?string
    {
        $s = trim((string) $v);
        return $s === '' ? null : mb_substr($s, 0, $len);
    }

    private function normDate($v): ?string
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) {
            $n = (float) $v;
            if ($n < 1) return null;
            return XlDate::excelToDateTimeObject($n)->format('Y-m-d');
        }
        $ts = strtotime(str_replace('/', '-', trim((string) $v)));
        return $ts ? date('Y-m-d', $ts) : null;
    }
}
