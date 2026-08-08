<?php

namespace App\Models;

use CodeIgniter\Model;

class PurchaseOrderModel extends Model
{
    protected $table = 'purchase_orders';
    protected $primaryKey = 'po_id';
    protected $allowedFields = [
        'po_no', 'po_s_id', 'po_date', 'po_expected_date', 'po_status',
        'po_subtotal', 'po_tax', 'po_total', 'po_note',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'po_created_at';
    protected $updatedField = 'po_updated_at';

    public const STATUSES = ['未結案', '部分進貨', '已結案', '作廢'];

    public function getList($keyword = null, $page = 1, $status = null)
    {
        $builder = $this->db->table('purchase_orders po')
            ->select('po.*, s.s_name')
            ->join('suppliers s', 's.s_id = po.po_s_id', 'left');
        if ($keyword) {
            $builder->groupStart()
                ->like('po.po_no', $keyword)->orLike('s.s_name', $keyword)->orLike('po.po_note', $keyword)
                ->groupEnd();
        }
        if ($status) $builder->where('po.po_status', $status);
        $builder->orderBy('po.po_date', 'DESC')->orderBy('po.po_id', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = 12;
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    public function getWithItems($id)
    {
        $po = $this->db->table('purchase_orders po')
            ->select('po.*, s.s_name, s.s_contact, s.s_phone')
            ->join('suppliers s', 's.s_id = po.po_s_id', 'left')
            ->where('po.po_id', $id)->get()->getRowArray();
        if (!$po) return null;
        $po['items'] = $this->db->table('purchase_order_items')
            ->where('poi_po_id', $id)->orderBy('poi_sort', 'ASC')->orderBy('poi_id', 'ASC')
            ->get()->getResultArray();
        return $po;
    }

    /** 採購報表：依廠商彙總（可選年月），排除作廢 */
    public function summaryBySupplier(?string $ym = null): array
    {
        $builder = $this->db->table('purchase_orders po')
            ->select('s.s_name, COUNT(*) cnt, SUM(po.po_subtotal) subtotal, SUM(po.po_tax) tax, SUM(po.po_total) total')
            ->join('suppliers s', 's.s_id = po.po_s_id', 'left')
            ->where('po.po_status !=', '作廢');
        if ($ym) $builder->like('po.po_date', $ym, 'after');
        return $builder->groupBy('po.po_s_id')->orderBy('total', 'DESC')->get()->getResultArray();
    }

    /** 有採購單的年月清單 */
    public function availableMonths(): array
    {
        $rows = $this->db->table('purchase_orders')
            ->select("DISTINCT LEFT(po_date, 7) ym", false)
            ->orderBy('ym', 'DESC')->get()->getResultArray();
        return array_column($rows, 'ym');
    }

    /** 產生採購單號 PO20260807-001 */
    public function generateNo(?string $date = null): string
    {
        // 原子取號，多人同時開單也不會重號（見 App\Libraries\DocumentNumber）
        return \App\Libraries\DocumentNumber::daily('PO', $date);
    }
}
