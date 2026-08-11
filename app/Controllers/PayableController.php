<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PayableModel;
use App\Models\SettlementModel;
use App\Models\SupplierModel;

class PayableController extends BaseController
{
    private $apModel;
    private $stModel;
    private $supplierModel;

    public function __construct()
    {
        $this->apModel = new PayableModel();
        $this->stModel = new SettlementModel();
        $this->supplierModel = new SupplierModel();
    }

    /**
     * 應付帳款＝帳上被標記為「應付」的科目、尚未沖銷的分錄。
     *
     * 舊版讀的是獨立的 `payables` 表（只吃結案採購單與手動新增），跟分錄傳票毫無關聯，
     * 導致「借費用／貸應付薪資」的傳票明明記了帳，這個畫面卻是空的。
     */
    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $showAll = (bool) $this->request->getGet('all');

        $book = new \App\Libraries\ArApBook();

        return view('payable/index', [
            'items' => $book->items(\App\Libraries\ArApBook::AP, $keyword, ! $showAll),
            'summary' => $book->summary(\App\Libraries\ArApBook::AP),
            'accounts' => $book->accounts(\App\Libraries\ArApBook::AP),
            'keyword' => $keyword,
            'showAll' => $showAll,
        ]);
    }

    public function create()
    {
        return view('payable/form', [
            'isEdit' => false,
            'suppliers' => $this->supplierModel->getAllForDropdown(),
            'defaultNo' => $this->apModel->generateNo(),
        ]);
    }

    public function store()
    {
        try {
            $d = $this->request->getPost();
            $this->apModel->insert([
                'ap_no' => $d['ap_no'] ?: $this->apModel->generateNo($d['ap_date'] ?? null),
                'ap_s_id' => $d['ap_s_id'] ?: null,
                'ap_date' => $d['ap_date'] ?: date('Y-m-d'),
                'ap_source' => '手動',
                'ap_amount' => (int) ($d['ap_amount'] ?? 0),
                'ap_due_date' => $d['ap_due_date'] ?: null,
                'ap_status' => '未付款',
                'ap_note' => $d['ap_note'] ?? null,
            ]);
            return redirect()->to('/payable')->with('success', '應付憑單新增成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '新增失敗：' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $data = $this->apModel->find($id);
        if (!$data) return redirect()->to('/payable')->with('error', '應付憑單不存在');
        return view('payable/form', ['isEdit' => true, 'data' => $data, 'suppliers' => $this->supplierModel->getAllForDropdown()]);
    }

    public function update($id)
    {
        // 樂觀鎖：這筆資料若在使用者編輯期間被別人改過，就擋下來，不要無聲覆蓋
        if ($msg = \App\Libraries\EditGuard::check('payables', 'ap_id', $id, 'ap_updated_at', $this->request->getPost(\App\Libraries\EditGuard::FIELD))) {
            return redirect()->back()->withInput()->with('error', $msg);
        }
        $ap = $this->apModel->find($id);
        if (!$ap) return redirect()->to('/payable')->with('error', '應付憑單不存在');
        try {
            $d = $this->request->getPost();
            $amount = (int) ($d['ap_amount'] ?? 0);
            $paid = (int) $ap['ap_paid'];
            $status = $paid <= 0 ? '未付款' : ($paid >= $amount ? '已付款' : '部分付款');
            $this->apModel->update($id, [
                'ap_s_id' => $d['ap_s_id'] ?: null,
                'ap_date' => $d['ap_date'] ?: date('Y-m-d'),
                'ap_amount' => $amount,
                'ap_due_date' => $d['ap_due_date'] ?: null,
                'ap_status' => $status,
                'ap_note' => $d['ap_note'] ?? null,
            ]);
            return redirect()->to('/payable')->with('success', '應付憑單更新成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '更新失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!$this->apModel->find($id)) return redirect()->to('/payable')->with('error', '應付憑單不存在');
        $this->apModel->delete($id);
        return redirect()->to('/payable')->with('success', '應付憑單刪除成功');
    }

    public function generate()
    {
        $n = $this->apModel->generateFromClosedPOs();
        return redirect()->to('/payable')->with('success', "已從結案採購單產生 {$n} 筆應付憑單");
    }

    public function pay($id)
    {
        $ap = $this->apModel->find($id);
        if (!$ap) return redirect()->to('/payable')->with('error', '應付憑單不存在');
        $ap['s_name'] = $ap['ap_s_id'] ? ($this->supplierModel->find($ap['ap_s_id'])['s_name'] ?? '') : '';
        return view('payable/pay', ['ap' => $ap, 'methods' => SettlementModel::METHODS]);
    }

    public function doPay($id)
    {
        $ap = $this->apModel->find($id);
        if (!$ap) return redirect()->to('/payable')->with('error', '應付憑單不存在');
        $amount = (int) $this->request->getPost('amount');
        $remain = (int) $ap['ap_amount'] - (int) $ap['ap_paid'];
        if ($amount <= 0 || $amount > $remain) {
            return redirect()->back()->with('error', "付款金額須介於 1 ～ {$remain}");
        }
        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $partner = $ap['ap_s_id'] ? ($this->supplierModel->find($ap['ap_s_id'])['s_name'] ?? null) : null;
            $this->stModel->record([
                'st_date' => $this->request->getPost('date') ?: date('Y-m-d'),
                'st_direction' => '付', 'st_target' => '應付', 'st_target_id' => (int) $id,
                'st_ref_no' => $ap['ap_no'], 'st_partner' => $partner, 'st_amount' => $amount,
                'st_method' => $this->request->getPost('method') ?: '現金',
                'st_note' => $this->request->getPost('note'),
            ]);
            $this->apModel->applyPayment((int) $id, $amount);
            $db->transComplete();
            if ($db->transStatus() === false) return redirect()->back()->with('error', '付款失敗，已回復');
            return redirect()->to('/payable')->with('success', '付款已登錄並更新應付狀態');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', '付款失敗：' . $e->getMessage());
        }
    }
}
