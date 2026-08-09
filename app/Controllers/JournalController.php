<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\JournalVoucherModel;
use App\Models\JournalEntryModel;
use App\Models\AccountModel;

class JournalController extends BaseController
{
    private $jvModel;
    private $jeModel;
    private $accountModel;

    public function __construct()
    {
        $this->jvModel = new JournalVoucherModel();
        $this->jeModel = new JournalEntryModel();
        $this->accountModel = new AccountModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->jvModel->getList($keyword, $page);
        return view('journal/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('journal/form', [
            'isEdit' => false,
            'accounts' => $this->accountModel->getAllForDropdown(),
            'types' => JournalVoucherModel::TYPES,
            // 只是預覽 —— 實際編號在存檔時依最終日期原子取號，避免開了表單沒存就佔掉號碼
            'defaultNo' => \App\Libraries\DocumentNumber::preview('JV'),
        ]);
    }

    /**
     * AJAX：預覽某個日期的下一個傳票編號（不佔號）。
     * 傳票編號含日期，使用者改日期時畫面要跟著更新，否則編號與日期會對不起來。
     */
    public function nextNo()
    {
        $date = $this->request->getGet('date') ?: date('Y-m-d');
        if (! strtotime($date)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => '日期格式不正確']);
        }

        return $this->response->setJSON(['no' => \App\Libraries\DocumentNumber::preview('JV', $date)]);
    }

    public function edit($id)
    {
        $jv = $this->jvModel->getWithEntries($id);
        if (!$jv) return redirect()->to('/journal')->with('error', '傳票不存在');
        return view('journal/form', [
            'isEdit' => true,
            'data' => $jv,
            'accounts' => $this->accountModel->getAllForDropdown(),
            'types' => JournalVoucherModel::TYPES,
        ]);
    }

    public function view($id)
    {
        $jv = $this->jvModel->getWithEntries($id);
        if (!$jv) return redirect()->to('/journal')->with('error', '傳票不存在');
        return view('journal/view', ['jv' => $jv]);
    }

    public function save()
    {
        $post = $this->request->getPost();
        $entries = $post['entries'] ?? [];

        // 計算借貸方合計並驗證平衡
        $sumDebit = 0; $sumCredit = 0; $valid = [];
        foreach ($entries as $e) {
            $acId = (int) ($e['je_ac_id'] ?? 0);
            $debit = (int) ($e['je_debit'] ?? 0);
            $credit = (int) ($e['je_credit'] ?? 0);
            if (!$acId || ($debit === 0 && $credit === 0)) continue;
            $sumDebit += $debit; $sumCredit += $credit;
            $valid[] = ['je_ac_id' => $acId, 'je_debit' => $debit, 'je_credit' => $credit, 'je_summary' => $e['je_summary'] ?? null];
        }

        if (count($valid) < 2) {
            return redirect()->back()->withInput()->with('error', '分錄至少需兩筆（一借一貸）');
        }
        if ($sumDebit !== $sumCredit) {
            return redirect()->back()->withInput()->with('error', "借貸不平衡：借方 " . number_format($sumDebit) . " ≠ 貸方 " . number_format($sumCredit));
        }
        if ($sumDebit === 0) {
            return redirect()->back()->withInput()->with('error', '金額不可為 0');
        }

        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $jvData = [
                'jv_date' => $post['jv_date'] ?: date('Y-m-d'),
                'jv_type' => $post['jv_type'] ?? '轉帳',
                'jv_segment' => $post['jv_segment'] ?? 'M-0',
                'jv_summary' => $post['jv_summary'] ?? null,
                'jv_amount' => $sumDebit,
                'jv_note' => $post['jv_note'] ?? null,
            ];
            $jvId = $post['jv_id'] ?? null;
            if ($jvId) {
                $this->jvModel->update($jvId, $jvData);
                $this->jeModel->deleteByVoucher($jvId);
            } else {
                // 編號一律在此依「最終日期」原子取號，不採用表單送來的值 ——
                // 表單上的只是預覽，使用者改過日期、或別人同時存檔，都以這裡為準
                $jvData['jv_no'] = $this->jvModel->generateNo($jvData['jv_date']);
                $jvId = $this->jvModel->insert($jvData);
            }
            $sort = 0;
            foreach ($valid as $v) {
                $v['je_jv_id'] = $jvId;
                $v['je_sort'] = $sort += 10;
                $this->jeModel->insert($v);
            }

            // 同步產生收付交易 —— 四階損益分析與資金餘額表讀的是它，不是傳票。
            // 少了這步，傳票存好了、那兩張報表卻不會有任何變化。
            $glCount = (new \App\Libraries\JournalGlPoster())->sync((int) $jvId);

            $db->transComplete();
            if ($db->transStatus() === false) return redirect()->back()->withInput()->with('error', '儲存失敗，已回復');

            $msg = '分錄傳票儲存成功（借貸平衡 ' . number_format($sumDebit) . '）';
            $msg .= $glCount > 0
                ? "，並同步 {$glCount} 筆收付交易（四階損益／資金餘額表已更新）"
                : '；本張只有資產負債科目的異動，不影響損益報表';

            return redirect()->to('/journal')->with('success', $msg);
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', '儲存失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!$this->jvModel->find($id)) return redirect()->to('/journal')->with('error', '傳票不存在');
        $db = \Config\Database::connect();
        $db->transStart();
        // 一併回收這張傳票產生的收付交易，否則傳票刪了、四階損益還留著那筆數字
        $glCount = (new \App\Libraries\JournalGlPoster())->remove((int) $id);
        $this->jeModel->deleteByVoucher($id);
        $this->jvModel->delete($id);
        $db->transComplete();

        $msg = '分錄傳票刪除成功';
        if ($glCount > 0) $msg .= "，同時回收 {$glCount} 筆收付交易";

        return redirect()->to('/journal')->with('success', $msg);
    }
}
