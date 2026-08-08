<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\JournalVoucherModel;
use App\Models\JournalEntryModel;
use App\Models\AccountModel;

/**
 * 自動分錄：依標準分錄範本，將營運單據（訂單/採購單/收付款）一鍵產生借貸傳票。
 * 範本：
 *   訂單   借 應收帳款 / 貸 營-銷貨收入
 *   採購單 借 存貨     / 貸 應付帳款
 *   收款   借 現金     / 貸 應收帳款
 *   付款   借 應付帳款 / 貸 現金
 */
class AutoJournalController extends BaseController
{
    private $jvModel;
    private $jeModel;
    private $accountModel;
    private $db;

    // [來源別 => [借方科目名, 貸方科目名, 摘要前綴]]
    private const TEMPLATES = [
        'order'   => ['應收帳款', '營-銷貨收入', '銷貨'],
        'po'      => ['存貨', '應付帳款', '進貨'],
        'receipt' => ['現金', '應收帳款', '收款'],
        'payment' => ['應付帳款', '現金', '付款'],
    ];


    public function __construct()
    {
        $this->jvModel = new JournalVoucherModel();
        $this->jeModel = new JournalEntryModel();
        $this->accountModel = new AccountModel();
        $this->db = \Config\Database::connect();
    }

    private function acctId(string $name): ?int
    {
        $r = $this->accountModel->where('ac_name', $name)->first();
        return $r ? (int) $r['ac_id'] : null;
    }

    private function postedSet(): array
    {
        $rows = $this->db->table('journal_vouchers')
            ->select('jv_source_type, jv_source_id')->where('jv_source_type IS NOT NULL')->get()->getResultArray();
        $set = [];
        foreach ($rows as $r) $set[$r['jv_source_type'] . ':' . $r['jv_source_id']] = true;
        return $set;
    }

    public function index()
    {
        $posted = $this->postedSet();

        $orders = $this->db->table('orders o')->select('o.o_id, o.o_number, o.o_date, o.o_total_amount, c.c_name')
            ->join('customers c', 'c.c_id = o.o_c_id', 'left')
            ->where('o.o_status !=', 'cancelled')->where('o.o_total_amount >', 0)
            ->orderBy('o.o_date', 'DESC')->limit(30)->get()->getResultArray();

        $pos = $this->db->table('purchase_orders po')->select('po.po_id, po.po_no, po.po_date, po.po_total, s.s_name')
            ->join('suppliers s', 's.s_id = po.po_s_id', 'left')
            ->where('po.po_status', '已結案')->where('po.po_total >', 0)
            ->orderBy('po.po_date', 'DESC')->limit(30)->get()->getResultArray();

        $sts = $this->db->table('settlements')->select('st_id, st_no, st_date, st_direction, st_amount, st_partner, st_ref_no')
            ->orderBy('st_date', 'DESC')->limit(30)->get()->getResultArray();

        return view('auto_journal/index', [
            'orders' => $orders, 'pos' => $pos, 'settlements' => $sts,
            'posted' => $posted,
            'templates' => self::TEMPLATES,
            'glStat' => (new \App\Libraries\GlJournalPoster())->stat(),
        ]);
    }

    // ===================== 交易登錄（收付制）→ 借貸傳票 =====================
    // 實作在 App\Libraries\GlJournalPoster（CLI 的 erp:post-gl 也共用同一份邏輯）

    /** 一鍵把「交易登錄（收付）」全部過帳成借貸傳票 */
    public function generateGlAll()
    {
        $r = (new \App\Libraries\GlJournalPoster())->postAll();
        if ($r['error']) return redirect()->to('/auto-journal')->with('error', $r['error']);

        $msg = "已將 {$r['ok']} 筆收付交易過帳為借貸傳票"
             . ($r['skipped'] ? "（略過 {$r['skipped']} 筆金額為 0 或無科目）" : '');
        return redirect()->to('/auto-journal')->with('success', $msg);
    }

    /** 清除由收付交易自動產生的傳票（不影響手動傳票與其他來源） */
    public function clearGl()
    {
        $n = (new \App\Libraries\GlJournalPoster())->clear();
        if ($n === 0) return redirect()->to('/auto-journal')->with('error', '沒有可清除的自動傳票');
        return redirect()->to('/auto-journal')->with('success', "已清除 {$n} 張由收付交易產生的傳票");
    }

    public function generate($type, $id)
    {
        if (!isset(self::TEMPLATES[$type])) return redirect()->to('/auto-journal')->with('error', '未知單別');

        // 防重複
        $exists = $this->db->table('journal_vouchers')->where('jv_source_type', $type)->where('jv_source_id', $id)->countAllResults();
        if ($exists > 0) return redirect()->to('/auto-journal')->with('error', '此單據已產生分錄，請勿重複過帳');

        // 取單據資料
        [$doc, $amount, $partner, $refNo, $date] = $this->loadDoc($type, (int) $id);
        if (!$doc) return redirect()->to('/auto-journal')->with('error', '單據不存在');
        if ($amount <= 0) return redirect()->to('/auto-journal')->with('error', '單據金額為 0，無法產生分錄');

        [$dName, $cName, $prefix] = self::TEMPLATES[$type];
        $dId = $this->acctId($dName); $cId = $this->acctId($cName);
        if (!$dId || !$cId) return redirect()->to('/auto-journal')->with('error', "缺少會計科目：{$dName} 或 {$cName}，請先於會計科目設定建立");

        $this->db->transStart();
        try {
            $jvId = $this->jvModel->insert([
                'jv_no' => $this->jvModel->generateNo($date),
                'jv_date' => $date, 'jv_type' => '轉帳',
                'jv_source_type' => $type, 'jv_source_id' => (int) $id,
                'jv_summary' => "{$prefix} {$refNo}" . ($partner ? " - {$partner}" : ''),
                'jv_amount' => $amount,
            ]);
            $this->jeModel->insert(['je_jv_id' => $jvId, 'je_ac_id' => $dId, 'je_debit' => $amount, 'je_credit' => 0, 'je_sort' => 10, 'je_summary' => $dName]);
            $this->jeModel->insert(['je_jv_id' => $jvId, 'je_ac_id' => $cId, 'je_debit' => 0, 'je_credit' => $amount, 'je_sort' => 20, 'je_summary' => $cName]);
            $this->db->transComplete();
            if ($this->db->transStatus() === false) return redirect()->to('/auto-journal')->with('error', '產生失敗，已回復');
            return redirect()->to('/auto-journal')->with('success', "已產生分錄：借 {$dName} / 貸 {$cName} " . number_format($amount));
        } catch (\Exception $e) {
            $this->db->transRollback();
            return redirect()->to('/auto-journal')->with('error', '產生失敗：' . $e->getMessage());
        }
    }

    /** 回傳 [doc, amount, partner, refNo, date] */
    private function loadDoc(string $type, int $id): array
    {
        if ($type === 'order') {
            $d = $this->db->table('orders o')->select('o.*, c.c_name')->join('customers c', 'c.c_id=o.o_c_id', 'left')->where('o.o_id', $id)->get()->getRowArray();
            return $d ? [$d, (int) round((float) $d['o_total_amount']), $d['c_name'] ?? null, $d['o_number'], $d['o_date']] : [null, 0, null, null, null];
        }
        if ($type === 'po') {
            $d = $this->db->table('purchase_orders po')->select('po.*, s.s_name')->join('suppliers s', 's.s_id=po.po_s_id', 'left')->where('po.po_id', $id)->get()->getRowArray();
            return $d ? [$d, (int) $d['po_total'], $d['s_name'] ?? null, $d['po_no'], $d['po_date']] : [null, 0, null, null, null];
        }
        // receipt / payment 皆來自 settlements
        $d = $this->db->table('settlements')->where('st_id', $id)->get()->getRowArray();
        if (!$d) return [null, 0, null, null, null];
        // 型別需與方向相符
        $wantDir = $type === 'receipt' ? '收' : '付';
        if ($d['st_direction'] !== $wantDir) return [null, 0, null, null, null];
        return [$d, (int) $d['st_amount'], $d['st_partner'] ?? null, $d['st_ref_no'], $d['st_date']];
    }
}
