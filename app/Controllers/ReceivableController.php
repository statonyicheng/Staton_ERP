<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ReceivableModel;
use App\Models\SettlementModel;
use App\Models\CustomerModel;

class ReceivableController extends BaseController
{
    private $arModel;
    private $stModel;
    private $customerModel;

    public function __construct()
    {
        $this->arModel = new ReceivableModel();
        $this->stModel = new SettlementModel();
        $this->customerModel = new CustomerModel();
    }

    private function customers(): array
    {
        return $this->customerModel->select('c_id, c_code, c_name')->orderBy('c_name', 'ASC')->findAll();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $status = $this->request->getGet('status');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->arModel->getList($keyword, $page, $status);

        return view('receivable/index', [
            'data' => $data['data'],
            'keyword' => $keyword, 'status' => $status,
            'statuses' => ReceivableModel::STATUSES,
            'summary' => $this->arModel->summary(),
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('receivable/form', [
            'isEdit' => false,
            'customers' => $this->customers(),
            'defaultNo' => $this->arModel->generateNo(),
        ]);
    }

    public function store()
    {
        try {
            $d = $this->request->getPost();
            $this->arModel->insert([
                'ar_no' => $d['ar_no'] ?: $this->arModel->generateNo($d['ar_date'] ?? null),
                'ar_c_id' => $d['ar_c_id'] ?: null,
                'ar_date' => $d['ar_date'] ?: date('Y-m-d'),
                'ar_source' => '手動',
                'ar_amount' => (int) ($d['ar_amount'] ?? 0),
                'ar_due_date' => $d['ar_due_date'] ?: null,
                'ar_status' => '未收款',
                'ar_note' => $d['ar_note'] ?? null,
            ]);
            return redirect()->to('/receivable')->with('success', '應收憑單新增成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '新增失敗：' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $data = $this->arModel->find($id);
        if (!$data) return redirect()->to('/receivable')->with('error', '應收憑單不存在');
        return view('receivable/form', ['isEdit' => true, 'data' => $data, 'customers' => $this->customers()]);
    }

    public function update($id)
    {
        $ar = $this->arModel->find($id);
        if (!$ar) return redirect()->to('/receivable')->with('error', '應收憑單不存在');
        try {
            $d = $this->request->getPost();
            $amount = (int) ($d['ar_amount'] ?? 0);
            $rec = (int) $ar['ar_received'];
            $status = $rec <= 0 ? '未收款' : ($rec >= $amount ? '已收款' : '部分收款');
            $this->arModel->update($id, [
                'ar_c_id' => $d['ar_c_id'] ?: null,
                'ar_date' => $d['ar_date'] ?: date('Y-m-d'),
                'ar_amount' => $amount,
                'ar_due_date' => $d['ar_due_date'] ?: null,
                'ar_status' => $status,
                'ar_note' => $d['ar_note'] ?? null,
            ]);
            return redirect()->to('/receivable')->with('success', '應收憑單更新成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '更新失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!$this->arModel->find($id)) return redirect()->to('/receivable')->with('error', '應收憑單不存在');
        $this->arModel->delete($id);
        return redirect()->to('/receivable')->with('success', '應收憑單刪除成功');
    }

    public function generate()
    {
        $n = $this->arModel->generateFromOrders();
        return redirect()->to('/receivable')->with('success', "已從訂單產生 {$n} 筆應收憑單");
    }

    public function receive($id)
    {
        $ar = $this->arModel->find($id);
        if (!$ar) return redirect()->to('/receivable')->with('error', '應收憑單不存在');
        $ar['c_name'] = $ar['ar_c_id'] ? ($this->customerModel->find($ar['ar_c_id'])['c_name'] ?? '') : '';
        return view('receivable/receive', ['ar' => $ar, 'methods' => SettlementModel::METHODS]);
    }

    public function doReceive($id)
    {
        $ar = $this->arModel->find($id);
        if (!$ar) return redirect()->to('/receivable')->with('error', '應收憑單不存在');
        $amount = (int) $this->request->getPost('amount');
        $remain = (int) $ar['ar_amount'] - (int) $ar['ar_received'];
        if ($amount <= 0 || $amount > $remain) {
            return redirect()->back()->with('error', "收款金額須介於 1 ～ {$remain}");
        }
        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $partner = $ar['ar_c_id'] ? ($this->customerModel->find($ar['ar_c_id'])['c_name'] ?? null) : null;
            $this->stModel->record([
                'st_date' => $this->request->getPost('date') ?: date('Y-m-d'),
                'st_direction' => '收', 'st_target' => '應收', 'st_target_id' => (int) $id,
                'st_ref_no' => $ar['ar_no'], 'st_partner' => $partner, 'st_amount' => $amount,
                'st_method' => $this->request->getPost('method') ?: '現金',
                'st_note' => $this->request->getPost('note'),
            ]);
            $this->arModel->applyReceipt((int) $id, $amount);
            $db->transComplete();
            if ($db->transStatus() === false) return redirect()->back()->with('error', '收款失敗，已回復');
            return redirect()->to('/receivable')->with('success', '收款已登錄並更新應收狀態');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', '收款失敗：' . $e->getMessage());
        }
    }
}
