<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'oi_id';
    protected $allowedFields = [
        'oi_o_id',
        'oi_pi_id',
        'oi_quantity',
        'oi_unit_price',
        'oi_discount',
        'oi_amount',
        'oi_shipped_quantity',
        'oi_color',
        'oi_size',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'oi_created_at';
    protected $updatedField = 'oi_updated_at';

    // 獲取訂單的所有項目（透過 product_images 關聯）
    public function getItemsByOrderId($orderId)
    {
        return $this->select('order_items.*, 
                              product_images.pi_name, 
                              product_images.pi_p_id,
                              products.p_id,
                              products.p_name, 
                              products.p_code, 
                              products.p_standard_price,
                              product_categories.pc_name')
            ->join('product_images', 'product_images.pi_id = order_items.oi_pi_id', 'left')
            ->join('products', 'products.p_id = product_images.pi_p_id', 'left')
            ->join('product_categories', 'product_categories.pc_id = products.p_pc_id', 'left')
            ->where('oi_o_id', $orderId)
            ->orderBy('oi_id', 'ASC')
            ->findAll();
    }

    // 計算訂單項目的總金額
    public function calculateOrderTotal($orderId)
    {
        $items = $this->where('oi_o_id', $orderId)->findAll();

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['oi_amount'];
        }

        return $subtotal;
    }

    // 批量更新項目金額
    public function updateItemAmounts($orderId)
    {
        $items = $this->where('oi_o_id', $orderId)->findAll();

        foreach ($items as $item) {
            $amount = $item['oi_quantity'] * $item['oi_unit_price'] * (1 - $item['oi_discount'] / 100);

            $this->update($item['oi_id'], [
                'oi_amount' => $amount
            ]);
        }
    }
}
