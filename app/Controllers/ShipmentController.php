<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ShipmentModel;
use App\Models\OrderModel;

class ShipmentController extends BaseController
{
    private $shipmentModel;
    private $orderModel;

    public function __construct()
    {
        $this->shipmentModel = new ShipmentModel();
        $this->orderModel = new OrderModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = $this->request->getGet('page') ?: 1;
        $orderId = $this->request->getGet('order_id');

        $data = $this->shipmentModel->getList($keyword, $page, $orderId);
        $pagerData = [
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages']
        ];

        // 如果有訂單篩選，取得訂單資訊
        $orderInfo = null;
        if ($orderId) {
            $orderInfo = $this->orderModel->getOrderWithItems($orderId);
        }

        return view('shipment/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'pager' => $pagerData,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo
        ]);
    }

    public function view($id)
    {
        $data = $this->shipmentModel->getShipmentDetails($id);

        if (!$data) {
            return redirect()->to(url_to('ShipmentController::index'))->with('error', '找不到該出貨單');
        }

        return view('shipment/view', ['data' => $data]);
    }

    public function create($orderId)
    {
        $order = $this->orderModel->getOrderWithItems($orderId);
        if (!$order) {
            return redirect()->back()->with('error', '訂單不存在');
        }

        // 計算剩餘未出貨數量
        $hasRemaining = false;
        foreach ($order['items'] as &$item) {
            $item['remaining_quantity'] = $item['oi_quantity'] - ($item['oi_shipped_quantity'] ?? 0);
            if ($item['remaining_quantity'] > 0) {
                $hasRemaining = true;
            }
        }

        // 檢查是否還有可出貨的項目
        if (!$hasRemaining) {
            return redirect()->back()->with('error', '訂單所有項目已全部出貨');
        }

        return view('shipment/form', [
            'isEdit' => false,
            'order' => $order,
            'shipment' => null,
            'shipmentNumber' => $this->shipmentModel->generateShipmentNumber(),
            'date' => date('Y-m-d')
        ]);
    }

    public function edit($id)
    {
        $shipmentModel = new \App\Models\ShipmentModel();
        $shipmentItemModel = new \App\Models\ShipmentItemModel();

        // 取得出貨單資料
        $shipment = $shipmentModel->find($id);
        if (!$shipment) {
            return redirect()->back()->with('error', '出貨單不存在');
        }

        // 取得訂單及項目資料
        $order = $this->orderModel->getOrderWithItems($shipment['s_o_id']);
        if (!$order) {
            return redirect()->back()->with('error', '訂單不存在');
        }

        // 取得出貨明細
        $shipmentItems = $shipmentItemModel->getItemsByShipmentId($id);

        // 建立出貨明細的映射（以訂單項目ID為鍵）
        $shipmentItemsMap = [];
        foreach ($shipmentItems as $item) {
            $shipmentItemsMap[$item['si_oi_id']] = $item;
        }

        // 計算剩餘未出貨數量（需要扣除本次出貨的數量來顯示其他出貨單的已出貨數量）
        foreach ($order['items'] as &$item) {
            $currentShipmentQty = $shipmentItemsMap[$item['oi_id']]['si_quantity'] ?? 0;
            // 其他出貨單的已出貨數量 = 總已出貨 - 本次出貨
            $item['other_shipped_quantity'] = $item['oi_shipped_quantity'] - $currentShipmentQty;
            // 剩餘可出貨數量 = 訂單數量 - 其他出貨單的已出貨數量
            $item['remaining_quantity'] = $item['oi_quantity'] - $item['other_shipped_quantity'];
            $item['current_shipment_qty'] = $currentShipmentQty;
        }

        return view('shipment/form', [
            'isEdit' => true,
            'order' => $order,
            'shipment' => $shipment,
            'shipmentNumber' => $shipment['s_number'],
            'date' => $shipment['s_date']
        ]);
    }

    public function save()
    {
        $data = $this->request->getPost();
        $items = $this->request->getPost('items');
        $isEdit = !empty($data['s_id']);

        // 驗證至少要有一個出貨項目且數量大於0
        $validItems = array_filter($items, function ($item) {
            return !empty($item['si_quantity']) && $item['si_quantity'] > 0;
        });

        if (empty($validItems)) {
            return redirect()->back()->withInput()->with('error', '至少需要輸入一個出貨項目的數量');
        }

        $result = $this->shipmentModel->saveShipmentWithItems($data, $items);

        if (!$result['success']) {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }

        $message = $isEdit ? '出貨單更新成功' : '出貨單建立成功';

        // 重定向回出貨單列表（帶訂單篩選）
        return redirect()->to(url_to('ShipmentController::index') . '?order_id=' . $data['s_o_id'])
            ->with('success', $message);
    }

    public function delete($id)
    {
        if ($this->shipmentModel->deleteShipment($id)) {
            return redirect()->back()->with('success', '出貨單已刪除');
        }
        return redirect()->back()->with('error', '刪除失敗');
    }
}
