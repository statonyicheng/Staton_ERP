<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InvoiceModel;
use App\Models\CustomerModel;

class InvoiceController extends BaseController
{
    private $invModel;
    private $customerModel;

    public function __construct()
    {
        $this->invModel = new InvoiceModel();
        $this->customerModel = new CustomerModel();
    }

    private function customers(): array
    {
        return $this->customerModel->select('c_id, c_code, c_name, c_tax_id')->orderBy('c_name', 'ASC')->findAll();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $status = $this->request->getGet('status');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->invModel->getList($keyword, $page, $status);
        return view('invoice/index', [
            'data' => $data['data'], 'keyword' => $keyword, 'status' => $status,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('invoice/form', ['isEdit' => false, 'customers' => $this->customers(), 'defaultNo' => $this->invModel->generateNumber()]);
    }

    public function store()
    {
        $d = $this->request->getPost();
        $amount = (int) ($d['inv_amount'] ?? 0);
        $tax = (int) ($d['inv_tax'] ?? 0);
        try {
            $this->invModel->insert([
                'inv_number' => $d['inv_number'] ?: $this->invModel->generateNumber(),
                'inv_date' => $d['inv_date'] ?: date('Y-m-d'),
                'inv_c_id' => $d['inv_c_id'] ?: null,
                'inv_buyer' => $d['inv_buyer'] ?: null, 'inv_buyer_tax' => $d['inv_buyer_tax'] ?: null,
                'inv_amount' => $amount, 'inv_tax' => $tax, 'inv_total' => $amount + $tax,
                'inv_status' => '已開立', 'inv_note' => $d['inv_note'] ?? null,
            ]);
            return redirect()->to('/invoice')->with('success', '發票開立成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '開立失敗：' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $data = $this->invModel->find($id);
        if (!$data) return redirect()->to('/invoice')->with('error', '發票不存在');
        return view('invoice/form', ['isEdit' => true, 'data' => $data, 'customers' => $this->customers()]);
    }

    public function update($id)
    {
        if (!$this->invModel->find($id)) return redirect()->to('/invoice')->with('error', '發票不存在');
        $d = $this->request->getPost();
        $amount = (int) ($d['inv_amount'] ?? 0);
        $tax = (int) ($d['inv_tax'] ?? 0);
        $this->invModel->update($id, [
            'inv_date' => $d['inv_date'] ?: date('Y-m-d'), 'inv_c_id' => $d['inv_c_id'] ?: null,
            'inv_buyer' => $d['inv_buyer'] ?: null, 'inv_buyer_tax' => $d['inv_buyer_tax'] ?: null,
            'inv_amount' => $amount, 'inv_tax' => $tax, 'inv_total' => $amount + $tax,
            'inv_note' => $d['inv_note'] ?? null,
        ]);
        return redirect()->to('/invoice')->with('success', '發票更新成功');
    }

    public function void($id)
    {
        if (!$this->invModel->find($id)) return redirect()->to('/invoice')->with('error', '發票不存在');
        $this->invModel->update($id, ['inv_status' => '作廢']);
        return redirect()->to('/invoice')->with('success', '發票已作廢');
    }

    public function delete($id)
    {
        if (!$this->invModel->find($id)) return redirect()->to('/invoice')->with('error', '發票不存在');
        $this->invModel->delete($id);
        return redirect()->to('/invoice')->with('success', '發票刪除成功');
    }
}
