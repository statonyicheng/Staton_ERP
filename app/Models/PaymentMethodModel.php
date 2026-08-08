<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentMethodModel extends Model
{
    protected $table = 'payment_methods';
    protected $primaryKey = 'pm_id';
    protected $allowedFields = [
        'pm_name',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField = 'pm_created_at';
    protected $updatedField = 'pm_updated_at';

    // Validation
    protected $validationRules = [
        'pm_name' => 'required|max_length[100]|is_unique[payment_methods.pm_name,pm_id,{pm_id}]',
    ];

    protected $validationMessages = [
        'pm_name' => [
            'required' => '結帳方式名稱為必填',
            'max_length' => '結帳方式名稱不能超過100個字元',
            'is_unique' => '此結帳方式名稱已存在',
        ],
    ];

    /**
     * 取得所有結帳方式（用於下拉選單）
     */
    public function getAllForDropdown()
    {
        return $this->select('pm_id, pm_name')
            ->orderBy('pm_name', 'ASC')
            ->findAll();
    }

    /**
     * 取得分頁列表
     */
    public function getList($keyword = null, $page = 1)
    {
        $builder = $this->builder()
            ->select('pm_id, pm_name, pm_created_at, pm_updated_at');

        if ($keyword) {
            $builder->like('pm_name', $keyword);
        }

        $builder->orderBy('pm_created_at', 'DESC');

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
     * 檢查結帳方式是否被客戶使用
     */
    public function isUsed($pmId)
    {
        return $this->db->table('customers')
            ->where('c_pm_id', $pmId)
            ->countAllResults() > 0;
    }
}
