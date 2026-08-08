<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\WarehouseModel;

class WarehouseController extends BaseController
{
    private $warehouseModel;

    public function __construct()
    {
        $this->warehouseModel = new WarehouseModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->warehouseModel->getList($keyword, $page);

        return view('warehouse/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('warehouse/form', ['isEdit' => false]);
    }

    public function store()
    {
        if (!$this->validate($this->warehouseModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $data = $this->request->getPost();
            $data['w_code'] = $data['w_code'] ?: $this->warehouseModel->generateCode();
            $data['w_is_active'] = $this->request->getPost('w_is_active') ? 1 : 0;
            $this->warehouseModel->insert($data);
            return redirect()->to('/warehouse')->with('success', '倉庫新增成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '新增失敗：' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $data = $this->warehouseModel->find($id);
        if (!$data) return redirect()->to('/warehouse')->with('error', '倉庫不存在');
        return view('warehouse/form', ['isEdit' => true, 'data' => $data]);
    }

    public function update($id)
    {
        if (!$this->warehouseModel->find($id)) return redirect()->to('/warehouse')->with('error', '倉庫不存在');
        if (!$this->validate($this->warehouseModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $data = $this->request->getPost();
            $data['w_is_active'] = $this->request->getPost('w_is_active') ? 1 : 0;
            $this->warehouseModel->update($id, $data);
            return redirect()->to('/warehouse')->with('success', '倉庫更新成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '更新失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!$this->warehouseModel->find($id)) return redirect()->to('/warehouse')->with('error', '倉庫不存在');
        try {
            $this->warehouseModel->delete($id);
            return redirect()->to('/warehouse')->with('success', '倉庫刪除成功');
        } catch (\Exception $e) {
            return redirect()->to('/warehouse')->with('error', '刪除失敗：' . $e->getMessage());
        }
    }
}
