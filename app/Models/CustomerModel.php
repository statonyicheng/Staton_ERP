<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'c_id';

    protected $allowedFields = [
        'c_code',
        'c_name',
        'c_manager',
        'c_phone',
        'c_fax',
        'c_email',
        'c_city',
        'c_address',
        'c_tax_id',
        'c_pm_id',
        'c_notes',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'c_created_at';
    protected $updatedField = 'c_updated_at';

    // Callbacks
    protected $allowCallbacks = true;

    public function getList($keyword = null, $page = 1)
    {
        $builder = $this->builder()
            ->select('c_id, c_code, c_name, c_phone, c_created_at, c_updated_at');

        if ($keyword) {
            $builder->groupStart()
                ->like('c_code', $keyword)
                ->orLike('c_name', $keyword)
                ->orLike('c_manager', $keyword)
                ->orLike('c_phone', $keyword)
                ->orLike('c_email', $keyword)
                ->orLike('c_city', $keyword)
                ->orLike('c_address', $keyword)
                ->orLike('c_tax_id', $keyword)
                ->orLike('c_notes', $keyword)
                ->groupEnd();
        }

        $builder->orderBy('c_created_at', 'DESC');

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
     * 產生客戶編號
     * 格式：C + 5位數流水號 (C00001, C00002, ...)
     */
    public function generateCustomerCode()
    {
        // 取得目前最大的編號
        $maxCode = $this->select('c_code')
            ->like('c_code', 'C', 'after')
            ->orderBy('c_code', 'DESC')
            ->first();

        if ($maxCode && $maxCode['c_code']) {
            // 取出數字部分並 +1
            $number = (int) substr($maxCode['c_code'], 1);
            $nextNumber = $number + 1;
        } else {
            // 第一個編號
            $nextNumber = 1;
        }

        // 格式化為 5 位數
        return 'C' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * 取得客戶詳細（含結帳方式名稱）
     */
    public function getDetailWithPayment($customerId)
    {
        return $this->select('customers.*, payment_methods.pm_name')
            ->join('payment_methods', 'payment_methods.pm_id = customers.c_pm_id', 'left')
            ->find($customerId);
    }
}
