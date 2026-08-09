<?php

namespace App\Models;

use App\Models\AuditedModel;

class BomItemModel extends AuditedModel
{
    protected $table = 'bom_items';
    protected $primaryKey = 'bi_id';
    protected $allowedFields = [
        'bi_parent_p_id', 'bi_child_p_id', 'bi_qty', 'bi_unit', 'bi_note', 'bi_created_at',
    ];
    protected $useTimestamps = false;

    /** 母件清單（有 BOM 者）＋元件數 */
    public function parentsWithCount($keyword = null, $page = 1)
    {
        $builder = $this->db->table('bom_items b')
            ->select('b.bi_parent_p_id, p.p_code, p.p_name, COUNT(*) cnt')
            ->join('products p', 'p.p_id = b.bi_parent_p_id', 'left');
        if ($keyword) {
            $builder->groupStart()->like('p.p_name', $keyword)->orLike('p.p_code', $keyword)->groupEnd();
        }
        $builder->groupBy('b.bi_parent_p_id')->orderBy('p.p_code', 'ASC');

        $total = $builder->countAllResults(false);
        $perPage = \App\Libraries\PageSize::get(12);
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    /** 某母件的元件清單（含子件品名） */
    public function getByParent($parentId): array
    {
        return $this->db->table('bom_items b')
            ->select('b.*, p.p_code child_code, p.p_name child_name')
            ->join('products p', 'p.p_id = b.bi_child_p_id', 'left')
            ->where('b.bi_parent_p_id', $parentId)
            ->orderBy('b.bi_id', 'ASC')->get()->getResultArray();
    }

    public function deleteByParent($parentId)
    {
        return $this->where('bi_parent_p_id', $parentId)->delete();
    }
}
