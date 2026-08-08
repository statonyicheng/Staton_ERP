<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'p_id';
    protected $allowedFields = [
        'p_code',
        'p_pc_id',
        'p_name',
        'p_color',
        'p_size',
        'p_specifications',
        'p_standard_price',
        'p_cost_price',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField = 'p_created_at';
    protected $updatedField = 'p_updated_at';

    // Validation
    protected $validationRules = [
        'p_name' => 'required|max_length[50]',
        'p_standard_price' => 'required|decimal',
    ];

    protected $validationMessages = [
        'p_name' => [
            'required' => '產品名稱為必填欄位',
            'max_length' => '產品名稱長度不可超過 50 個字元',
        ],
        'p_standard_price' => [
            'required' => '售價為必填欄位',
            'decimal' => '售價必須是有效的數字',
        ],
    ];

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['generateProductCode'];

    /**
     * 自動產生產品編號（格式：P000001, P000002, ...）
     */
    protected function generateProductCode(array $data)
    {
        if (!isset($data['data']['p_code']) || empty($data['data']['p_code'])) {
            // 查詢目前最大的編號（使用原生 SQL 排序）
            $builder = $this->builder();
            $maxCode = $builder->select('p_code')
                ->orderBy('p_code', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            if ($maxCode && !empty($maxCode['p_code'])) {
                // 提取數字部分並 +1
                $number = (int)substr($maxCode['p_code'], 1) + 1;
            } else {
                // 第一個產品編號
                $number = 1;
            }

            // 格式化為6位數字
            $data['data']['p_code'] = 'P' . str_pad($number, 6, '0', STR_PAD_LEFT);
        }

        return $data;
    }

    /**
     * 獲取產品列表（包含分類資訊和第一張圖片）
     */
    public function getProductsWithCategory($keyword = null, $page = 1, $perPage = 10)
    {
        $builder = $this->builder('products p')
            ->select('p.*, pc.pc_name, pi.pi_name as first_image')
            ->join('product_categories pc', 'pc.pc_id = p.p_pc_id', 'left')
            ->join('(SELECT pi_p_id, pi_name FROM product_images WHERE pi_id IN (SELECT MIN(pi_id) FROM product_images GROUP BY pi_p_id)) pi', 'pi.pi_p_id = p.p_id', 'left');

        if ($keyword) {
            $builder->groupStart()
                ->like('p.p_code', $keyword)
                ->orLike('p.p_name', $keyword)
                ->orLike('pc.pc_name', $keyword)
                ->groupEnd();
        }

        $builder->orderBy('p.p_created_at', 'DESC');

        $total = $builder->countAllResults(false);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return [
            'data' => $data,
            'total' => $total,
            'totalPages' => ceil($total / $perPage),
            'currentPage' => $page
        ];
    }

    /**
     * 根據ID獲取產品（包含分類資訊）
     */
    public function getProductWithCategory($id)
    {
        return $this->builder('products p')
            ->select('p.*, pc.pc_name')
            ->join('product_categories pc', 'pc.pc_id = p.p_pc_id', 'left')
            ->where('p.p_id', $id)
            ->get()
            ->getRowArray();
    }
}
