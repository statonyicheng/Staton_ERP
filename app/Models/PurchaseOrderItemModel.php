<?php

namespace App\Models;

use CodeIgniter\Model;

class PurchaseOrderItemModel extends Model
{
    protected $table = 'purchase_order_items';
    protected $primaryKey = 'poi_id';
    protected $allowedFields = [
        'poi_po_id', 'poi_p_id', 'poi_name', 'poi_spec',
        'poi_qty', 'poi_unit', 'poi_price', 'poi_amount', 'poi_sort',
    ];
    protected $useTimestamps = false;

    public function deleteByPo($poId)
    {
        return $this->where('poi_po_id', $poId)->delete();
    }
}
