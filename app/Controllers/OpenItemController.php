<?php

namespace App\Controllers;

use App\Controllers\BaseController;

/**
 * 立沖帳(開放項目):
 *  - 立沖帳作業:對「需立沖帳」科目,逐筆挑借方項目與貸方項目互相沖銷。
 *  - 立沖帳餘額表:指定期間,列出立沖帳科目的未沖銷餘額(未沖 = 借或貸金額 − 已沖)。
 * 每筆分錄的未沖餘額 = (je_debit + je_credit) − je_offset。
 */
class OpenItemController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /** 立沖帳科目清單 + 借貸未沖合計 */
    private function openAccounts(): array
    {
        return $this->db->table('accounts a')
            ->select('a.ac_id, a.ac_code, a.ac_name,
                SUM(CASE WHEN je.je_debit>0 THEN je.je_debit + je.je_credit - je.je_offset ELSE 0 END) as debit_open,
                SUM(CASE WHEN je.je_credit>0 THEN je.je_debit + je.je_credit - je.je_offset ELSE 0 END) as credit_open,
                COUNT(je.je_id) as cnt', false)
            ->join('journal_entries je', 'je.je_ac_id = a.ac_id', 'left')
            ->where('a.ac_open_item', 1)
            ->groupBy('a.ac_id')->orderBy('a.ac_code', 'ASC')->get()->getResultArray();
    }

    // ===== 立沖帳餘額表 =====
    public function balance()
    {
        $from = $this->request->getGet('from') ?: '';
        $to = $this->request->getGet('to') ?: '';

        $b = $this->db->table('journal_entries je')
            ->select('je.je_id, je.je_debit, je.je_credit, je.je_offset, je.je_summary,
                      v.jv_no, v.jv_date, v.jv_summary as v_summary, a.ac_id, a.ac_code, a.ac_name,
                      (je.je_debit + je.je_credit - je.je_offset) as open_amt', false)
            ->join('journal_vouchers v', 'v.jv_id = je.je_jv_id', 'left')
            ->join('accounts a', 'a.ac_id = je.je_ac_id', 'left')
            ->where('a.ac_open_item', 1)
            ->where('(je.je_debit + je.je_credit - je.je_offset) >', 0);
        if ($from) $b->where('v.jv_date >=', $from);
        if ($to) $b->where('v.jv_date <=', $to);
        $rows = $b->orderBy('a.ac_code', 'ASC')->orderBy('v.jv_date', 'ASC')->get()->getResultArray();

        // 依科目分組
        $byAcct = [];
        foreach ($rows as $r) {
            $byAcct[$r['ac_id']]['acct'] = ['code' => $r['ac_code'], 'name' => $r['ac_name']];
            $byAcct[$r['ac_id']]['items'][] = $r;
        }

        return view('open_item/balance', [
            'byAcct' => $byAcct, 'from' => $from, 'to' => $to,
        ]);
    }

    // ===== 立沖帳作業 =====
    public function match()
    {
        return view('open_item/match', ['accounts' => $this->openAccounts()]);
    }

    public function account($acId)
    {
        $acct = $this->db->table('accounts')->where('ac_id', $acId)->where('ac_open_item', 1)->get()->getRowArray();
        if (!$acct) return redirect()->to('/open-item/match')->with('error', '此科目非立沖帳科目');

        $items = $this->db->table('journal_entries je')
            ->select('je.je_id, je.je_debit, je.je_credit, je.je_offset, je.je_summary,
                      v.jv_no, v.jv_date, (je.je_debit + je.je_credit - je.je_offset) as open_amt', false)
            ->join('journal_vouchers v', 'v.jv_id = je.je_jv_id', 'left')
            ->where('je.je_ac_id', $acId)
            ->where('(je.je_debit + je.je_credit - je.je_offset) >', 0)
            ->orderBy('v.jv_date', 'ASC')->get()->getResultArray();

        $debits = array_values(array_filter($items, fn($i) => (int) $i['je_debit'] > 0));
        $credits = array_values(array_filter($items, fn($i) => (int) $i['je_credit'] > 0));

        return view('open_item/account', ['acct' => $acct, 'debits' => $debits, 'credits' => $credits]);
    }

    public function doOffset($acId)
    {
        $debitIds = $this->request->getPost('debit') ?? [];
        $creditIds = $this->request->getPost('credit') ?? [];
        if (empty($debitIds) || empty($creditIds)) {
            return redirect()->back()->with('error', '請至少各勾選一筆借方與貸方項目');
        }

        $debitOpen = $this->sumOpen($debitIds);
        $creditOpen = $this->sumOpen($creditIds);
        $offsetable = min($debitOpen, $creditOpen);
        if ($offsetable <= 0) return redirect()->back()->with('error', '所選項目無可沖銷金額');

        // 沖銷日期＝這次收付實際發生的日期；收付制報表要靠它認列到正確的月份
        $offsetDate = $this->request->getPost('offset_date') ?: date('Y-m-d');

        $this->db->transStart();
        try {
            $this->distribute($debitIds, $offsetable, $offsetDate);
            $this->distribute($creditIds, $offsetable, $offsetDate);

            // 應收/應付沖銷完之後，對應的收付交易要從「未收付」翻成「已收付」——
            // 否則賒銷的錢收到了，資金餘額表卻永遠看不到這筆現金流入
            $synced = $this->syncSettlement(array_merge($debitIds, $creditIds));

            $this->db->transComplete();
            if ($this->db->transStatus() === false) return redirect()->back()->with('error', '沖銷失敗,已回復');

            $msg = '已沖銷 ' . number_format($offsetable);
            if ($synced > 0) $msg .= "，並更新 {$synced} 筆收付交易為已收付（資金餘額表已反映）";

            return redirect()->to('/open-item/account/' . $acId)->with('success', $msg);
        } catch (\Exception $e) {
            $this->db->transRollback();
            return redirect()->back()->with('error', '沖銷失敗:' . $e->getMessage());
        }
    }

    private function sumOpen(array $ids): int
    {
        $sum = 0;
        foreach ($ids as $id) {
            $e = $this->db->table('journal_entries')->where('je_id', (int) $id)->get()->getRowArray();
            if ($e) $sum += (int) $e['je_debit'] + (int) $e['je_credit'] - (int) $e['je_offset'];
        }
        return $sum;
    }

    private function distribute(array $ids, int $amount, string $offsetDate): void
    {
        foreach ($ids as $id) {
            if ($amount <= 0) break;
            $e = $this->db->table('journal_entries')->where('je_id', (int) $id)->get()->getRowArray();
            if (!$e) continue;
            $open = (int) $e['je_debit'] + (int) $e['je_credit'] - (int) $e['je_offset'];
            if ($open <= 0) continue;
            $take = min($open, $amount);
            $this->db->table('journal_entries')->where('je_id', (int) $id)
                ->update([
                    'je_offset' => (int) $e['je_offset'] + $take,
                    'je_offset_date' => $offsetDate,
                ]);
            $amount -= $take;
        }
    }

    /**
     * 重新推導受影響傳票的收付交易（沖銷會改變收付狀態與收付日期）。
     *
     * @return int 有幾筆收付交易因此變成已收付
     */
    private function syncSettlement(array $entryIds): int
    {
        $entryIds = array_map('intval', array_filter($entryIds));
        if ($entryIds === []) return 0;

        $jvIds = array_unique(array_column(
            $this->db->table('journal_entries')->select('je_jv_id')->whereIn('je_id', $entryIds)->get()->getResultArray(),
            'je_jv_id'
        ));

        $poster = new \App\Libraries\JournalGlPoster();
        $settled = 0;

        foreach ($jvIds as $jvId) {
            $poster->sync((int) $jvId);
            $settled += (int) $this->db->table('gl_transactions')
                ->where('t_jv_id', (int) $jvId)
                ->where('t_settle_status', '已收付')
                ->countAllResults();
        }

        return $settled;
    }
}
