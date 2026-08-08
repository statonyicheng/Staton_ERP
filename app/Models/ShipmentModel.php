<?php

namespace App\Models;

use App\Models\AuditedModel;

class ShipmentModel extends AuditedModel
{
    protected $table = 'shipments';
    protected $primaryKey = 's_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        's_o_id',
        's_number',
        's_date',
        's_status',
        's_after_sales_status',
        's_notes',
    ];

    // 出貨狀態
    public const STATUS_FACTORY_ORDERED = 1; // 工廠已下單
    public const STATUS_FACTORY_SHIPPED = 2; // 工廠已發貨
    public const STATUS_LAND_WAREHOUSE = 3;  // 已到陸倉
    public const STATUS_CUSTOMS_CLEARED = 4; // 已清關待收尾款
    public const STATUS_FINAL_PAYMENT = 5;   // 已收尾款
    public const STATUS_SHIPPED = 6;         // 已發貨
    public const STATUS_ARRIVED = 7;         // 已到貨

    // 售後狀態
    public const AFTERSALES_NORMAL = 1;      // 正常
    public const AFTERSALES_PROCESSING = 2;  // 售後處理中
    public const AFTERSALES_COMPLETED = 3;   // 售後完成

    // 出貨狀態映射
    public static $statusMap = [
        self::STATUS_FACTORY_ORDERED => '工廠已下單',
        self::STATUS_FACTORY_SHIPPED => '工廠已發貨',
        self::STATUS_LAND_WAREHOUSE => '已到陸倉',
        self::STATUS_CUSTOMS_CLEARED => '已清關待收尾款',
        self::STATUS_FINAL_PAYMENT => '已收尾款',
        self::STATUS_SHIPPED => '已發貨',
        self::STATUS_ARRIVED => '已到貨',
    ];

    // 售後狀態映射
    public static $afterSalesStatusMap = [
        self::AFTERSALES_NORMAL => '正常',
        self::AFTERSALES_PROCESSING => '售後處理中',
        self::AFTERSALES_COMPLETED => '售後完成',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 's_created_at';
    protected $updatedField = 's_updated_at';

    // Validation
    protected $validationRules = [
        's_o_id' => 'required|integer',
        's_number' => 'required|max_length[50]',
        's_date' => 'required|valid_date',
        's_status' => 'permit_empty|integer',
        's_after_sales_status' => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        's_o_id' => [
            'required' => '訂單為必填',
        ],
        's_number' => [
            'required' => '出貨單號為必填',
        ],
        's_date' => [
            'required' => '出貨日期為必填',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;

    /**
     * 取得出貨單及訂單資料
     */
    public function getWithOrder($id = null)
    {
        $builder = $this->select('shipments.*, orders.o_number as order_number, orders.o_date')
            ->join('orders', 'orders.o_id = shipments.s_o_id', 'left');

        if ($id !== null) {
            return $builder->where('shipments.s_id', $id)->first();
        }

        return $builder->findAll();
    }

    /**
     * 獲取出貨單列表（支援搜尋、訂單篩選和分頁）
     */
    public function getList($keyword = null, $page = 1, $orderId = null)
    {
        $builder = $this->builder()
            ->select('shipments.s_id, shipments.s_number, shipments.s_date, shipments.s_status, shipments.s_after_sales_status, shipments.s_notes, shipments.s_created_at, shipments.s_o_id, orders.o_number, customers.c_name')
            ->join('orders', 'orders.o_id = shipments.s_o_id')
            ->join('customers', 'customers.c_id = orders.o_c_id');

        // 訂單篩選
        if ($orderId) {
            $builder->where('shipments.s_o_id', $orderId);
        }

        // 關鍵字搜尋
        if ($keyword) {
            $builder->groupStart()
                ->like('shipments.s_number', $keyword)
                ->orLike('orders.o_number', $keyword)
                ->orLike('customers.c_name', $keyword)
                ->groupEnd();
        }

        $builder->orderBy('shipments.s_created_at', 'DESC');

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
     * 取得訂單的所有出貨單
     */
    public function getByOrder($orderId)
    {
        return $this->where('s_o_id', $orderId)->findAll();
    }

    /**
     * 取得出貨單詳細資料（包含訂單、客戶、聯絡人、出貨項目）
     */
    public function getShipmentDetails($id)
    {
        // 1. 取得出貨單基本資料（含訂單、客戶、聯絡人）
        $shipment = $this->select('shipments.*, orders.o_number, orders.o_date as order_date, customers.c_name, customers.c_tax_id, customer_contacts.cc_name, customer_contacts.cc_phone')
            ->join('orders', 'orders.o_id = shipments.s_o_id')
            ->join('customers', 'customers.c_id = orders.o_c_id')
            ->join('customer_contacts', 'customer_contacts.cc_id = orders.o_cc_id', 'left')
            ->where('shipments.s_id', $id)
            ->first();

        if (!$shipment) {
            return null;
        }

        // 2. 取得出貨項目（關聯訂單項目與產品）
        $shipmentItemModel = new ShipmentItemModel();
        $items = $shipmentItemModel->select('shipment_items.*, order_items.oi_quantity as order_quantity, order_items.oi_shipped_quantity as total_shipped, products.p_name, products.p_image, products.p_code, product_categories.pc_name')
            ->join('order_items', 'order_items.oi_id = shipment_items.si_oi_id')
            ->join('products', 'products.p_id = order_items.oi_p_id')
            ->join('product_categories', 'product_categories.pc_id = products.p_pc_id', 'left')
            ->where('shipment_items.si_s_id', $id)
            ->findAll();

        // 補上樣式/顏色/尺寸等資訊 (這些在 order_items)
        // 再次 join order_items 取得詳細規格，或者直接在上方的 select 加入
        // 為了確保資訊完整，我調整上方查詢加入 order_items 的規格欄位

        // 重新查詢 items 以包含規格
        $items = $shipmentItemModel->select('
                shipment_items.*, 
                order_items.oi_quantity as order_quantity, 
                order_items.oi_shipped_quantity as total_shipped,
                order_items.oi_style,
                order_items.oi_color,
                order_items.oi_size,
                order_items.oi_supplier,
                products.p_name, 
                products.p_image, 
                products.p_code, 
                product_categories.pc_name
            ')
            ->join('order_items', 'order_items.oi_id = shipment_items.si_oi_id')
            ->join('products', 'products.p_id = order_items.oi_p_id')
            ->join('product_categories', 'product_categories.pc_id = products.p_pc_id', 'left')
            ->where('shipment_items.si_s_id', $id)
            ->findAll();

        $shipment['items'] = $items;

        return $shipment;
    }

    /**
     * 出貨扣庫存 / 退貨回沖：依訂單項目對應之商品,於預設倉庫產生
     * 銷貨出庫(出)或退貨回沖(入)異動,即時更新在庫量。
     */
    private function postSalesStock($db, $oiId, int $qty, ?string $date, ?string $refNo, bool $isReturn, ?int $sId = null): void
    {
        if ($qty <= 0) return;
        $oi = $db->table('order_items')->select('oi_p_id')->where('oi_id', $oiId)->get()->getRowArray();
        $pId = $oi ? (int) ($oi['oi_p_id'] ?? 0) : 0;
        if (!$pId) return;
        // 預設出貨倉：第一個啟用倉庫
        $w = $db->table('warehouses')->select('w_id')->where('w_is_active', 1)->orderBy('w_id', 'ASC')->get()->getRowArray();
        $wId = $w ? (int) $w['w_id'] : 0;
        if (!$wId) return;
        (new StockMovementModel())->apply([
            'sm_date' => $date ?: date('Y-m-d'),
            'sm_type' => $isReturn ? '退貨入庫' : '銷貨出庫',
            'sm_direction' => $isReturn ? '入' : '出',
            'sm_p_id' => $pId, 'sm_w_id' => $wId, 'sm_qty' => $qty,
            'sm_ref_type' => '出貨單', 'sm_ref_id' => $sId, 'sm_ref_no' => $refNo,
            'sm_note' => $isReturn ? '出貨回沖入庫' : '銷貨出庫',
        ]);
    }

    /**
     * 儲存出貨單及其項目
     * 同時更新訂單項目的已出貨數量和訂單的出貨狀態
     */
    public function saveShipmentWithItems(array $shipmentData, array $items): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $shipmentItemModel = new ShipmentItemModel();
            $orderModel = new OrderModel();

            $shipmentId = $shipmentData['s_id'] ?? null;
            $orderId = $shipmentData['s_o_id'];

            // 1. 儲存出貨單
            if ($shipmentId) {
                $this->update($shipmentId, $shipmentData);

                // 編輯模式：先回復之前的出貨數量，再重新扣除
                $oldItems = $shipmentItemModel->where('si_s_id', $shipmentId)->findAll();
                foreach ($oldItems as $oldItem) {
                    $db->table('order_items')
                        ->where('oi_id', $oldItem['si_oi_id'])
                        ->increment('oi_shipped_quantity', -$oldItem['si_quantity']);
                    // 回沖舊出貨的庫存(入)
                    $this->postSalesStock($db, $oldItem['si_oi_id'], (int) $oldItem['si_quantity'], $shipmentData['s_date'] ?? null, $shipmentData['s_number'] ?? null, true, (int) $shipmentId);
                }
                $shipmentItemModel->where('si_s_id', $shipmentId)->delete();
            } else {
                $shipmentId = $this->insert($shipmentData);
            }

            // 2. 儲存出貨明細並更新訂單項目已出貨數量
            foreach ($items as $item) {
                if (empty($item['si_quantity']) || $item['si_quantity'] <= 0) {
                    continue;
                }

                $item['si_s_id'] = $shipmentId;
                $shipmentItemModel->insert($item);

                // 更新訂單項目的已出貨數量
                $db->table('order_items')
                    ->where('oi_id', $item['si_oi_id'])
                    ->increment('oi_shipped_quantity', $item['si_quantity']);

                // 銷貨出庫:扣減庫存
                $this->postSalesStock($db, $item['si_oi_id'], (int) $item['si_quantity'], $shipmentData['s_date'] ?? null, $shipmentData['s_number'] ?? null, false, (int) $shipmentId);
            }

            // 3. 更新訂單出貨狀態
            $orderModel->updateShipmentStatus($orderId);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return [
                    'success' => false,
                    'message' => '儲存失敗，請稍後再試',
                ];
            }

            return [
                'success' => true,
                'message' => '儲存成功',
                'shipmentId' => $shipmentId,
            ];
        } catch (\Exception $e) {
            $db->transRollback();
            return [
                'success' => false,
                'message' => '儲存失敗：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 生成新的出貨單號
     * 格式：S + 年月日 + 流水號(3位)
     * 例如：S20251128001
     * 
     * @return string
     */
    public function generateShipmentNumber(): string
    {
        $date = date('Ymd');
        $prefix = 'S' . $date;

        $lastShipment = $this->like('s_number', $prefix, 'after')
            ->orderBy('s_number', 'DESC')
            ->first();

        if ($lastShipment) {
            $lastNumber = intval(substr($lastShipment['s_number'], -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * 刪除出貨單
     * 同時回復訂單項目的已出貨數量和訂單的出貨狀態
     */
    public function deleteShipment($id)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $shipment = $this->find($id);
            if (!$shipment) {
                throw new \Exception('出貨單不存在');
            }

            $shipmentItemModel = new ShipmentItemModel();
            $orderModel = new OrderModel();

            // 1. 回復已出貨數量
            $items = $shipmentItemModel->where('si_s_id', $id)->findAll();
            foreach ($items as $item) {
                $db->table('order_items')
                    ->where('oi_id', $item['si_oi_id'])
                    ->increment('oi_shipped_quantity', -$item['si_quantity']);
                // 回沖庫存(入)
                $this->postSalesStock($db, $item['si_oi_id'], (int) $item['si_quantity'], $shipment['s_date'] ?? null, $shipment['s_number'] ?? null, true, (int) $id);
            }

            // 2. 刪除出貨單
            $this->delete($id);

            // 3. 更新訂單出貨狀態
            $orderModel->updateShipmentStatus($shipment['s_o_id']);

            $db->transComplete();
            return true;
        } catch (\Exception $e) {
            $db->transRollback();
            return false;
        }
    }
}
