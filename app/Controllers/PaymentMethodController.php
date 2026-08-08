<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PaymentMethodModel;

class PaymentMethodController extends BaseController
{
    private $paymentMethodModel;

    public function __construct()
    {
        $this->paymentMethodModel = new PaymentMethodModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = $this->request->getGet('page') ?: 1;

        $data = $this->paymentMethodModel->getList($keyword, $page);
        $pagerData = [
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages']
        ];

        return view('payment_method/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'pager' => $pagerData
        ]);
    }

    public function create()
    {
        return view('payment_method/form', [
            'isEdit' => false,
        ]);
    }

    public function store()
    {
        // 驗證資料
        if (!$this->validate($this->paymentMethodModel->getValidationRules())) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            $data = [
                'pm_name' => $this->request->getPost('pm_name'),
            ];

            $this->paymentMethodModel->insert($data);

            return redirect()->to('/payment-method')->with('success', '結帳方式新增成功');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', '新增失敗：' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $data = $this->paymentMethodModel->find($id);
        if (!$data) {
            return redirect()->to('/payment-method')->with('error', '結帳方式不存在');
        }

        return view('payment_method/form', [
            'isEdit' => true,
            'data' => $data,
        ]);
    }

    public function update($id)
    {
        $data = $this->paymentMethodModel->find($id);
        if (!$data) {
            return redirect()->to('/payment-method')->with('error', '結帳方式不存在');
        }

        // 驗證資料
        if (!$this->validate($this->paymentMethodModel->getValidationRules())) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            $updateData = [
                'pm_name' => $this->request->getPost('pm_name'),
            ];

            $this->paymentMethodModel->update($id, $updateData);

            return redirect()->to('/payment-method')->with('success', '結帳方式更新成功');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', '更新失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        $data = $this->paymentMethodModel->find($id);
        if (!$data) {
            return redirect()->to('/payment-method')->with('error', '結帳方式不存在');
        }

        // 檢查是否被使用
        if ($this->paymentMethodModel->isUsed($id)) {
            return redirect()->to('/payment-method')->with('error', '此結帳方式正在被使用，無法刪除');
        }

        try {
            $this->paymentMethodModel->delete($id);
            return redirect()->to('/payment-method')->with('success', '結帳方式刪除成功');
        } catch (\Exception $e) {
            return redirect()->to('/payment-method')->with('error', '刪除失敗：' . $e->getMessage());
        }
    }
}
