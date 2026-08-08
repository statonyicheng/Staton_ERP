<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PurchaseRequisitionModel;

class PurchaseRequisitionController extends BaseController
{
    private $prModel;

    public function __construct()
    {
        $this->prModel = new PurchaseRequisitionModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $status = $this->request->getGet('status');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->prModel->getList($keyword, $page, $status);

        return view('purchase_requisition/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'status' => $status,
            'statuses' => PurchaseRequisitionModel::STATUSES,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('purchase_requisition/form', [
            'isEdit' => false,
            'statuses' => PurchaseRequisitionModel::STATUSES,
            'defaultNo' => $this->prModel->generateNo(),
        ]);
    }

    public function store()
    {
        if (!$this->validate($this->prModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $data = $this->request->getPost();
            $data['pr_no'] = $data['pr_no'] ?: $this->prModel->generateNo($data['pr_date'] ?? null);
            $this->prModel->insert($data);
            return redirect()->to('/purchase-requisition')->with('success', '請購單新增成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '新增失敗：' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $data = $this->prModel->find($id);
        if (!$data) return redirect()->to('/purchase-requisition')->with('error', '請購單不存在');
        return view('purchase_requisition/form', [
            'isEdit' => true,
            'data' => $data,
            'statuses' => PurchaseRequisitionModel::STATUSES,
        ]);
    }

    public function update($id)
    {
        // 樂觀鎖：這筆資料若在使用者編輯期間被別人改過，就擋下來，不要無聲覆蓋
        if ($msg = \App\Libraries\EditGuard::check('purchase_requisitions', 'pr_id', $id, 'pr_updated_at', $this->request->getPost(\App\Libraries\EditGuard::FIELD))) {
            return redirect()->back()->withInput()->with('error', $msg);
        }
        if (!$this->prModel->find($id)) return redirect()->to('/purchase-requisition')->with('error', '請購單不存在');
        if (!$this->validate($this->prModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $this->prModel->update($id, $this->request->getPost());
            return redirect()->to('/purchase-requisition')->with('success', '請購單更新成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '更新失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!$this->prModel->find($id)) return redirect()->to('/purchase-requisition')->with('error', '請購單不存在');
        $this->prModel->delete($id);
        return redirect()->to('/purchase-requisition')->with('success', '請購單刪除成功');
    }
}
