<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'oi_id';
    protected $allowedFields = [
        'oi_o_id',
        'oi_p_id',
        'oi_quantity',
        'oi_unit_price',
        'oi_discount',
        'oi_amount',
        'oi_shipped_quantity',
        'oi_supplier',
        'oi_color',
        'oi_size',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'oi_created_at';
    protected $updatedField = 'oi_updated_at';

    /**
     * 獲取訂單的所有項目。
     *
     * 品項的單位是「商品」（oi_p_id），庫存／成本／出貨都以商品為準；
     * 圖片只是顯示用，取該商品的第一張圖（沒有圖也不影響單據成立）。
     */
    public function getItemsByOrderId($orderId)
    {
        return $this->select('order_items.*,
                              products.p_id,
                              products.p_name,
                              products.p_code,
                              products.p_image,
                              products.p_standard_price,
                              product_categories.pc_name,
                              pi.pi_name,
                              pi.pi_p_id')
            ->join('products', 'products.p_id = order_items.oi_p_id', 'left')
            ->join('product_categories', 'product_categories.pc_id = products.p_pc_id', 'left')
            ->join('(SELECT pi_p_id, pi_name FROM product_images WHERE pi_id IN (SELECT MIN(pi_id) FROM product_images GROUP BY pi_p_id)) pi', 'pi.pi_p_id = products.p_id', 'left')
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
