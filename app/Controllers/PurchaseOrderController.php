<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PurchaseOrderModel;
use App\Models\PurchaseOrderItemModel;
use App\Models\SupplierModel;
use App\Models\ProductModel;

class PurchaseOrderController extends BaseController
{
    private $poModel;
    private $itemModel;
    private $supplierModel;
    private $productModel;

    public function __construct()
    {
        $this->poModel = new PurchaseOrderModel();
        $this->itemModel = new PurchaseOrderItemModel();
        $this->supplierModel = new SupplierModel();
        $this->productModel = new ProductModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $status = $this->request->getGet('status');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->poModel->getList($keyword, $page, $status);

        return view('purchase_order/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'status' => $status,
            'statuses' => PurchaseOrderModel::STATUSES,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('purchase_order/form', [
            'isEdit' => false,
            'suppliers' => $this->supplierModel->getAllForDropdown(),
            'products' => $this->productList(),
            'defaultNo' => $this->poModel->generateNo(),
        ]);
    }

    public function edit($id)
    {
        $po = $this->poModel->getWithItems($id);
        if (!$po) return redirect()->to('/purchase-order')->with('error', '採購單不存在');
        return view('purchase_order/form', [
            'isEdit' => true,
            'data' => $po,
            'suppliers' => $this->supplierModel->getAllForDropdown(),
            'products' => $this->productList(),
        ]);
    }

    public function view($id)
    {
        $po = $this->poModel->getWithItems($id);
        if (!$po) return redirect()->to('/purchase-order')->with('error', '採購單不存在');
        return view('purchase_order/view', ['data' => $po]);
    }

    public function save()
    {
        $post = $this->request->getPost();
        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $items = $post['items'] ?? [];
            $subtotal = 0;
            foreach ($items as $it) {
                $subtotal += ((int) ($it['poi_qty'] ?? 0)) * ((int) ($it['poi_price'] ?? 0));
            }
            $tax = (int) ($post['po_tax'] ?? 0);

            $poData = [
                'po_s_id' => $post['po_s_id'] ?: null,
                'po_date' => $post['po_date'] ?: date('Y-m-d'),
                'po_expected_date' => $post['po_expected_date'] ?: null,
                'po_status' => $post['po_status'] ?? '未結案',
                'po_subtotal' => $subtotal,
                'po_tax' => $tax,
                'po_total' => $subtotal + $tax,
                'po_note' => $post['po_note'] ?? null,
            ];

            $poId = $post['po_id'] ?? null;
            if ($poId) {
                $this->poModel->update($poId, $poData);
                $this->itemModel->deleteByPo($poId);
            } else {
                $poData['po_no'] = $post['po_no'] ?: $this->poModel->generateNo($poData['po_date']);
                $poId = $this->poModel->insert($poData);
            }

            $sort = 0;
            foreach ($items as $it) {
                if (empty($it['poi_name'])) continue;
                $qty = (int) ($it['poi_qty'] ?? 0);
                $price = (int) ($it['poi_price'] ?? 0);
                $this->itemModel->insert([
                    'poi_po_id' => $poId,
                    'poi_p_id' => $it['poi_p_id'] ?: null,
                    'poi_name' => $it['poi_name'],
                    'poi_spec' => $it['poi_spec'] ?? null,
                    'poi_qty' => $qty,
                    'poi_unit' => $it['poi_unit'] ?? null,
                    'poi_price' => $price,
                    'poi_amount' => $qty * $price,
                    'poi_sort' => $sort += 10,
                ]);
            }

            $db->transComplete();
            if ($db->transStatus() === false) {
                return redirect()->back()->withInput()->with('error', '儲存失敗，交易已回復');
            }
            return redirect()->to('/purchase-order')->with('success', '採購單儲存成功');
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', $e->getMessage());
            return redirect()->back()->withInput()->with('error', '儲存失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!$this->poModel->find($id)) return redirect()->to('/purchase-order')->with('error', '採購單不存在');
        $db = \Config\Database::connect();
        $db->transStart();
        $this->itemModel->deleteByPo($id);
        $this->poModel->delete($id);
        $db->transComplete();
        return redirect()->to('/purchase-order')->with('success', '採購單刪除成功');
    }

    public function report()
    {
        $months = $this->poModel->availableMonths();
        $ym = $this->request->getGet('ym') ?: '';
        return view('purchase_order/report', [
            'rows' => $this->poModel->summaryBySupplier($ym ?: null),
            'ym' => $ym,
            'months' => $months,
        ]);
    }

    private function productList(): array
    {
        return $this->productModel->select('p_id, p_name, p_specifications, p_standard_price')
            ->orderBy('p_name', 'ASC')->findAll();
    }
}
