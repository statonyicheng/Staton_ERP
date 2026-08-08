<?php

namespace App\Models;

use CodeIgniter\Model;

class QuoteItemModel extends Model
{
    protected $table = 'quote_items';
    protected $primaryKey = 'qi_id';
    protected $allowedFields = [
        'qi_q_id',
        'qi_pi_id',
        'qi_color',
        'qi_size',
        'qi_quantity',
        'qi_unit_price',
        'qi_discount',
        'qi_amount',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'qi_created_at';
    protected $updatedField = 'qi_updated_at';

    // Validation
    protected $validationRules = [
        'qi_q_id' => 'required|integer',
        'qi_pi_id' => 'required|integer',
        'qi_quantity' => 'required|integer|greater_than[0]',
        'qi_unit_price' => 'permit_empty|integer',
        'qi_discount' => 'permit_empty|decimal',
    ];

    protected $validationMessages = [
        'qi_q_id' => [
            'required' => '報價單為必填',
        ],
        'qi_pi_id' => [
            'required' => '商品圖片為必填',
        ],
        'qi_quantity' => [
            'required' => '數量為必填',
            'greater_than' => '數量必須大於 0',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 取得報價單明細及商品資料（透過 product_images 關聯）
     */
    public function getItemsWithProduct($quoteId)
    {
        return $this->select('quote_items.*, 
                              product_images.pi_name, 
                              product_images.pi_p_id,
                              products.p_id,
                              products.p_name, 
                              products.p_code, 
                              products.p_specifications, 
                              products.p_standard_price,
                              product_categories.pc_name')
            ->join('product_images', 'product_images.pi_id = quote_items.qi_pi_id', 'left')
            ->join('products', 'products.p_id = product_images.pi_p_id', 'left')
            ->join('product_categories', 'product_categories.pc_id = products.p_pc_id', 'left')
            ->where('qi_q_id', $quoteId)
            ->findAll();
    }
}
