<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductModel;

class PricingController extends BaseController
{
    private $productModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = (int) ($this->request->getGet('page') ?: 1);
        $builder = $this->productModel->builder()->select('p_id, p_code, p_name, p_specifications, p_standard_price, p_cost_price');
        if ($keyword) {
            $builder->groupStart()->like('p_name', $keyword)->orLike('p_code', $keyword)->groupEnd();
        }
        $builder->orderBy('p_code', 'ASC');
        $total = $builder->countAllResults(false);
        $perPage = \App\Libraries\PageSize::get(15);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return view('pricing/index', [
            'data' => $data, 'keyword' => $keyword,
            'pager' => ['currentPage' => $page, 'totalPages' => (int) ceil($total / $perPage)],
        ]);
    }

    public function edit($id)
    {
        $data = $this->productModel->find($id);
        if (!$data) return redirect()->to('/pricing')->with('error', '商品不存在');
        return view('pricing/form', ['data' => $data]);
    }

    public function update($id)
    {
        if (!$this->productModel->find($id)) return redirect()->to('/pricing')->with('error', '商品不存在');
        $this->productModel->update($id, [
            'p_standard_price' => (int) $this->request->getPost('p_standard_price'),
            'p_cost_price' => (float) $this->request->getPost('p_cost_price'),
        ]);
        return redirect()->to('/pricing')->with('success', '價格更新成功');
    }
}
