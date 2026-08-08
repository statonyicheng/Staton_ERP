<?php

namespace App\Models;

use CodeIgniter\Model;

class QuoteItemModel extends Model
{
    protected $table = 'quote_items';
    protected $primaryKey = 'qi_id';
    protected $allowedFields = [
        'qi_q_id',
        'qi_p_id',
        'qi_supplier',
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
        'qi_p_id' => 'required|integer',
        'qi_quantity' => 'required|integer|greater_than[0]',
        'qi_unit_price' => 'permit_empty|integer',
        'qi_discount' => 'permit_empty|decimal',
    ];

    protected $validationMessages = [
        'qi_q_id' => [
            'required' => '報價單為必填',
        ],
        'qi_p_id' => [
            'required' => '商品為必填',
        ],
        'qi_quantity' => [
            'required' => '數量為必填',
            'greater_than' => '數量必須大於 0',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 取得報價單明細及商品資料。
     *
     * 品項的單位是「商品」（qi_p_id）；圖片只是顯示用，取該商品的第一張圖。
     */
    public function getItemsWithProduct($quoteId)
    {
        return $this->select('quote_items.*,
                              products.p_id,
                              products.p_name,
                              products.p_code,
                              products.p_image,
                              products.p_specifications,
                              products.p_standard_price,
                              product_categories.pc_name,
                              pi.pi_name,
                              pi.pi_p_id')
            ->join('products', 'products.p_id = quote_items.qi_p_id', 'left')
            ->join('product_categories', 'product_categories.pc_id = products.p_pc_id', 'left')
            ->join('(SELECT pi_p_id, pi_name FROM product_images WHERE pi_id IN (SELECT MIN(pi_id) FROM product_images GROUP BY pi_p_id)) pi', 'pi.pi_p_id = products.p_id', 'left')
            ->where('qi_q_id', $quoteId)
            ->orderBy('qi_id', 'ASC')
            ->findAll();
    }
}
