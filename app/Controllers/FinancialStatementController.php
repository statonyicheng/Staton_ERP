<?php

namespace App\Controllers;

use App\Controllers\BaseController;

/**
 * 四大財務報表：由複式簿記分錄（journal_entries × accounts）自動編製。
 *   資產負債表、損益表、現金流量表、權益變動表。
 * 科目類別 ac_category：資產/負債/權益/收入/支出/非損益；ac_tier 供損益分階。
 */
class FinancialStatementController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /** 各科目在期間 [from,to] 的借貸合計（to 為 null 表不限；from 為 null 表自始） */
    private function balances(?string $from, ?string $to): array
    {
        $b = $this->db->table('journal_entries je')
            ->select('a.ac_id, a.ac_code, a.ac_name, a.ac_category, a.ac_tier, SUM(je.je_debit) d, SUM(je.je_credit) c')
            ->join('journal_vouchers v', 'v.jv_id = je.je_jv_id', 'left')
            ->join('accounts a', 'a.ac_id = je.je_ac_id', 'left');
        if ($from) $b->where('v.jv_date >=', $from);
        if ($to) $b->where('v.jv_date <=', $to);
        $rows = $b->groupBy('je.je_ac_id')->orderBy('a.ac_code', 'ASC')->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'code' => $r['ac_code'], 'name' => $r['ac_name'],
                'category' => $r['ac_category'], 'tier' => $r['ac_tier'],
                'debit' => (int) $r['d'], 'credit' => (int) $r['c'],
                'net' => (int) $r['d'] - (int) $r['c'], // 借正貸負
            ];
        }
        return $out;
    }

    private function year(): int
    {
        return (int) ($this->request->getGet('year') ?: date('Y'));
    }

    /** 本期損益（收入貸方 − 支出借方） */
    private function netIncome(?string $from, ?string $to): int
    {
        $rows = $this->balances($from, $to);
        $income = 0;
        foreach ($rows as $r) {
            if ($r['category'] === '收入') $income += -$r['net'];      // 收入為貸方
            elseif ($r['category'] === '支出') $income -= $r['net'];   // 支出為借方
        }
        return $income;
    }

    public function income()
    {
        return view('fs/income', $this->incomeData($this->year()));
    }

    /** 損益表資料（畫面與匯出共用） */
    public function incomeData(int $y): array
    {
        $from = "$y-01-01"; $to = "$y-12-31";
        $rows = $this->balances($from, $to);

        $revenue = []; $expenseByTier = []; $totRev = 0; $totExp = 0;
        foreach ($rows as $r) {
            if ($r['category'] === '收入') { $amt = -$r['net']; $revenue[] = ['name' => $r['name'], 'amt' => $amt]; $totRev += $amt; }
            elseif ($r['category'] === '支出') { $amt = $r['net']; $expenseByTier[$r['tier']][] = ['name' => $r['name'], 'amt' => $amt]; $totExp += $amt; }
        }
        return [
            'year' => $y, 'revenue' => $revenue, 'expenseByTier' => $expenseByTier,
            'totRev' => $totRev, 'totExp' => $totExp, 'net' => $totRev - $totExp,
        ];
    }

    public function balance()
    {
        return view('fs/balance', $this->balanceData($this->year()));
    }

    /** 資產負債表資料（畫面與匯出共用） */
    public function balanceData(int $y): array
    {
        $to = "$y-12-31";
        $rows = $this->balances(null, $to);
        $net = $this->netIncome("$y-01-01", $to);
        $priorRE = $this->netIncome(null, "$y-01-01") * -1; // 期初保留盈餘(累積至上年底的損益，貸方為正) → 以正值表示

        $assets = []; $liab = []; $equity = [];
        $tA = 0; $tL = 0; $tE = 0;
        foreach ($rows as $r) {
            if ($r['category'] === '資產') { $v = $r['net']; $assets[] = ['name' => $r['name'], 'amt' => $v]; $tA += $v; }
            elseif ($r['category'] === '負債') { $v = -$r['net']; $liab[] = ['name' => $r['name'], 'amt' => $v]; $tL += $v; }
            elseif ($r['category'] === '權益') { $v = -$r['net']; $equity[] = ['name' => $r['name'], 'amt' => $v]; $tE += $v; }
        }
        // 本期損益併入權益
        $equity[] = ['name' => '本期損益', 'amt' => $net];
        $tE += $net;

        return [
            'year' => $y, 'assets' => $assets, 'liab' => $liab, 'equity' => $equity,
            'tA' => $tA, 'tL' => $tL, 'tE' => $tE, 'balanced' => ($tA === $tL + $tE),
        ];
    }

    public function cashflow()
    {
        return view('fs/cashflow', $this->cashflowData($this->year()));
    }

    /** 現金流量表資料（畫面與匯出共用） */
    public function cashflowData(int $y): array
    {
        $from = "$y-01-01"; $to = "$y-12-31";

        // 現金類科目 id
        $cashIds = array_column($this->db->table('accounts')->select('ac_id')
            ->whereIn('ac_name', ['現金', '銀行存款'])->get()->getResultArray(), 'ac_id');
        if (empty($cashIds)) $cashIds = [0];

        // 逐張含現金的傳票，依「對方科目類別」歸類 營業/投資/籌資
        $vouchers = $this->db->table('journal_entries je')
            ->select('je.je_jv_id, je.je_ac_id, je.je_debit, je.je_credit, a.ac_category, a.ac_name')
            ->join('journal_vouchers v', 'v.jv_id = je.je_jv_id', 'left')
            ->join('accounts a', 'a.ac_id = je.je_ac_id', 'left')
            ->where('v.jv_date >=', $from)->where('v.jv_date <=', $to)
            ->get()->getResultArray();

        $byJv = [];
        foreach ($vouchers as $e) $byJv[$e['je_jv_id']][] = $e;

        $op = 0; $inv = 0; $fin = 0; $netCash = 0;
        foreach ($byJv as $entries) {
            // 現金淨變動
            $cashDelta = 0; $counterCats = [];
            foreach ($entries as $e) {
                if (in_array($e['je_ac_id'], $cashIds)) $cashDelta += (int) $e['je_debit'] - (int) $e['je_credit'];
                else $counterCats[] = $e['ac_category'];
            }
            if ($cashDelta === 0) continue;
            $netCash += $cashDelta;
            // 依對方科目類別歸類
            $cat = $counterCats[0] ?? '';
            if (in_array($cat, ['資產']) && !empty(array_intersect($counterCats, ['資產']))) {
                // 對方為固定資產類 → 投資；其餘資產(應收/存貨)→營業
                $inv += $cashDelta; // 簡化：資產類歸投資
            }
            if (in_array($cat, ['收入', '支出'])) $op += $cashDelta;
            elseif ($cat === '權益' || ($cat === '負債' && $this->isFinancing($counterCats))) $fin += $cashDelta;
            elseif ($cat === '負債') $op += $cashDelta;
            elseif ($cat === '資產') { /* handled above as inv */ }
            else $op += $cashDelta;
        }

        $openCash = $this->cashBalance(null, "$y-01-01", $cashIds);
        return [
            'year' => $y, 'op' => $op, 'inv' => $inv, 'fin' => $fin,
            'netCash' => $netCash, 'openCash' => $openCash, 'closeCash' => $openCash + $netCash,
        ];
    }

    private function isFinancing(array $cats): bool
    {
        return in_array('權益', $cats, true);
    }

    private function cashBalance(?string $from, ?string $to, array $cashIds): int
    {
        $b = $this->db->table('journal_entries je')
            ->select('SUM(je.je_debit) d, SUM(je.je_credit) c')
            ->join('journal_vouchers v', 'v.jv_id = je.je_jv_id', 'left')
            ->whereIn('je.je_ac_id', $cashIds);
        if ($from) $b->where('v.jv_date >=', $from);
        if ($to) $b->where('v.jv_date <', $to);
        $r = $b->get()->getRowArray();
        return (int) ($r['d'] ?? 0) - (int) ($r['c'] ?? 0);
    }

    public function equity()
    {
        return view('fs/equity', $this->equityData($this->year()));
    }

    /** 權益變動表資料（畫面與匯出共用） */
    public function equityData(int $y): array
    {
        $priorNet = $this->netIncome(null, "$y-01-01");     // 累積至期初的損益(權益增加為正時 netIncome 已為正)
        $curNet = $this->netIncome("$y-01-01", "$y-12-31");

        // 股本等權益科目（不含本期損益）
        $capOpen = $this->equityCapital(null, "$y-01-01");
        $capChange = $this->equityCapital("$y-01-01", "$y-12-31");

        return [
            'year' => $y,
            'capOpen' => $capOpen, 'capChange' => $capChange, 'capClose' => $capOpen + $capChange,
            'reOpen' => $priorNet, 'curNet' => $curNet, 'reClose' => $priorNet + $curNet,
        ];
    }

    /** 權益類科目(排除本期損益概念)貸方淨額 */
    private function equityCapital(?string $from, ?string $to): int
    {
        $b = $this->db->table('journal_entries je')
            ->select('SUM(je.je_credit) c, SUM(je.je_debit) d')
            ->join('journal_vouchers v', 'v.jv_id = je.je_jv_id', 'left')
            ->join('accounts a', 'a.ac_id = je.je_ac_id', 'left')
            ->where('a.ac_category', '權益');
        if ($from) $b->where('v.jv_date >=', $from);
        if ($to) $b->where('v.jv_date <', $to);
        $r = $b->get()->getRowArray();
        return (int) ($r['c'] ?? 0) - (int) ($r['d'] ?? 0);
    }
}
