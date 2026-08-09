<?php

namespace App\Models;

use App\Models\AuditedModel;

class TransactionModel extends AuditedModel
{
    protected $table = 'gl_transactions';
    protected $primaryKey = 't_id';
    protected $allowedFields = [
        't_date', 't_ym', 't_summary', 't_partner', 't_direction',
        't_segment', 't_ac_id', 't_amount', 't_tax', 't_settle_status', 't_settle_date', 't_note',
    ];

    protected $useTimestamps = true;
    protected $createdField = 't_created_at';
    protected $updatedField = 't_updated_at';

    protected $validationRules = [
        't_date' => 'required|valid_date',
        't_summary' => 'required|max_length[255]',
        't_ac_id' => 'required|is_natural_no_zero',
        't_amount' => 'permit_empty|integer',
    ];
    protected $validationMessages = [
        't_date' => ['required' => '交易日期為必填'],
        't_summary' => ['required' => '摘要為必填'],
        't_ac_id' => ['required' => '請選擇會計科目'],
    ];

    /**
     * 業務別（商業模式）已改成可維護的主檔 `business_segments`
     * （基本資料管理 → 業務別設定），不再寫死在程式碼裡。
     * 這兩個存取點保留下來，全站沿用同一個來源。
     *
     * ⚠ 業務別的標籤必須跟資料來源對得上：【嵐可】示範資料用的是另一套商業模式
     * （空間租賃／借址登記／額外服務），換資料來源時主檔也要一起改，
     * 否則報表會數字對、名稱錯 —— 比錯得明顯更難發現。
     */
    public static function segments(): array
    {
        return \App\Models\BusinessSegmentModel::map();
    }

    /** 進損益表的損益階層順序 */
    public const PL_TIERS = ['營業收入', '一階成本', '二階費用', '三階費用', '四階費用'];

    /** 損益表用的營運業務別；由主檔的「列入四階損益」決定，不含非營業 */
    public static function plSegments(): array
    {
        return \App\Models\BusinessSegmentModel::plCodes();
    }

    public function getList($keyword = null, $page = 1, $ym = null)
    {
        $builder = $this->db->table('gl_transactions t')
            ->select('t.*, a.ac_name, a.ac_code, a.ac_tier, a.ac_category')
            ->join('accounts a', 'a.ac_id = t.t_ac_id', 'left');

        if ($keyword) {
            $builder->groupStart()
                ->like('t.t_summary', $keyword)->orLike('t.t_partner', $keyword)->orLike('a.ac_name', $keyword)
                ->groupEnd();
        }
        if ($ym) {
            $builder->where('t.t_ym', $ym);
        }
        $builder->orderBy('t.t_date', 'DESC')->orderBy('t.t_id', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = \App\Libraries\PageSize::get(15);
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    /**
     * 四階損益分析：依業務別 × 損益階層彙總
     *
     * @param string      $from  起始年月 yyyy-mm
     * @param string|null $to    結束年月 yyyy-mm（null＝與 $from 同月，即單月報表）
     * @param bool        $withTax 金額基準：false＝未稅（會計標準）、true＝含稅（對帳用）
     */
    public function pnl(string $from, ?string $to = null, bool $withTax = false): array
    {
        $to ??= $from;
        if ($to < $from) [$from, $to] = [$to, $from];

        // 報表欄位由業務別主檔決定（基本資料管理 → 業務別設定），不再寫死在程式碼裡
        $plSegments = self::plSegments();

        $amtExpr = $withTax ? '(t.t_amount + t.t_tax)' : 't.t_amount';

        $rows = $this->db->table('gl_transactions t')
            ->select("a.ac_tier tier, t.t_segment seg, SUM({$amtExpr}) amt", false)
            ->join('accounts a', 'a.ac_id = t.t_ac_id', 'left')
            ->where('t.t_ym >=', $from)
            ->where('t.t_ym <=', $to)
            ->where('a.ac_is_pl', 1)
            ->groupBy('a.ac_tier, t.t_segment')
            ->get()->getResultArray();

        // matrix[tier][seg] = amt
        $m = [];
        foreach (self::PL_TIERS as $tier) {
            foreach ($plSegments as $seg) $m[$tier][$seg] = 0;
            $m[$tier]['total'] = 0;
        }
        foreach ($rows as $r) {
            $tier = $r['tier']; $seg = $r['seg'];
            if (!isset($m[$tier])) continue;
            if (!in_array($seg, $plSegments, true)) $seg = \App\Models\BusinessSegmentModel::fallbackCode();
            $m[$tier][$seg] += (int) $r['amt'];
            $m[$tier]['total'] += (int) $r['amt'];
        }

        // 計算各階毛利
        $cols = array_merge($plSegments, ['total']);
        $calc = fn($a, $b) => array_combine($cols, array_map(fn($c) => ($m[$a][$c] ?? 0) - ($m[$b][$c] ?? 0), $cols));
        $sub = function ($tierA, $expenseTier) use ($m, $cols) {
            $out = [];
            foreach ($cols as $c) $out[$c] = ($tierA[$c] ?? 0) - ($m[$expenseTier][$c] ?? 0);
            return $out;
        };

        $gp1 = [];
        foreach ($cols as $c) $gp1[$c] = ($m['營業收入'][$c] ?? 0) - ($m['一階成本'][$c] ?? 0);
        $gp2 = $sub($gp1, '二階費用');
        $gp3 = $sub($gp2, '三階費用');
        $gp4 = $sub($gp3, '四階費用');

        return [
            'from' => $from, 'to' => $to, 'withTax' => $withTax,
            'ym' => $from === $to ? $from : "{$from} ~ {$to}",
            'segments' => $plSegments,
            'matrix' => $m,
            'gp1' => $gp1, 'gp2' => $gp2, 'gp3' => $gp3, 'gp4' => $gp4,
        ];
    }

    /**
     * 資金餘額表：逐月期初＋淨變動＝期末（含稅、收付基礎）
     */
    public function cashflow(int $year, int $openingBalance = 0): array
    {
        $rows = $this->db->table('gl_transactions')
            ->select("t_ym, t_direction, SUM(t_amount + t_tax) amt")
            ->where('t_settle_status', '已收付')
            ->like('t_ym', (string) $year, 'after')
            ->groupBy('t_ym, t_direction')
            ->get()->getResultArray();

        $byMonth = [];
        for ($mo = 1; $mo <= 12; $mo++) {
            $byMonth[sprintf('%04d-%02d', $year, $mo)] = ['in' => 0, 'out' => 0];
        }
        foreach ($rows as $r) {
            if (!isset($byMonth[$r['t_ym']])) continue;
            if ($r['t_direction'] === '收') $byMonth[$r['t_ym']]['in'] += (int) $r['amt'];
            else $byMonth[$r['t_ym']]['out'] += (int) $r['amt'];
        }

        $result = [];
        $bal = $openingBalance;
        foreach ($byMonth as $ym => $v) {
            $net = $v['in'] - $v['out'];
            $open = $bal;
            $bal += $net;
            $result[] = [
                'ym' => $ym, 'in' => $v['in'], 'out' => $v['out'],
                'net' => $net, 'open' => $open, 'close' => $bal,
            ];
        }
        return $result;
    }

    /**
     * 會計總帳：依科目彙總（可選月份）
     */
    public function ledger(?string $ym = null): array
    {
        $builder = $this->db->table('gl_transactions t')
            ->select('a.ac_code, a.ac_name, a.ac_tier, a.ac_category,
                      SUM(CASE WHEN t.t_direction = "收" THEN t.t_amount + t.t_tax ELSE 0 END) as debit_in,
                      SUM(CASE WHEN t.t_direction = "付" THEN t.t_amount + t.t_tax ELSE 0 END) as credit_out,
                      COUNT(*) as cnt')
            ->join('accounts a', 'a.ac_id = t.t_ac_id', 'left');
        if ($ym) $builder->where('t.t_ym', $ym);
        $builder->groupBy('t.t_ac_id')->orderBy('a.ac_code', 'ASC');
        return $builder->get()->getResultArray();
    }

    /** 資料庫中有交易的所有年月（供下拉） */
    public function availableMonths(): array
    {
        $rows = $this->db->table('gl_transactions')->select('t_ym')->distinct()
            ->orderBy('t_ym', 'DESC')->get()->getResultArray();
        return array_column($rows, 't_ym');
    }

    /**
     * 指定年度之前累積的資金結餘（已收付、含稅）。
     * 資金餘額表的年度期初必須承接前一年度期末，否則跨年度資料每年都會從 0 重算。
     */
    public function cashOpeningBefore(int $year): int
    {
        $r = $this->db->table('gl_transactions')
            ->select("SUM(CASE WHEN t_direction = '收' THEN t_amount + t_tax ELSE -(t_amount + t_tax) END) bal", false)
            ->where('t_settle_status', '已收付')
            ->where('t_ym <', sprintf('%04d-01', $year))
            ->get()->getRowArray();
        return (int) ($r['bal'] ?? 0);
    }

    /** 資料涵蓋的年月區間（供報表預設期間） */
    public function periodRange(): array
    {
        $r = $this->db->table('gl_transactions')->select('MIN(t_ym) mn, MAX(t_ym) mx')->get()->getRowArray();
        $now = date('Y-m');
        return ['min' => $r['mn'] ?: $now, 'max' => $r['mx'] ?: $now];
    }

    /** 資料庫中有交易的所有年度（供下拉） */
    public function availableYears(): array
    {
        $rows = $this->db->table('gl_transactions')->select('DISTINCT LEFT(t_ym,4) y', false)
            ->orderBy('y', 'DESC')->get()->getResultArray();
        $years = array_map('intval', array_column($rows, 'y'));
        return $years ?: [(int) date('Y')];
    }
}
