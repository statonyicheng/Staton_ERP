<?php

namespace App\Models;

use CodeIgniter\Model;

class PurchaseRequisitionModel extends Model
{
    protected $table = 'purchase_requisitions';
    protected $primaryKey = 'pr_id';
    protected $allowedFields = [
        'pr_no', 'pr_date', 'pr_dept', 'pr_name', 'pr_spec',
        'pr_qty', 'pr_unit', 'pr_need_date', 'pr_status', 'pr_note',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'pr_created_at';
    protected $updatedField = 'pr_updated_at';

    protected $validationRules = [
        'pr_name' => 'required|max_length[150]',
    ];
    protected $validationMessages = [
        'pr_name' => ['required' => '品名為必填'],
    ];

    public const STATUSES = ['待處理', '已轉採購', '已取消'];

    public function getList($keyword = null, $page = 1, $status = null)
    {
        $builder = $this->builder();
        if ($keyword) {
            $builder->groupStart()
                ->like('pr_name', $keyword)->orLike('pr_no', $keyword)->orLike('pr_dept', $keyword)
                ->groupEnd();
        }
        if ($status) $builder->where('pr_status', $status);
        $builder->orderBy('pr_date', 'DESC')->orderBy('pr_id', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = 12;
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    public function generateNo(?string $date = null): string
    {
        // 原子取號，多人同時開單也不會重號（見 App\Libraries\DocumentNumber）
        return \App\Libraries\DocumentNumber::daily('PR', $date);
    }
}
