<?php

namespace App\Models;

use App\Models\AuditedModel;

class ProductStockModel extends AuditedModel
{
    protected $table = 'product_stock';
    protected $primaryKey = 'ps_id';
    protected $allowedFields = ['ps_p_id', 'ps_w_id', 'ps_qty', 'ps_updated_at'];
    protected $useTimestamps = false;

    /** 庫存查詢：各品號各倉在庫量（joined） */
    public function getList($keyword = null, $page = 1, $wId = null, bool $hideZero = false)
    {
        $builder = $this->db->table('product_stock ps')
            ->select('ps.*, p.p_code, p.p_name, p.p_specifications, w.w_name')
            ->join('products p', 'p.p_id = ps.ps_p_id', 'left')
            ->join('warehouses w', 'w.w_id = ps.ps_w_id', 'left');
        if ($keyword) {
            $builder->groupStart()->like('p.p_name', $keyword)->orLike('p.p_code', $keyword)->groupEnd();
        }
        if ($wId) $builder->where('ps.ps_w_id', $wId);
        if ($hideZero) $builder->where('ps.ps_qty !=', 0);
        $builder->orderBy('p.p_code', 'ASC');

        $total = $builder->countAllResults(false);
        $perPage = 15;
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    public function qtyOf($pId, $wId): int
    {
        $row = $this->where('ps_p_id', $pId)->where('ps_w_id', $wId)->first();
        return $row ? (int) $row['ps_qty'] : 0;
    }

    public function totalValueRows(): array
    {
        return $this->db->table('product_stock ps')
            ->select('SUM(ps.ps_qty) total_qty, COUNT(DISTINCT ps.ps_p_id) sku')
            ->where('ps.ps_qty !=', 0)->get()->getRowArray() ?: ['total_qty' => 0, 'sku' => 0];
    }
}
