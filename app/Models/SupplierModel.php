<?php

namespace App\Models;

use App\Models\AuditedModel;

class SupplierModel extends AuditedModel
{
    protected $table = 'suppliers';
    protected $primaryKey = 's_id';
    protected $allowedFields = [
        's_code', 's_name', 's_tax_id', 's_contact', 's_phone',
        's_fax', 's_email', 's_address', 's_pm_id', 's_note',
    ];

    protected $useTimestamps = true;
    protected $createdField = 's_created_at';
    protected $updatedField = 's_updated_at';

    protected $validationRules = [
        's_name' => 'required|max_length[100]',
        's_tax_id' => 'permit_empty|max_length[20]',
        's_email' => 'permit_empty|valid_email',
    ];
    protected $validationMessages = [
        's_name' => ['required' => '廠商名稱為必填', 'max_length' => '廠商名稱不能超過100個字元'],
        's_email' => ['valid_email' => '電子郵件格式不正確'],
    ];

    public function getAllForDropdown()
    {
        return $this->select('s_id, s_code, s_name')->orderBy('s_name', 'ASC')->findAll();
    }

    public function getList($keyword = null, $page = 1)
    {
        $builder = $this->builder();
        if ($keyword) {
            $builder->groupStart()
                ->like('s_name', $keyword)
                ->orLike('s_code', $keyword)
                ->orLike('s_contact', $keyword)
                ->orLike('s_tax_id', $keyword)
                ->groupEnd();
        }
        $builder->orderBy('s_created_at', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = 10;
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    /**
     * 自動產生廠商編號 SUP0001
     */
    public function generateCode(): string
    {
        $last = $this->select('s_code')->like('s_code', 'SUP', 'after')
            ->orderBy('s_id', 'DESC')->first();
        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last['s_code'], $m)) {
            $next = (int) $m[1] + 1;
        }
        return 'SUP' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
