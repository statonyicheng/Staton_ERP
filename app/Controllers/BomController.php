<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BomItemModel;
use App\Models\ProductModel;

class BomController extends BaseController
{
    private $bomModel;
    private $productModel;

    public function __construct()
    {
        $this->bomModel = new BomItemModel();
        $this->productModel = new ProductModel();
    }

    private function products(): array
    {
        return $this->productModel->select('p_id, p_code, p_name')->orderBy('p_name', 'ASC')->findAll();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->bomModel->parentsWithCount($keyword, $page);

        return view('bom/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function manage($pId = null)
    {
        $items = $pId ? $this->bomModel->getByParent($pId) : [];
        $parent = null;
        if ($pId) $parent = $this->productModel->find($pId);
        return view('bom/form', [
            'parentId' => $pId,
            'parent' => $parent,
            'items' => $items,
            'products' => $this->products(),
        ]);
    }

    public function save()
    {
        $parentId = (int) $this->request->getPost('parent_p_id');
        if (!$parentId) return redirect()->back()->withInput()->with('error', '請選擇母件商品');

        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $this->bomModel->deleteByParent($parentId);
            $items = $this->request->getPost('items') ?? [];
            $now = date('Y-m-d H:i:s');
            foreach ($items as $it) {
                $childId = (int) ($it['bi_child_p_id'] ?? 0);
                if (!$childId) continue;
                $this->bomModel->insert([
                    'bi_parent_p_id' => $parentId,
                    'bi_child_p_id' => $childId,
                    'bi_qty' => (int) ($it['bi_qty'] ?? 1),
                    'bi_unit' => $it['bi_unit'] ?? null,
                    'bi_note' => $it['bi_note'] ?? null,
                    'bi_created_at' => $now,
                ]);
            }
            $db->transComplete();
            if ($db->transStatus() === false) return redirect()->back()->withInput()->with('error', '儲存失敗，已回復');
            return redirect()->to('/bom')->with('success', 'BOM 儲存成功');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', '儲存失敗：' . $e->getMessage());
        }
    }

    public function delete($pId)
    {
        $this->bomModel->deleteByParent($pId);
        return redirect()->to('/bom')->with('success', 'BOM 已刪除');
    }
}
