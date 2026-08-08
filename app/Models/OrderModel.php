<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'o_id';
    protected $allowedFields = [
        'o_number',
        'o_date',
        'o_c_id',
        'o_cc_id',
        'o_q_id',
        'o_delivery_date',
        'o_delivery_city',
        'o_delivery_address',
        'o_total_amount',
        'o_subtotal',
        'o_discount',
        'o_tax_rate',
        'o_shipping_fee',
        'o_tax_amount',
        'o_payment_status',
        'o_invoice_number',
        'o_status',
        'o_shipment_status',
        'o_notes',
        'o_shipping_address',
        'o_vendor_contect',
        'o_vendor_address',
        'o_vendor',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField = 'o_created_at';
    protected $updatedField = 'o_updated_at';

    // 獲取訂單及其相關資訊
    public function getOrdersWithDetails()
    {
        return $this->select('
                orders.*,
                customers.c_name,
                customers.c_code
            ')
            ->join('customers', 'customers.c_id = orders.o_c_id')
            ->orderBy('orders.o_created_at', 'DESC')
            ->findAll();
    }

    // 獲取單個訂單及其項目
    public function getOrderWithItems($orderId)
    {
        $order = $this->select('orders.*, customers.c_name, customers.c_phone, customers.c_email, customer_contacts.cc_name, customer_contacts.cc_phone')
            ->join('customers', 'customers.c_id = orders.o_c_id', 'left')
            ->join('customer_contacts', 'customer_contacts.cc_id = orders.o_cc_id', 'left')
            ->where('orders.o_id', $orderId)
            ->first();

        if (!$order) {
            return null;
        }

        $orderItemsModel = new OrderItemModel();
        $order['items'] = $orderItemsModel->getItemsByOrderId($orderId);

        return $order;
    }

    // 獲取訂單列表（支援搜尋和分頁）
    public function getList($keyword = null, $page = 1)
    {
        $builder = $this->builder()
            ->select('orders.o_id, orders.o_number, orders.o_date, orders.o_total_amount, orders.o_status, orders.o_payment_status, orders.o_shipment_status, orders.o_delivery_date, orders.o_created_at, orders.o_updated_at, customers.c_name')
            ->join('customers', 'customers.c_id = orders.o_c_id');

        if ($keyword) {
            $builder->groupStart()
                ->like('orders.o_number', $keyword)
                ->orLike('customers.c_name', $keyword)
                ->groupEnd();
        }

        $builder->orderBy('orders.o_created_at', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = 10;
        $totalPages = ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return [
            'data' => $data,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * 取得指定客戶的訂單列表（分頁）
     */
    public function getByCustomer(int $customerId, int $page = 1, int $perPage = 10): array
    {
        $builder = $this->builder()
            ->select('o_id, o_number, o_date, o_total_amount, o_payment_status, o_shipment_status, o_status, o_created_at')
            ->where('o_c_id', $customerId)
            ->orderBy('o_created_at', 'DESC');

        $total = $builder->countAllResults(false);
        $totalPages = ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return [
            'data' => $data,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ];
    }

    // 根據報價單創建訂單
    public function createFromQuote($quoteId)
    {
        $quoteModel = new \App\Models\QuoteModel();
        $quote = $quoteModel->getQuoteWithItems($quoteId);

        if (!$quote) {
            return false;
        }

        // 檢查是否已經有對應的訂單
        if (!empty($quote['q_o_id'])) {
            return false; // 已轉換過
        }

        // 準備訂單數據
        $orderData = [
            'o_number' => $this->generateOrderNumber(),
            'o_date' => date('Y-m-d'),
            'o_c_id' => $quote['q_c_id'],
            'o_q_id' => $quote['q_id'],
            'o_total_amount' => $quote['q_total_amount'],
            'o_status' => 'processing',
            'o_payment_status' => 'unpaid',
            'o_shipment_status' => 'preparing',
            'o_cc_id' => $quote['q_cc_id'],
            'o_delivery_city' => $quote['q_delivery_city'],
            'o_delivery_address' => $quote['q_delivery_address'],
            'o_subtotal' => $quote['q_subtotal'],
            'o_discount' => $quote['q_discount'],
            'o_tax_rate' => $quote['q_tax_rate'],
            'o_shipping_fee' => $quote['q_shipping_fee'],
            'o_tax_amount' => $quote['q_tax_amount'],
            'o_vendor' => $quote['q_vendor'],
        ];

        // 準備訂單項目數據
        $orderItems = [];
        foreach ($quote['items'] as $item) {
            $orderItems[] = [
                'oi_pi_id' => $item['qi_pi_id'],
                'oi_quantity' => $item['qi_quantity'],
                'oi_unit_price' => $item['qi_unit_price'],
                'oi_discount' => $item['qi_discount'],
                'oi_amount' => $item['qi_quantity'] * $item['qi_unit_price'] * (1 - $item['qi_discount'] / 100),
                'oi_color' => $item['qi_color'],
                'oi_size' => $item['qi_size'],
            ];
        }

        // 使用統一的儲存方法
        $result = $this->saveOrderWithItems($orderData, $orderItems);

        if ($result['success']) {
            // 更新報價單的訂單ID，避免重複轉換
            $quoteModel->update($quoteId, ['q_o_id' => $result['orderId']]);
            return $result['orderId'];
        }

        return false;
    }

    // 儲存訂單及其項目
    public function saveOrderWithItems(array $orderData, array $items): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $orderItemModel = new OrderItemModel();
            $orderId = $orderData['o_id'] ?? null;
            $orderData['o_cc_id'] = $orderData['o_cc_id'] ?? null;

            // 手動驗證資料
            if (!$this->validate($orderData)) {
                return [
                    'success' => false,
                    'message' => '驗證失敗：' . implode(', ', $this->errors()),
                    'orderId' => null,
                ];
            }

            if ($orderId) {
                // 更新訂單：需要驗證修改的合法性
                $oldItems = $orderItemModel->where('oi_o_id', $orderId)->findAll();

                // 建立舊項目的映射（以圖片ID為鍵）
                $oldItemsMap = [];
                foreach ($oldItems as $oldItem) {
                    $oldItemsMap[$oldItem['oi_pi_id']] = $oldItem;
                }

                // 驗證新項目
                foreach ($items as $item) {
                    if (empty($item['oi_pi_id'])) {
                        continue;
                    }

                    $imageId = $item['oi_pi_id'];
                    $newQuantity = $item['oi_quantity'];

                    // 如果是現有項目，檢查數量是否小於已出貨數量
                    if (isset($oldItemsMap[$imageId])) {
                        $shippedQty = $oldItemsMap[$imageId]['oi_shipped_quantity'] ?? 0;

                        if ($newQuantity < $shippedQty) {
                            return [
                                'success' => false,
                                'message' => "商品訂購數量不能小於已出貨數量 (已出貨：{$shippedQty})",
                                'orderId' => null,
                            ];
                        }

                        // 從映射中移除，剩下的就是被刪除的項目
                        unset($oldItemsMap[$imageId]);
                    }
                }

                // 檢查被刪除的項目是否有出貨記錄
                foreach ($oldItemsMap as $deletedItem) {
                    $shippedQty = $deletedItem['oi_shipped_quantity'] ?? 0;
                    if ($shippedQty > 0) {
                        return [
                            'success' => false,
                            'message' => '無法刪除已有出貨記錄的商品項目',
                            'orderId' => null,
                        ];
                    }
                }

                // 更新訂單
                $this->update($orderId, $orderData);

                // 刪除舊的項目
                $orderItemModel->where('oi_o_id', $orderId)->delete();
            } else {
                // 新增訂單
                $orderId = $this->insert($orderData);
                if (!$orderId) {
                    return [
                        'success' => false,
                        'message' => '儲存失敗：無法建立訂單',
                        'orderId' => null,
                    ];
                }
            }

            // 新增項目
            foreach ($items as $item) {
                if (empty($item['oi_pi_id'])) {
                    continue;
                }

                $item['oi_o_id'] = $orderId;
                $orderItemModel->insert($item);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return [
                    'success' => false,
                    'message' => '儲存失敗，請稍後再試',
                    'orderId' => null,
                ];
            }

            return [
                'success' => true,
                'message' => '儲存成功',
                'orderId' => $orderId,
            ];
        } catch (\Exception $e) {
            $db->transRollback();
            return [
                'success' => false,
                'message' => '儲存失敗：' . $e->getMessage(),
                'orderId' => null,
            ];
        }
    }

    /**
     * 生成新的訂單號
     * 格式：O + 年月日 + 流水號(3位)
     * 例如：O20250127001
     * 
     * @return string
     */
    public function generateOrderNumber(): string
    {
        $date = date('Ymd');
        $prefix = 'O' . $date;

        $lastOrder = $this->like('o_number', $prefix, 'after')
            ->orderBy('o_number', 'DESC')
            ->first();

        if ($lastOrder) {
            $lastNumber = intval(substr($lastOrder['o_number'], -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * 更新訂單的出貨狀態
     * 根據訂單項目的出貨情況自動判斷
     */
    public function updateShipmentStatus($orderId)
    {
        $orderItemModel = new OrderItemModel();
        $items = $orderItemModel->where('oi_o_id', $orderId)->findAll();

        if (empty($items)) {
            return;
        }

        $totalQuantity = 0;
        $totalShipped = 0;

        foreach ($items as $item) {
            $totalQuantity += $item['oi_quantity'];
            $totalShipped += $item['oi_shipped_quantity'];
        }

        $status = 'preparing';
        if ($totalShipped >= $totalQuantity && $totalQuantity > 0) {
            $status = 'shipped';
        } elseif ($totalShipped > 0) {
            $status = 'partial';
        }

        $this->update($orderId, ['o_shipment_status' => $status]);
    }
}
