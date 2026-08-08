<?php

namespace App\Models;

use App\Models\AuditedModel;

class InvoiceModel extends AuditedModel
{
    protected $table = 'invoices';
    protected $primaryKey = 'inv_id';
    protected $allowedFields = [
        'inv_number', 'inv_date', 'inv_c_id', 'inv_buyer', 'inv_buyer_tax', 'inv_ref_no',
        'inv_amount', 'inv_tax', 'inv_total', 'inv_status', 'inv_note',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'inv_created_at';
    protected $updatedField = 'inv_updated_at';

    public function getList($keyword = null, $page = 1, $status = null)
    {
        $builder = $this->db->table('invoices inv')->select('inv.*, c.c_name')
            ->join('customers c', 'c.c_id = inv.inv_c_id', 'left');
        if ($keyword) {
            $builder->groupStart()->like('inv.inv_number', $keyword)->orLike('inv.inv_buyer', $keyword)->orLike('c.c_name', $keyword)->groupEnd();
        }
        if ($status) $builder->where('inv.inv_status', $status);
        $builder->orderBy('inv.inv_date', 'DESC')->orderBy('inv.inv_id', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = 12;
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => (int) ceil($total / $perPage)];
    }

    /** 產生發票號碼 AA00000001（2 碼字軌 + 8 碼流水） */
    public function generateNumber(): string
    {
        // 發票號碼不分期、連號；原子取號避免重複（重複發票號碼會有稅務問題）
        return \App\Libraries\DocumentNumber::serial('INV', 'AA', 8);
    }
}
