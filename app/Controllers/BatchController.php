<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BatchModel;
use App\Models\ProductModel;
use App\Models\WarehouseModel;

class BatchController extends BaseController
{
    private $batchModel;
    private $productModel;
    private $warehouseModel;

    public function __construct()
    {
        $this->batchModel = new BatchModel();
        $this->productModel = new ProductModel();
        $this->warehouseModel = new WarehouseModel();
    }

    private function products(): array
    {
        return $this->productModel->select('p_id, p_code, p_name')->orderBy('p_name', 'ASC')->findAll();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->batchModel->getList($keyword, $page);
        return view('batch/index', [
            'data' => $data['data'], 'keyword' => $keyword,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('batch/form', ['isEdit' => false, 'products' => $this->products(), 'warehouses' => $this->warehouseModel->getAllForDropdown()]);
    }

    public function store()
    {
        if (!$this->validate($this->batchModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $this->batchModel->insert($this->request->getPost());
        return redirect()->to('/batch')->with('success', '批號/序號新增成功');
    }

    public function edit($id)
    {
        $data = $this->batchModel->find($id);
        if (!$data) return redirect()->to('/batch')->with('error', '資料不存在');
        return view('batch/form', ['isEdit' => true, 'data' => $data, 'products' => $this->products(), 'warehouses' => $this->warehouseModel->getAllForDropdown()]);
    }

    public function update($id)
    {
        if (!$this->batchModel->find($id)) return redirect()->to('/batch')->with('error', '資料不存在');
        if (!$this->validate($this->batchModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $this->batchModel->update($id, $this->request->getPost());
        return redirect()->to('/batch')->with('success', '批號/序號更新成功');
    }

    public function delete($id)
    {
        if (!$this->batchModel->find($id)) return redirect()->to('/batch')->with('error', '資料不存在');
        $this->batchModel->delete($id);
        return redirect()->to('/batch')->with('success', '批號/序號刪除成功');
    }
}
