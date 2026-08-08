<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductStockModel;
use App\Models\StockMovementModel;
use App\Models\WarehouseModel;

/**
 * 庫存盤點：輸入實盤數量，系統對差異產生盤盈(入)/盤虧(出)異動並更新在庫。
 */
class StocktakeController extends BaseController
{
    private $stockModel;
    private $smModel;
    private $warehouseModel;

    public function __construct()
    {
        $this->stockModel = new ProductStockModel();
        $this->smModel = new StockMovementModel();
        $this->warehouseModel = new WarehouseModel();
    }

    public function index()
    {
        $wId = $this->request->getGet('w');
        $builder = $this->stockModel->builder()
            ->select('ps.ps_id, ps.ps_p_id, ps.ps_w_id, ps.ps_qty, p.p_code, p.p_name, w.w_name', false)
            ->from('product_stock ps', true)
            ->join('products p', 'p.p_id = ps.ps_p_id', 'left')
            ->join('warehouses w', 'w.w_id = ps.ps_w_id', 'left');
        if ($wId) $builder->where('ps.ps_w_id', $wId);
        $rows = $builder->orderBy('p.p_code', 'ASC')->get()->getResultArray();

        return view('stocktake/index', [
            'rows' => $rows, 'wId' => $wId,
            'warehouses' => $this->warehouseModel->getAllForDropdown(),
        ]);
    }

    public function save()
    {
        $counted = $this->request->getPost('counted') ?? [];
        $date = $this->request->getPost('date') ?: date('Y-m-d');

        $db = \Config\Database::connect();
        $db->transStart();
        $adjusted = 0;
        try {
            foreach ($counted as $psId => $cntVal) {
                $ps = $this->stockModel->find($psId);
                if (!$ps) continue;
                $cnt = (int) $cntVal;
                $diff = $cnt - (int) $ps['ps_qty'];
                if ($diff === 0) continue;
                $this->smModel->apply([
                    'sm_date' => $date,
                    'sm_type' => $diff > 0 ? '盤盈' : '盤虧',
                    'sm_direction' => $diff > 0 ? '入' : '出',
                    'sm_p_id' => (int) $ps['ps_p_id'], 'sm_w_id' => (int) $ps['ps_w_id'],
                    'sm_qty' => abs($diff), 'sm_note' => '盤點調整',
                ]);
                $adjusted++;
            }
            $db->transComplete();
            if ($db->transStatus() === false) return redirect()->back()->with('error', '盤點失敗，已回復');
            return redirect()->to('/stocktake')->with('success', "盤點完成，調整 {$adjusted} 個品項");
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', '盤點失敗：' . $e->getMessage());
        }
    }
}
