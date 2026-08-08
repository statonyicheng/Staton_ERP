<?php

namespace App\Libraries;

/**
 * 會計三帳簿：日記帳、總分類帳、明細分類帳
 *
 * 全部由複式簿記的 journal_vouchers / journal_entries 產生（借貸基礎），
 * 與「交易登錄（收付制）」的四階損益、資金餘額是兩套不同基礎的報表，不要混用。
 *
 * 餘額方向：一律以「借方為正、貸方為負」內部計算，
 * 顯示時再依科目性質（資產/支出為借餘；負債/權益/收入為貸餘）標示借或貸。
 */
class AccountingBooks
{
    private $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /** 有傳票資料的日期區間，供報表預設期間 */
    public function dateRange(): array
    {
        $r = $this->db->table('journal_vouchers')->select('MIN(jv_date) mn, MAX(jv_date) mx')->get()->getRowArray();
        return [
            'min' => $r['mn'] ?: date('Y-01-01'),
            'max' => $r['mx'] ?: date('Y-12-31'),
        ];
    }

    /** 科目下拉（只列出實際有分錄的科目） */
    public function accountsWithEntries(): array
    {
        return $this->db->query("
            SELECT a.ac_id, a.ac_code, a.ac_name, a.ac_category
            FROM accounts a
            WHERE EXISTS (SELECT 1 FROM journal_entries e WHERE e.je_ac_id = a.ac_id)
            ORDER BY a.ac_code
        ")->getResultArray();
    }

    /**
     * 日記帳：依日期順序列出期間內每一筆分錄。
     * 這是會計帳簿的「原始序時簿」，借貸總額必須相等。
     */
    public function journal(string $from, string $to, ?int $acId = null): array
    {
        $sql = "
            SELECT v.jv_date, v.jv_no, v.jv_type, v.jv_summary,
                   a.ac_code, a.ac_name, e.je_summary, e.je_debit, e.je_credit
            FROM journal_entries e
            JOIN journal_vouchers v ON v.jv_id = e.je_jv_id
            LEFT JOIN accounts a ON a.ac_id = e.je_ac_id
            WHERE v.jv_date >= ? AND v.jv_date <= ?";
        $binds = [$from, $to];
        if ($acId) { $sql .= ' AND e.je_ac_id = ?'; $binds[] = $acId; }
        $sql .= ' ORDER BY v.jv_date, v.jv_id, e.je_sort, e.je_id';

        return $this->db->query($sql, $binds)->getResultArray();
    }

    /**
     * 總分類帳：各科目「期初餘額 ＋ 本期借方 − 本期貸方 ＝ 期末餘額」。
     * 只列出期初或本期有異動的科目。
     */
    public function ledger(string $from, string $to): array
    {
        $rows = $this->db->query("
            SELECT a.ac_id, a.ac_code, a.ac_name, a.ac_category, a.ac_tier,
                   COALESCE(SUM(CASE WHEN v.jv_date <  ? THEN e.je_debit  - e.je_credit ELSE 0 END), 0) AS opening,
                   COALESCE(SUM(CASE WHEN v.jv_date >= ? AND v.jv_date <= ? THEN e.je_debit  ELSE 0 END), 0) AS debit,
                   COALESCE(SUM(CASE WHEN v.jv_date >= ? AND v.jv_date <= ? THEN e.je_credit ELSE 0 END), 0) AS credit,
                   COALESCE(SUM(CASE WHEN v.jv_date >= ? AND v.jv_date <= ? THEN 1 ELSE 0 END), 0) AS cnt
            FROM accounts a
            JOIN journal_entries e   ON e.je_ac_id = a.ac_id
            JOIN journal_vouchers v  ON v.jv_id = e.je_jv_id
            WHERE v.jv_date <= ?
            GROUP BY a.ac_id, a.ac_code, a.ac_name, a.ac_category, a.ac_tier
            ORDER BY a.ac_code
        ", [$from, $from, $to, $from, $to, $from, $to, $to])->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $opening = (int) $r['opening'];
            $debit   = (int) $r['debit'];
            $credit  = (int) $r['credit'];
            $closing = $opening + $debit - $credit;

            // 期初、本期、期末皆為 0 的科目不列出，避免報表被空科目灌爆
            if ($opening === 0 && $debit === 0 && $credit === 0) continue;

            $out[] = [
                'ac_id' => (int) $r['ac_id'], 'ac_code' => $r['ac_code'], 'ac_name' => $r['ac_name'],
                'ac_category' => $r['ac_category'], 'ac_tier' => $r['ac_tier'],
                'cnt' => (int) $r['cnt'],
                'opening' => $opening, 'debit' => $debit, 'credit' => $credit, 'closing' => $closing,
                'opening_side' => self::side($opening), 'closing_side' => self::side($closing),
            ];
        }
        return $out;
    }

    /**
     * 明細分類帳：指定科目在期間內逐筆列出，並帶出累計餘額。
     * 未指定科目時，回傳所有有異動科目的明細（依科目分組）。
     */
    public function detail(string $from, string $to, ?int $acId = null): array
    {
        $accounts = $this->db->query("
            SELECT a.ac_id, a.ac_code, a.ac_name, a.ac_category
            FROM accounts a
            WHERE EXISTS (
                SELECT 1 FROM journal_entries e
                JOIN journal_vouchers v ON v.jv_id = e.je_jv_id
                WHERE e.je_ac_id = a.ac_id AND v.jv_date >= ? AND v.jv_date <= ?
            )" . ($acId ? ' AND a.ac_id = ?' : '') . '
            ORDER BY a.ac_code',
            $acId ? [$from, $to, $acId] : [$from, $to]
        )->getResultArray();

        $out = [];
        foreach ($accounts as $a) {
            // 期初：期間開始前的累計
            $op = $this->db->query("
                SELECT COALESCE(SUM(e.je_debit - e.je_credit), 0) bal
                FROM journal_entries e
                JOIN journal_vouchers v ON v.jv_id = e.je_jv_id
                WHERE e.je_ac_id = ? AND v.jv_date < ?
            ", [$a['ac_id'], $from])->getRow();
            $balance = (int) $op->bal;

            $lines = $this->db->query("
                SELECT v.jv_date, v.jv_no, v.jv_summary, e.je_summary, e.je_debit, e.je_credit
                FROM journal_entries e
                JOIN journal_vouchers v ON v.jv_id = e.je_jv_id
                WHERE e.je_ac_id = ? AND v.jv_date >= ? AND v.jv_date <= ?
                ORDER BY v.jv_date, v.jv_id, e.je_sort, e.je_id
            ", [$a['ac_id'], $from, $to])->getResultArray();

            $rows = [];
            $sumD = 0; $sumC = 0;
            foreach ($lines as $l) {
                $d = (int) $l['je_debit']; $c = (int) $l['je_credit'];
                $balance += $d - $c;
                $sumD += $d; $sumC += $c;
                $rows[] = $l + ['balance' => $balance, 'side' => self::side($balance)];
            }

            $out[] = [
                'account' => $a,
                'opening' => (int) $op->bal, 'opening_side' => self::side((int) $op->bal),
                'rows' => $rows,
                'debit' => $sumD, 'credit' => $sumC,
                'closing' => $balance, 'closing_side' => self::side($balance),
            ];
        }
        return $out;
    }

    /** 借餘為正、貸餘為負；回傳顯示用的方向字 */
    public static function side(int $balance): string
    {
        if ($balance > 0) return '借';
        if ($balance < 0) return '貸';
        return '平';
    }
}
