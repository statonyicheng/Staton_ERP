<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ArApBook;
use App\Libraries\PaymentTerms;

/**
 * 應收／應付的帳齡與周轉分析。
 *
 * 兩個問題一次回答：
 *   1. **誰欠我、我欠誰、逾期多久** —— 依對象（客戶／廠商）與其付款條件推算到期日，
 *      算出逾期天數並分帳齡區間。用傳票日期直接當到期日會把月結、還沒到期的
 *      通通誤判成逾期，所以一定要走付款條件。
 *   2. **周轉快不快** —— 應收帳款周轉率與平均收現天數（DSO）、
 *      應付帳款周轉率與平均付款天數（DPO）。
 *
 * 資料一律取自會計帳（`ArApBook`），與應收/應付管理畫面同一個來源。
 */
class ArApAnalysisController extends BaseController
{
    public function index()
    {
        $type = $this->request->getGet('type') === 'AP' ? ArApBook::AP : ArApBook::AR;
        $year = (int) ($this->request->getGet('year') ?: date('Y'));

        $book = new ArApBook();

        return view('ar_ap_analysis/index', [
            'type' => $type,
            'isAr' => $type === ArApBook::AR,
            'year' => $year,
            'years' => $this->availableYears(),
            'aging' => $book->aging($type),
            'buckets' => PaymentTerms::BUCKETS,
            'summary' => $book->summary($type),
            'turnover' => $this->turnover($type, $year),
        ]);
    }

    /** 資料庫裡有交易的年度（供年度下拉） */
    private function availableYears(): array
    {
        $rows = db_connect()->query(
            'SELECT DISTINCT LEFT(t_ym, 4) y FROM gl_transactions ORDER BY y DESC'
        )->getResultArray();

        $years = array_map('intval', array_column($rows, 'y'));

        return $years ?: [(int) date('Y')];
    }

    /**
     * 周轉率與周轉天數。
     *
     * 應收：周轉率 = 年度營業收入 ÷ 平均應收餘額；DSO = 365 ÷ 周轉率
     * 應付：周轉率 = 年度費用/進貨 ÷ 平均應付餘額；DPO = 365 ÷ 周轉率
     *
     * 平均餘額用「期初＋期末 ÷ 2」—— 只用期末會被年底一次性的大額拉歪。
     * 分母為 0（沒有賒帳）時不計算，直接標示無法計算，不要硬擠一個數字出來。
     */
    private function turnover(string $type, int $year): array
    {
        $db = db_connect();
        $isAr = $type === ArApBook::AR;

        // 分子：該年度的營業收入（應收）或費用（應付），未稅
        $direction = $isAr ? '收' : '付';
        $flow = (int) ($db->query(
            'SELECT COALESCE(SUM(t.t_amount), 0) n
               FROM gl_transactions t
               JOIN accounts a ON a.ac_id = t.t_ac_id
              WHERE a.ac_is_pl = 1 AND t.t_direction = ? AND LEFT(t.t_ym, 4) = ?',
            [$direction, (string) $year]
        )->getRow()->n ?? 0);

        // 分母：期初／期末的未沖銷餘額（以傳票日期切期間）
        $opening = $this->balanceAt($type, sprintf('%d-01-01', $year));
        $closing = $this->balanceAt($type, sprintf('%d-12-31', $year));
        $average = (int) round(($opening + $closing) / 2);

        $rate = $average > 0 ? round($flow / $average, 2) : null;
        $days = ($rate !== null && $rate > 0) ? (int) round(365 / $rate) : null;

        return [
            'flow' => $flow,
            'opening' => $opening,
            'closing' => $closing,
            'average' => $average,
            'rate' => $rate,
            'days' => $days,
            'flowLabel' => $isAr ? '年度營業收入（未稅）' : '年度費用／進貨（未稅）',
            'daysLabel' => $isAr ? '平均收現天數（DSO）' : '平均付款天數（DPO）',
        ];
    }

    /** 某個日期當下，該類科目尚未沖銷的餘額 */
    private function balanceAt(string $type, string $date): int
    {
        $r = db_connect()->query(
            'SELECT COALESCE(SUM(je.je_debit + je.je_credit - je.je_offset), 0) n
               FROM journal_entries je
               JOIN journal_vouchers v ON v.jv_id = je.je_jv_id
               JOIN accounts a ON a.ac_id = je.je_ac_id
              WHERE a.ac_ar_ap = ? AND v.jv_date <= ?',
            [$type, $date]
        )->getRow();

        return (int) ($r->n ?? 0);
    }
}
