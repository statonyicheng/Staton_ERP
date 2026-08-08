<?php

namespace App\Models;

use App\Models\AuditedModel;

class WorkOrderModel extends AuditedModel
{
    protected $table = 'work_orders';
    protected $primaryKey = 'wo_id';
    protected $allowedFields = [
        'wo_no', 'wo_p_id', 'wo_qty', 'wo_date', 'wo_due_date', 'wo_w_id', 'wo_status', 'wo_note',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'wo_created_at';
    protected $updatedField = 'wo_updated_at';

    public const STATUSES = ['未完工', '已完工', '取消'];

    public function getList($keyword = null, $page = 1, $status = null)
    {
        $builder = $this->db->table('work_orders wo')
            ->select('wo.*, p.p_code, p.p_name, w.w_name')
            ->join('products p', 'p.p_id = wo.wo_p_id', 'left')
            ->join('warehouses w', 'w.w_id = wo.wo_w_id', 'left');
        if ($keyword) {
            $builder->groupStart()->like('wo.wo_no', $keyword)->orLike('p.p_name', $keyword)->groupEnd();
        }
        if ($status) $builder->where('wo.wo_status', $status);
        $builder->orderBy('wo.wo_date', 'DESC')->orderBy('wo.wo_id', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = 12;
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    public function getWithProduct($id)
    {
        return $this->db->table('work_orders wo')
            ->select('wo.*, p.p_code, p.p_name, w.w_name')
            ->join('products p', 'p.p_id = wo.wo_p_id', 'left')
            ->join('warehouses w', 'w.w_id = wo.wo_w_id', 'left')
            ->where('wo.wo_id', $id)->get()->getRowArray();
    }

    public function generateNo(?string $date = null): string
    {
        // 原子取號，多人同時開單也不會重號（見 App\Libraries\DocumentNumber）
        return \App\Libraries\DocumentNumber::daily('WO', $date);
    }
}
