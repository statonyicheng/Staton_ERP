<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SupplierModel;
use App\Models\PaymentMethodModel;

class SupplierController extends BaseController
{
    private $supplierModel;
    private $paymentMethodModel;

    public function __construct()
    {
        $this->supplierModel = new SupplierModel();
        $this->paymentMethodModel = new PaymentMethodModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->supplierModel->getList($keyword, $page);

        return view('supplier/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('supplier/form', [
            'isEdit' => false,
            'paymentMethods' => $this->paymentMethodModel->getAllForDropdown(),
        ]);
    }

    public function store()
    {
        if (!$this->validate($this->supplierModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $data = $this->request->getPost();
            $data['s_code'] = $data['s_code'] ?: $this->supplierModel->generateCode();
            $this->supplierModel->insert($data);
            return redirect()->to('/supplier')->with('success', '廠商新增成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '新增失敗：' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $data = $this->supplierModel->find($id);
        if (!$data) return redirect()->to('/supplier')->with('error', '廠商不存在');

        return view('supplier/form', [
            'isEdit' => true,
            'data' => $data,
            'paymentMethods' => $this->paymentMethodModel->getAllForDropdown(),
        ]);
    }

    public function update($id)
    {
        if (!$this->supplierModel->find($id)) return redirect()->to('/supplier')->with('error', '廠商不存在');
        if (!$this->validate($this->supplierModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $this->supplierModel->update($id, $this->request->getPost());
            return redirect()->to('/supplier')->with('success', '廠商更新成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '更新失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!$this->supplierModel->find($id)) return redirect()->to('/supplier')->with('error', '廠商不存在');
        try {
            $this->supplierModel->delete($id);
            return redirect()->to('/supplier')->with('success', '廠商刪除成功');
        } catch (\Exception $e) {
            return redirect()->to('/supplier')->with('error', '刪除失敗：' . $e->getMessage());
        }
    }
}
