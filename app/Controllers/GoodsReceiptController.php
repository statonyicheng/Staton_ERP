<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PurchaseOrderModel;
use App\Models\StockMovementModel;
use App\Models\WarehouseModel;

/**
 * 進貨作業：依採購單收貨入庫，產生「進貨」異動並更新在庫量，回寫採購單狀態。
 */
class GoodsReceiptController extends BaseController
{
    private $poModel;
    private $smModel;
    private $warehouseModel;

    public function __construct()
    {
        $this->poModel = new PurchaseOrderModel();
        $this->smModel = new StockMovementModel();
        $this->warehouseModel = new WarehouseModel();
    }

    public function index()
    {
        // 待進貨採購單（未結案 / 部分進貨）
        $pending = $this->poModel->getList(null, 1, '未結案')['data'];
        return view('goods_receipt/index', [
            'pending' => $pending,
        ]);
    }

    public function receive($poId)
    {
        $po = $this->poModel->getWithItems($poId);
        if (!$po) return redirect()->to('/goods-receipt')->with('error', '採購單不存在');
        if (in_array($po['po_status'], ['已結案', '作廢'], true)) {
            return redirect()->to('/goods-receipt')->with('error', '此採購單已結案或作廢，無法進貨');
        }
        return view('goods_receipt/receive', [
            'po' => $po,
            'warehouses' => $this->warehouseModel->getAllForDropdown(),
        ]);
    }

    public function doReceive($poId)
    {
        $po = $this->poModel->getWithItems($poId);
        if (!$po) return redirect()->to('/goods-receipt')->with('error', '採購單不存在');

        $wId = (int) $this->request->getPost('w_id');
        if (!$wId) return redirect()->back()->withInput()->with('error', '請選擇入庫倉庫');
        $qtys = $this->request->getPost('qty') ?? [];
        $date = $this->request->getPost('date') ?: date('Y-m-d');

        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $count = 0;
            foreach ($po['items'] as $it) {
                if (!$it['poi_p_id']) continue; // 未對應商品者不進庫
                $q = (int) ($qtys[$it['poi_id']] ?? 0);
                if ($q <= 0) continue;
                $this->smModel->apply([
                    'sm_date' => $date, 'sm_type' => '進貨', 'sm_direction' => '入',
                    'sm_p_id' => (int) $it['poi_p_id'], 'sm_w_id' => $wId, 'sm_qty' => $q,
                    'sm_ref_type' => '採購單', 'sm_ref_id' => (int) $po['po_id'], 'sm_ref_no' => $po['po_no'],
                    'sm_note' => $it['poi_name'],
                ]);
                $count++;
            }
            $this->poModel->update($poId, ['po_status' => '已結案']);
            $db->transComplete();
            if ($db->transStatus() === false) {
                return redirect()->back()->with('error', '進貨失敗，已回復');
            }
            return redirect()->to('/goods-receipt')->with('success', "進貨完成，已入庫 {$count} 個品項並更新在庫量");
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', '進貨失敗：' . $e->getMessage());
        }
    }
}
