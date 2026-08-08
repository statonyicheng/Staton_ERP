<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BomItemModel;
use App\Models\ProductStockModel;
use App\Models\ProductModel;

/**
 * 批次需求計劃 MRP：輸入目標母件與生產數量，展開 BOM 一階，
 * 扣除現有庫存，計算各元件短缺量並提出請購/製令建議。
 */
class MrpController extends BaseController
{
    private $bomModel;
    private $stockModel;
    private $productModel;

    public function __construct()
    {
        $this->bomModel = new BomItemModel();
        $this->stockModel = new ProductStockModel();
        $this->productModel = new ProductModel();
    }

    public function index()
    {
        $pId = (int) ($this->request->getGet('p') ?? 0);
        $qty = (int) ($this->request->getGet('qty') ?? 0);

        $rows = [];
        $parent = null;
        if ($pId && $qty > 0) {
            $parent = $this->productModel->find($pId);
            $bom = $this->bomModel->getByParent($pId);
            foreach ($bom as $b) {
                $required = (int) $b['bi_qty'] * $qty;
                $onhand = $this->stockModel->qtyOf((int) $b['bi_child_p_id'], 0); // 不分倉：以全倉合計
                $onhandAll = $this->totalOnhand((int) $b['bi_child_p_id']);
                $short = max(0, $required - $onhandAll);
                $rows[] = [
                    'child_code' => $b['child_code'], 'child_name' => $b['child_name'],
                    'unit_qty' => $b['bi_qty'], 'unit' => $b['bi_unit'],
                    'required' => $required, 'onhand' => $onhandAll, 'short' => $short,
                ];
            }
        }

        return view('mrp/index', [
            'products' => $this->productModel->select('p_id, p_code, p_name')->orderBy('p_name', 'ASC')->findAll(),
            'pId' => $pId, 'qty' => $qty, 'parent' => $parent, 'rows' => $rows,
        ]);
    }

    private function totalOnhand(int $pId): int
    {
        $r = $this->stockModel->builder()->selectSum('ps_qty', 'q')->where('ps_p_id', $pId)->get()->getRowArray();
        return (int) ($r['q'] ?? 0);
    }
}
