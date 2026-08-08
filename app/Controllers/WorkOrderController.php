<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\WorkOrderModel;
use App\Models\BomItemModel;
use App\Models\StockMovementModel;
use App\Models\ProductStockModel;
use App\Models\ProductModel;
use App\Models\WarehouseModel;

class WorkOrderController extends BaseController
{
    private $woModel;
    private $bomModel;
    private $smModel;
    private $stockModel;
    private $productModel;
    private $warehouseModel;

    public function __construct()
    {
        $this->woModel = new WorkOrderModel();
        $this->bomModel = new BomItemModel();
        $this->smModel = new StockMovementModel();
        $this->stockModel = new ProductStockModel();
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
        $status = $this->request->getGet('status');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->woModel->getList($keyword, $page, $status);

        return view('work_order/index', [
            'data' => $data['data'],
            'keyword' => $keyword, 'status' => $status,
            'statuses' => WorkOrderModel::STATUSES,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('work_order/form', [
            'isEdit' => false,
            'products' => $this->products(),
            'warehouses' => $this->warehouseModel->getAllForDropdown(),
            'defaultNo' => $this->woModel->generateNo(),
        ]);
    }

    public function store()
    {
        try {
            $d = $this->request->getPost();
            if (!$d['wo_p_id']) return redirect()->back()->withInput()->with('error', '請選擇生產母件');
            $this->woModel->insert([
                'wo_no' => $d['wo_no'] ?: $this->woModel->generateNo($d['wo_date'] ?? null),
                'wo_p_id' => $d['wo_p_id'], 'wo_qty' => (int) ($d['wo_qty'] ?? 0),
                'wo_date' => $d['wo_date'] ?: date('Y-m-d'), 'wo_due_date' => $d['wo_due_date'] ?: null,
                'wo_w_id' => $d['wo_w_id'] ?: null, 'wo_status' => '未完工', 'wo_note' => $d['wo_note'] ?? null,
            ]);
            return redirect()->to('/work-order')->with('success', '製令新增成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '新增失敗：' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $wo = $this->woModel->find($id);
        if (!$wo) return redirect()->to('/work-order')->with('error', '製令不存在');
        return view('work_order/form', ['isEdit' => true, 'data' => $wo, 'products' => $this->products(), 'warehouses' => $this->warehouseModel->getAllForDropdown()]);
    }

    public function update($id)
    {
        // 樂觀鎖：這筆資料若在使用者編輯期間被別人改過，就擋下來，不要無聲覆蓋
        if ($msg = \App\Libraries\EditGuard::check('work_orders', 'wo_id', $id, 'wo_updated_at', $this->request->getPost(\App\Libraries\EditGuard::FIELD))) {
            return redirect()->back()->withInput()->with('error', $msg);
        }
        $wo = $this->woModel->find($id);
        if (!$wo) return redirect()->to('/work-order')->with('error', '製令不存在');
        if ($wo['wo_status'] === '已完工') return redirect()->to('/work-order')->with('error', '已完工製令不可修改');
        try {
            $d = $this->request->getPost();
            $this->woModel->update($id, [
                'wo_p_id' => $d['wo_p_id'], 'wo_qty' => (int) ($d['wo_qty'] ?? 0),
                'wo_date' => $d['wo_date'] ?: date('Y-m-d'), 'wo_due_date' => $d['wo_due_date'] ?: null,
                'wo_w_id' => $d['wo_w_id'] ?: null, 'wo_note' => $d['wo_note'] ?? null,
            ]);
            return redirect()->to('/work-order')->with('success', '製令更新成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '更新失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!$this->woModel->find($id)) return redirect()->to('/work-order')->with('error', '製令不存在');
        $this->woModel->delete($id);
        return redirect()->to('/work-order')->with('success', '製令刪除成功');
    }

    /** 製令明細：BOM 展開所需用料 + 現有庫存 */
    public function view($id)
    {
        $wo = $this->woModel->getWithProduct($id);
        if (!$wo) return redirect()->to('/work-order')->with('error', '製令不存在');
        $bom = $this->bomModel->getByParent($wo['wo_p_id']);
        foreach ($bom as &$b) {
            $b['required'] = (int) $b['bi_qty'] * (int) $wo['wo_qty'];
            $b['onhand'] = $wo['wo_w_id'] ? $this->stockModel->qtyOf($b['bi_child_p_id'], $wo['wo_w_id']) : 0;
            $b['short'] = max(0, $b['required'] - $b['onhand']);
        }
        return view('work_order/view', ['wo' => $wo, 'bom' => $bom]);
    }

    /** 完工入庫：依 BOM 領料出庫，成品入庫 */
    public function complete($id)
    {
        $wo = $this->woModel->getWithProduct($id);
        if (!$wo) return redirect()->to('/work-order')->with('error', '製令不存在');
        if ($wo['wo_status'] === '已完工') return redirect()->to('/work-order')->with('error', '此製令已完工');
        if (!$wo['wo_w_id']) return redirect()->to('/work-order')->with('error', '請先於製令指定領料/入庫倉');

        $bom = $this->bomModel->getByParent($wo['wo_p_id']);
        $date = date('Y-m-d');

        $db = \Config\Database::connect();
        $db->transStart();
        try {
            // 依 BOM 領料出庫
            foreach ($bom as $b) {
                $need = (int) $b['bi_qty'] * (int) $wo['wo_qty'];
                if ($need <= 0) continue;
                $this->smModel->apply([
                    'sm_date' => $date, 'sm_type' => '領料', 'sm_direction' => '出',
                    'sm_p_id' => (int) $b['bi_child_p_id'], 'sm_w_id' => (int) $wo['wo_w_id'], 'sm_qty' => $need,
                    'sm_ref_type' => '製令', 'sm_ref_id' => (int) $wo['wo_id'], 'sm_ref_no' => $wo['wo_no'],
                    'sm_note' => '製令領料',
                ]);
            }
            // 成品入庫
            $this->smModel->apply([
                'sm_date' => $date, 'sm_type' => '完工入庫', 'sm_direction' => '入',
                'sm_p_id' => (int) $wo['wo_p_id'], 'sm_w_id' => (int) $wo['wo_w_id'], 'sm_qty' => (int) $wo['wo_qty'],
                'sm_ref_type' => '製令', 'sm_ref_id' => (int) $wo['wo_id'], 'sm_ref_no' => $wo['wo_no'],
                'sm_note' => '製令完工入庫',
            ]);
            $this->woModel->update($id, ['wo_status' => '已完工']);
            $db->transComplete();
            if ($db->transStatus() === false) return redirect()->back()->with('error', '完工失敗，已回復');
            return redirect()->to('/work-order')->with('success', '製令已完工：依 BOM 領料出庫並成品入庫完成');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', '完工失敗：' . $e->getMessage());
        }
    }
}
