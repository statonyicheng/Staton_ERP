<?php

namespace App\Models;

use CodeIgniter\Model;

class ShipmentItemModel extends Model
{
    protected $table = 'shipment_items';
    protected $primaryKey = 'si_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'si_s_id',
        'si_oi_id',
        'si_quantity',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'si_created_at';
    protected $updatedField = 'si_updated_at';

    // Validation
    protected $validationRules = [
        'si_s_id' => 'required|integer',
        'si_oi_id' => 'required|integer',
        'si_quantity' => 'required|integer|greater_than[0]',
    ];

    protected $validationMessages = [
        'si_s_id' => [
            'required' => '出貨單為必填',
        ],
        'si_oi_id' => [
            'required' => '訂單明細為必填',
        ],
        'si_quantity' => [
            'required' => '出貨數量為必填',
            'greater_than' => '出貨數量必須大於 0',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * 取得出貨單的所有項目
     */
    public function getItemsByShipmentId($shipmentId)
    {
        return $this->where('si_s_id', $shipmentId)->findAll();
    }

    /**
     * 取得出貨明細及訂單明細、商品資料
     */
    public function getItemsWithDetails($shipmentId)
    {
        return $this->select('
                shipment_items.*,
                order_items.oi_quantity,
                order_items.oi_shipped_quantity,
                order_items.oi_unit_price,
                products.p_name,
                products.p_code,
                products.p_unit
            ')
            ->join('order_items', 'order_items.oi_id = shipment_items.si_oi_id', 'left')
            ->join('products', 'products.p_id = order_items.oi_p_id', 'left')
            ->where('si_s_id', $shipmentId)
            ->findAll();
    }

    /**
     * 取得訂單明細的已出貨總數
     */
    public function getTotalShippedByOrderItem($orderItemId)
    {
        $result = $this->selectSum('si_quantity')
                       ->where('si_oi_id', $orderItemId)
                       ->first();
        
        return $result['si_quantity'] ?? 0;
    }
}

