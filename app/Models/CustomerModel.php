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
     * 產生客戶編號 —— **客戶編號就是統一編號**。
     *
     * 開單、查詢直接打統編就找得到，不必再記一組系統流水號；
     * 統編由政府配發、本來就唯一，拿來當編號最省事。
     *
     * 還沒有統編的客戶回傳 null（編號留空，畫面顯示「待補統編」）。
     * 不發流水號的原因：發了就等於憑空造出一組永遠不會用到的號碼，
     * 之後統編補上還要再換一次，中間開過的單據就對不回來。
     * 統編補上時（客戶表單存檔或 `erp:sync-customer-code`）會自動帶入。
     *
     * 註：`c_code` 有 UNIQUE 索引，所以「沒統編」只能留空（NULL 可以重複），
     * 不能全部塞 00000000 —— 第二筆就會被資料庫擋下來。
     */
    public function generateCustomerCode(?string $taxId = null): ?string
    {
        $taxId = trim((string) $taxId);

        // 統編必須是 8 位數字，且不能是 CRM 資料夾用的佔位值 00000000
        if (preg_match('/^\d{8}$/', $taxId) && $taxId !== '00000000'
            && $this->where('c_code', $taxId)->countAllResults() === 0) {
            return $taxId;
        }

        return null;
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
