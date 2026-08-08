<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BomItemModel;
use App\Models\ProductModel;

/**
 * 成本計算：依 BOM 展算母件標準成本＝Σ(子件標準成本 × 用量)，可回寫至商品成本。
 */
class CostController extends BaseController
{
    private $bomModel;
    private $productModel;

    public function __construct()
    {
        $this->bomModel = new BomItemModel();
        $this->productModel = new ProductModel();
    }

    private function rollup(int $parentId): array
    {
        $comps = $this->bomModel->getByParent($parentId);
        $total = 0.0;
        $lines = [];
        foreach ($comps as $c) {
            $child = $this->productModel->find($c['bi_child_p_id']);
            $unitCost = $child ? (float) $child['p_cost_price'] : 0;
            $line = $unitCost * (int) $c['bi_qty'];
            $total += $line;
            $lines[] = [
                'name' => $c['child_name'], 'code' => $c['child_code'],
                'qty' => $c['bi_qty'], 'unit_cost' => $unitCost, 'line' => $line,
            ];
        }
        return ['total' => $total, 'lines' => $lines];
    }

    public function index()
    {
        // 有 BOM 的母件
        $parents = $this->bomModel->builder()
            ->select('DISTINCT bi_parent_p_id', false)->get()->getResultArray();
        $rows = [];
        foreach ($parents as $p) {
            $pid = (int) $p['bi_parent_p_id'];
            $prod = $this->productModel->find($pid);
            if (!$prod) continue;
            $r = $this->rollup($pid);
            $rows[] = [
                'p_id' => $pid, 'p_code' => $prod['p_code'], 'p_name' => $prod['p_name'],
                'current_cost' => (float) $prod['p_cost_price'], 'calc_cost' => $r['total'],
            ];
        }
        return view('cost/index', ['rows' => $rows]);
    }

    public function view($id)
    {
        $prod = $this->productModel->find($id);
        if (!$prod) return redirect()->to('/cost')->with('error', '商品不存在');
        $r = $this->rollup((int) $id);
        return view('cost/view', ['prod' => $prod, 'calc' => $r]);
    }

    public function apply($id)
    {
        $prod = $this->productModel->find($id);
        if (!$prod) return redirect()->to('/cost')->with('error', '商品不存在');
        $r = $this->rollup((int) $id);
        $this->productModel->update($id, ['p_cost_price' => $r['total']]);
        return redirect()->to('/cost')->with('success', $prod['p_name'] . ' 標準成本已回寫：' . number_format($r['total'], 2));
    }
}
