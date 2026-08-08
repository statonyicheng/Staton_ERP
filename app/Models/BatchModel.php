<?php

namespace App\Models;

use App\Models\AuditedModel;

class BatchModel extends AuditedModel
{
    protected $table = 'batches';
    protected $primaryKey = 'b_id';
    protected $allowedFields = [
        'b_p_id', 'b_batch_no', 'b_serial', 'b_w_id', 'b_qty', 'b_mfg_date', 'b_exp_date', 'b_note',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'b_created_at';
    protected $updatedField = 'b_updated_at';

    protected $validationRules = ['b_p_id' => 'required'];
    protected $validationMessages = ['b_p_id' => ['required' => '請選擇商品']];

    public function getList($keyword = null, $page = 1)
    {
        $builder = $this->db->table('batches b')->select('b.*, p.p_code, p.p_name, w.w_name')
            ->join('products p', 'p.p_id = b.b_p_id', 'left')
            ->join('warehouses w', 'w.w_id = b.b_w_id', 'left');
        if ($keyword) {
            $builder->groupStart()->like('b.b_batch_no', $keyword)->orLike('b.b_serial', $keyword)->orLike('p.p_name', $keyword)->groupEnd();
        }
        $builder->orderBy('b.b_id', 'DESC');
        $total = $builder->countAllResults(false);
        $perPage = 12;
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => (int) ceil($total / $perPage)];
    }
}
