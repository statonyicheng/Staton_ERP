<?php

namespace App\Models;

use App\Models\AuditedModel;

class OrderModel extends AuditedModel
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
        $perPage = \App\Libraries\PageSize::get(10);
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
    public function getByCustomer(int $customerId, int $page = 1, ?int $perPage = null): array
    {
        $perPage = $perPage ?? \App\Libraries\PageSize::get(10);
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
                'oi_p_id' => $item['qi_p_id'],
                'oi_quantity' => $item['qi_quantity'],
                'oi_unit_price' => $item['qi_unit_price'],
                'oi_discount' => $item['qi_discount'],
                'oi_amount' => $item['qi_quantity'] * $item['qi_unit_price'] * (1 - $item['qi_discount'] / 100),
                'oi_supplier' => $item['qi_supplier'] ?? null,
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

    /**
     * 儲存訂單及其項目。
     *
     * 品項以「商品」（oi_p_id）為單位；沒有選商品的列視為空白列直接略過。
     * 所有檢查都在開啟交易「之前」做完，避免中途 return 留下懸空的交易
     * （懸空交易會讓同一連線後續的寫入被一起回滾）。
     * 編輯時採逐筆比對更新，不整批刪除重建 —— 否則已出貨數量會歸零，
     * 出貨明細（shipment_items.si_oi_id）也會跟著失聯。
     */
    public function saveOrderWithItems(array $orderData, array $items): array
    {
        $orderItemModel = new OrderItemModel();
        $orderId = $orderData['o_id'] ?? null;
        $orderData['o_cc_id'] = ($orderData['o_cc_id'] ?? '') === '' ? null : $orderData['o_cc_id'];

        // 只留下真的有選商品的列
        $items = array_values(array_filter(
            $items ?: [],
            static fn ($item) => ! empty($item['oi_p_id']) && ! empty($item['oi_quantity'])
        ));

        if ($items === []) {
            return $this->saveFailed('至少需要一個有效的商品項目（請選擇商品並填入數量）');
        }

        if (! $this->validate($orderData)) {
            return $this->saveFailed('驗證失敗：' . implode(', ', $this->errors()));
        }

        // ---- 編輯模式：先確認這次修改不會弄丟已出貨的紀錄 ----
        $oldItems = [];
        if ($orderId) {
            foreach ($orderItemModel->where('oi_o_id', $orderId)->findAll() as $oldItem) {
                $oldItems[(int) $oldItem['oi_id']] = $oldItem;
            }

            $keptIds = [];
            foreach ($items as $item) {
                $itemId = (int) ($item['oi_id'] ?? 0);
                if (! isset($oldItems[$itemId])) {
                    continue;   // 新增的列
                }

                $keptIds[] = $itemId;
                $shippedQty = (int) ($oldItems[$itemId]['oi_shipped_quantity'] ?? 0);

                if ($shippedQty > 0 && (int) $item['oi_p_id'] !== (int) $oldItems[$itemId]['oi_p_id']) {
                    return $this->saveFailed('已有出貨記錄的品項不可更換商品（請改為新增一列）');
                }

                if ((int) $item['oi_quantity'] < $shippedQty) {
                    return $this->saveFailed("商品訂購數量不能小於已出貨數量 (已出貨：{$shippedQty})");
                }
            }

            foreach ($oldItems as $itemId => $oldItem) {
                if (! in_array($itemId, $keptIds, true) && (int) ($oldItem['oi_shipped_quantity'] ?? 0) > 0) {
                    return $this->saveFailed('無法刪除已有出貨記錄的商品項目');
                }
            }
        }

        // ---- 檢查都過了才開交易 ----
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            if ($orderId) {
                $this->update($orderId, $orderData);
            } else {
                $orderId = $this->insert($orderData);
                if (! $orderId) {
                    throw new \RuntimeException('無法建立訂單');
                }
            }

            $keptIds = [];
            foreach ($items as $item) {
                $itemId = (int) ($item['oi_id'] ?? 0);
                unset($item['oi_id']);
                $item['oi_o_id'] = $orderId;

                if ($itemId && isset($oldItems[$itemId])) {
                    // 既有品項：只更新內容，保留 oi_id 與已出貨數量
                    $orderItemModel->update($itemId, $item);
                    $keptIds[] = $itemId;
                } else {
                    $orderItemModel->insert($item);
                    $keptIds[] = (int) $orderItemModel->getInsertID();
                }
            }

            // 這次沒送上來的舊品項才刪除（上面已確認它們都沒有出貨紀錄）
            $removedIds = array_diff(array_keys($oldItems), $keptIds);
            if ($removedIds !== []) {
                $orderItemModel->whereIn('oi_id', $removedIds)->delete();
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->saveFailed('儲存失敗，請稍後再試');
            }

            return [
                'success' => true,
                'message' => '儲存成功',
                'orderId' => $orderId,
            ];
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->saveFailed('儲存失敗：' . $e->getMessage());
        }
    }

    private function saveFailed(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'orderId' => null,
        ];
    }

    /**
     * 生成新的訂單號，格式 O20260808-001。
     *
     * 走 DocumentNumber 的原子計數器，多人同一秒開單也不會拿到同一個號
     * （原本的「查最大號再加一」在兩人同時操作時會安靜重號）。
     */
    public function generateOrderNumber(): string
    {
        return \App\Libraries\DocumentNumber::daily('O');
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
