<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductCategoryModel extends Model
{
    protected $table = 'product_categories';
    protected $primaryKey = 'pc_id';
    protected $allowedFields = [
        'pc_name',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'pc_created_at';
    protected $updatedField = 'pc_updated_at';

    protected $validationRules = [
        'pc_name' => 'required|max_length[50]',
    ];

    protected $validationMessages = [
        'pc_name' => [
            'required' => '分類名稱為必填欄位',
            'max_length' => '分類名稱長度不可超過 50 個字元',
        ],
    ];

    /**
     * 取得分頁列表資料
     */
    public function getList(?string $keyword, int $page = 1, int $perPage = 10): array
    {
        $builder = $this->select('pc_id, pc_name, pc_created_at, pc_updated_at');

        if ($keyword) {
            $builder->groupStart()
                ->like('pc_name', $keyword)
                ->groupEnd();
        }

        $builder->orderBy('pc_name', 'ASC');

        $total = $builder->countAllResults(false);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return [
            'data' => $data,
            'pager' => [
                'currentPage' => $page,
                'totalPages' => (int) max(1, ceil($total / $perPage)),
            ],
            'total' => $total,
        ];
    }


    /**
     * 獲取所有分類（用於下拉選單）
     */
    public function getAllForDropdown()
    {
        return $this->select('pc_id, pc_name')
            ->orderBy('pc_name', 'ASC')
            ->findAll();
    }

    /**
     * 檢查分類名稱是否重複
     */
    public function isNameExists($name, $excludeId = null)
    {
        $builder = $this->where('pc_name', $name);

        if ($excludeId) {
            $builder->where('pc_id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }
}
