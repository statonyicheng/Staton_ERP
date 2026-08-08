<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\WarehouseModel;

/**
 * 存貨計價 / 結轉：以「在庫量 × 標準成本」計算存貨金額，供月結存貨評價。
 */
class InventoryValuationController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $wId = $this->request->getGet('w');

        $builder = $db->table('product_stock ps')
            ->select('p.p_code, p.p_name, w.w_name, ps.ps_qty, p.p_cost_price, (ps.ps_qty * p.p_cost_price) AS value', false)
            ->join('products p', 'p.p_id = ps.ps_p_id', 'left')
            ->join('warehouses w', 'w.w_id = ps.ps_w_id', 'left')
            ->where('ps.ps_qty !=', 0);
        if ($wId) $builder->where('ps.ps_w_id', $wId);
        $rows = $builder->orderBy('p.p_code', 'ASC')->get()->getResultArray();

        $totalValue = 0;
        foreach ($rows as $r) $totalValue += (float) $r['value'];

        $warehouseModel = new WarehouseModel();
        return view('inventory_valuation/index', [
            'rows' => $rows, 'wId' => $wId, 'totalValue' => $totalValue,
            'warehouses' => $warehouseModel->getAllForDropdown(),
        ]);
    }
}
