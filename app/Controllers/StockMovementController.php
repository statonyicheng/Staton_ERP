<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\StockMovementModel;
use App\Models\ProductModel;
use App\Models\WarehouseModel;

class StockMovementController extends BaseController
{
    private $smModel;
    private $productModel;
    private $warehouseModel;

    public function __construct()
    {
        $this->smModel = new StockMovementModel();
        $this->productModel = new ProductModel();
        $this->warehouseModel = new WarehouseModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $type = $this->request->getGet('type');
        $wId = $this->request->getGet('w');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->smModel->getList($keyword, $page, $type, $wId);

        return view('stock_movement/index', [
            'data' => $data['data'],
            'keyword' => $keyword, 'type' => $type, 'wId' => $wId,
            'types' => array_merge(StockMovementModel::IN_TYPES, StockMovementModel::OUT_TYPES),
            'warehouses' => $this->warehouseModel->getAllForDropdown(),
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('stock_movement/form', [
            'products' => $this->productList(),
            'warehouses' => $this->warehouseModel->getAllForDropdown(),
            'inTypes' => StockMovementModel::IN_TYPES,
            'outTypes' => StockMovementModel::OUT_TYPES,
        ]);
    }

    public function save()
    {
        $post = $this->request->getPost();
        $pId = (int) ($post['sm_p_id'] ?? 0);
        $qty = (int) ($post['sm_qty'] ?? 0);
        $date = $post['sm_date'] ?: date('Y-m-d');
        $type = $post['sm_type'] ?? '';

        if (!$pId || $qty <= 0 || !$type) {
            return redirect()->back()->withInput()->with('error', '請選擇商品、異動類別並輸入正確數量');
        }

        $db = \Config\Database::connect();
        $db->transStart();
        try {
            if ($type === '調撥') {
                $fromW = (int) ($post['sm_w_id'] ?? 0);
                $toW = (int) ($post['to_w_id'] ?? 0);
                if (!$fromW || !$toW || $fromW === $toW) {
                    return redirect()->back()->withInput()->with('error', '調撥需選擇不同的來源與目標倉庫');
                }
                $this->smModel->apply(['sm_date'=>$date,'sm_type'=>'調撥','sm_direction'=>'出','sm_p_id'=>$pId,'sm_w_id'=>$fromW,'sm_qty'=>$qty,'sm_note'=>$post['sm_note'] ?? '調撥出']);
                $this->smModel->apply(['sm_date'=>$date,'sm_type'=>'調撥','sm_direction'=>'入','sm_p_id'=>$pId,'sm_w_id'=>$toW,'sm_qty'=>$qty,'sm_note'=>$post['sm_note'] ?? '調撥入']);
            } else {
                $wId = (int) ($post['sm_w_id'] ?? 0);
                if (!$wId) return redirect()->back()->withInput()->with('error', '請選擇倉庫');
                $this->smModel->apply([
                    'sm_date' => $date, 'sm_type' => $type,
                    'sm_direction' => StockMovementModel::directionOf($type),
                    'sm_p_id' => $pId, 'sm_w_id' => $wId, 'sm_qty' => $qty,
                    'sm_note' => $post['sm_note'] ?? null,
                ]);
            }
            $db->transComplete();
            if ($db->transStatus() === false) {
                return redirect()->back()->withInput()->with('error', '異動失敗，已回復');
            }
            return redirect()->to('/stock-movement')->with('success', '庫存異動已登錄並更新在庫量');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', '異動失敗：' . $e->getMessage());
        }
    }

    private function productList(): array
    {
        return $this->productModel->select('p_id, p_code, p_name')->orderBy('p_name', 'ASC')->findAll();
    }
}
