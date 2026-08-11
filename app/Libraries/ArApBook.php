<?php

namespace App\Libraries;

/**
 * 帳上的應收 / 應付（直接讀分錄，不另外維護一張表）。
 *
 * 舊做法是 `receivables` / `payables` 兩張獨立的表，只能從採購單或手動建立，
 * 跟分錄傳票毫無關聯 —— 開一張「借薪資費用／貸應付薪資」的傳票，
 * 帳上明明多了一筆負債，應付帳款管理卻是空的。
 *
 * 現在改成：**應收付＝帳上被標記為應收/應付的科目，尚未沖銷的分錄**。
 * 一筆應付從產生到付清的完整過程都是分錄：
 *   產生：借 費用／貸 應付薪資      → 出現在應付清單
 *   付款：借 應付薪資／貸 銀行存款  → 到立沖帳把兩筆勾銷 → 從未付餘額消失
 *
 * 好處是不會有「帳上有、管理畫面沒有」的落差，而且沖銷日期會同步讓
 * 收付制報表（資金餘額表）認列在正確的月份（見 JournalGlPoster）。
 */
class ArApBook
{
    public const AR = 'AR';
    public const AP = 'AP';

    private $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * 未沖銷明細（＝還沒收到／還沒付掉的部分）。
     *
     * @param string      $type    self::AR 或 self::AP
     * @param string|null $keyword 比對傳票號、摘要、科目名稱
     * @param bool        $onlyOpen true＝只看未沖銷完的；false＝含已沖銷完的（歷史）
     */
    public function items(string $type, ?string $keyword = null, bool $onlyOpen = true): array
    {
        // 對象（客戶／廠商）與其付款條件一起帶出來 —— 帳齡分析要靠付款條件推到期日，
        // 用傳票日期直接當到期日會把「月結、還沒到期」的通通誤判成逾期
        $b = $this->db->table('journal_entries je')
            ->select('je.je_id, je.je_debit, je.je_credit, je.je_offset, je.je_offset_date, je.je_summary,
                      v.jv_id, v.jv_no, v.jv_date, v.jv_summary, v.jv_segment,
                      v.jv_partner_type, v.jv_partner_id,
                      a.ac_id, a.ac_code, a.ac_name,
                      COALESCE(c.c_name, s.s_name) as partner_name,
                      COALESCE(cpm.pm_name, spm.pm_name) as pm_name,
                      COALESCE(cpm.pm_type, spm.pm_type) as pm_type,
                      COALESCE(cpm.pm_days, spm.pm_days) as pm_days,
                      (je.je_debit + je.je_credit) as amount,
                      (je.je_debit + je.je_credit - je.je_offset) as open_amt', false)
            ->join('journal_vouchers v', 'v.jv_id = je.je_jv_id', 'left')
            ->join('accounts a', 'a.ac_id = je.je_ac_id', 'left')
            ->join('customers c', "c.c_id = v.jv_partner_id AND v.jv_partner_type = 'customer'", 'left', false)
            ->join('suppliers s', "s.s_id = v.jv_partner_id AND v.jv_partner_type = 'supplier'", 'left', false)
            ->join('payment_methods cpm', 'cpm.pm_id = c.c_pm_id', 'left')
            ->join('payment_methods spm', 'spm.pm_id = s.s_pm_id', 'left')
            ->where('a.ac_ar_ap', $type);

        if ($onlyOpen) {
            $b->where('(je.je_debit + je.je_credit - je.je_offset) >', 0, false);
        }
        if ($keyword) {
            $b->groupStart()
                ->like('v.jv_no', $keyword)
                ->orLike('v.jv_summary', $keyword)
                ->orLike('je.je_summary', $keyword)
                ->orLike('a.ac_name', $keyword)
                ->orLike('c.c_name', $keyword)
                ->orLike('s.s_name', $keyword)
                ->groupEnd();
        }

        $rows = $b->orderBy('v.jv_date', 'ASC')->orderBy('je.je_id', 'ASC')->get()->getResultArray();

        // 到期日與逾期天數在這裡算好，畫面、報表、匯出才不會各算各的
        foreach ($rows as &$r) {
            $r['due_date'] = PaymentTerms::dueDate($r['jv_date'], $r['pm_type'] ?? null, (int) ($r['pm_days'] ?? 0));
            $r['overdue_days'] = (int) $r['open_amt'] > 0 ? PaymentTerms::overdueDays($r['due_date']) : 0;
            $r['bucket'] = PaymentTerms::bucket($r['overdue_days']);
            $r['terms'] = PaymentTerms::describe($r['pm_type'] ?? null, (int) ($r['pm_days'] ?? 0));
            $r['partner_name'] = $r['partner_name'] ?: '（未指定對象）';
        }
        unset($r);

        return $rows;
    }

    /**
     * 依對象彙總的帳齡分析。
     *
     * @return array<string, array> partner_name => [terms, buckets[], total, overdue, maxOverdue, items[]]
     */
    public function aging(string $type): array
    {
        $out = [];

        foreach ($this->items($type) as $r) {
            $key = $r['partner_name'];

            if (! isset($out[$key])) {
                $out[$key] = [
                    'partner' => $key,
                    'terms' => $r['terms'],
                    'buckets' => array_fill_keys(PaymentTerms::BUCKETS, 0),
                    'total' => 0,
                    'overdue' => 0,
                    'maxOverdue' => 0,
                    'items' => [],
                ];
            }

            $open = (int) $r['open_amt'];
            $out[$key]['buckets'][$r['bucket']] += $open;
            $out[$key]['total'] += $open;
            if ($r['overdue_days'] > 0) {
                $out[$key]['overdue'] += $open;
                $out[$key]['maxOverdue'] = max($out[$key]['maxOverdue'], $r['overdue_days']);
            }
            $out[$key]['items'][] = $r;
        }

        // 逾期金額大的排前面，要先處理的自然在最上面
        uasort($out, fn($a, $b) => [$b['overdue'], $b['total']] <=> [$a['overdue'], $a['total']]);

        return $out;
    }

    /**
     * 彙總：總額 / 已沖銷 / 未沖銷餘額。
     * 直接從分錄加總，不會跟明細對不起來。
     */
    public function summary(string $type): array
    {
        $r = $this->db->table('journal_entries je')
            ->select('COALESCE(SUM(je.je_debit + je.je_credit), 0) total,
                      COALESCE(SUM(je.je_offset), 0) settled', false)
            ->join('accounts a', 'a.ac_id = je.je_ac_id', 'left')
            ->where('a.ac_ar_ap', $type)
            ->get()->getRowArray();

        $total = (int) ($r['total'] ?? 0);
        $settled = (int) ($r['settled'] ?? 0);

        return ['total' => $total, 'settled' => $settled, 'open' => $total - $settled];
    }

    /** 這個類別涵蓋哪些科目（畫面上要讓使用者知道自己在看什麼） */
    public function accounts(string $type): array
    {
        return $this->db->table('accounts')
            ->select('ac_id, ac_code, ac_name')
            ->where('ac_ar_ap', $type)
            ->orderBy('ac_code', 'ASC')
            ->get()->getResultArray();
    }
}
