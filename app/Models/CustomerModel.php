<?php

namespace App\Models;

use App\Models\AuditedModel;

class CustomerModel extends AuditedModel
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
        $perPage = \App\Libraries\PageSize::get(10);
        $totalPages = ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return [
            'data' => $data,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * 產生客戶編號。
     *
     * **有統一編號時，客戶編號就是統一編號** —— 開單、查詢直接打統編就找得到，
     * 不必再記一組系統流水號。統編由政府配發、本來就唯一，拿來當編號最省事。
     *
     * 沒有統編（個人客戶、或統編還沒拿到）才退回流水號 C00001、C00002…；
     * 日後補上統編時，`erp:sync-customer-code` 會把編號一起換過來。
     */
    public function generateCustomerCode(?string $taxId = null)
    {
        $taxId = trim((string) $taxId);

        // 統編必須是 8 位數字，且不能是 CRM 資料夾用的佔位值 00000000
        if (preg_match('/^\d{8}$/', $taxId) && $taxId !== '00000000'
            && $this->where('c_code', $taxId)->countAllResults() === 0) {
            return $taxId;
        }

        // 沒統編才用流水號（只看 C 開頭的，統編當編號的不算在內）
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
