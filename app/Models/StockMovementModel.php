<?php

namespace App\Models;

use App\Models\AuditedModel;

class StockMovementModel extends AuditedModel
{
    protected $table = 'stock_movements';
    protected $primaryKey = 'sm_id';
    protected $allowedFields = [
        'sm_date', 'sm_type', 'sm_direction', 'sm_p_id', 'sm_w_id', 'sm_qty',
        'sm_ref_type', 'sm_ref_id', 'sm_ref_no', 'sm_note', 'sm_created_at',
    ];
    protected $useTimestamps = false;

    /** 「期初存量」供導入舊系統庫存用：讓在庫量一律有異動紀錄可追溯 */
    public const IN_TYPES = ['期初存量', '進貨', '退貨入庫', '完工入庫', '盤盈', '其他入庫'];
    public const OUT_TYPES = ['銷貨出庫', '領料', '盤虧', '其他出庫'];

    public static function directionOf(string $type): string
    {
        return in_array($type, self::OUT_TYPES, true) ? '出' : '入';
    }

    /**
     * 寫入一筆異動並即時更新 product_stock 在庫量。
     * $mv 需含 sm_date, sm_type, sm_direction, sm_p_id, sm_w_id, sm_qty (可含 ref/note)
     */
    public function apply(array $mv): int
    {
        $mv['sm_created_at'] = date('Y-m-d H:i:s');
        $this->insert($mv);
        $id = (int) $this->getInsertID();

        $delta = ($mv['sm_direction'] === '出' ? -1 : 1) * (int) $mv['sm_qty'];
        $now = date('Y-m-d H:i:s');
        $ps = $this->db->table('product_stock')
            ->where('ps_p_id', $mv['sm_p_id'])->where('ps_w_id', $mv['sm_w_id'])
            ->get()->getRowArray();
        if ($ps) {
            $this->db->table('product_stock')->where('ps_id', $ps['ps_id'])
                ->update(['ps_qty' => (int) $ps['ps_qty'] + $delta, 'ps_updated_at' => $now]);
        } else {
            $this->db->table('product_stock')->insert([
                'ps_p_id' => $mv['sm_p_id'], 'ps_w_id' => $mv['sm_w_id'],
                'ps_qty' => $delta, 'ps_updated_at' => $now,
            ]);
        }
        return $id;
    }

    public function getList($keyword = null, $page = 1, $type = null, $wId = null)
    {
        $builder = $this->db->table('stock_movements sm')
            ->select('sm.*, p.p_code, p.p_name, w.w_name')
            ->join('products p', 'p.p_id = sm.sm_p_id', 'left')
            ->join('warehouses w', 'w.w_id = sm.sm_w_id', 'left');
        if ($keyword) {
            $builder->groupStart()->like('p.p_name', $keyword)->orLike('p.p_code', $keyword)->orLike('sm.sm_ref_no', $keyword)->groupEnd();
        }
        if ($type) $builder->where('sm.sm_type', $type);
        if ($wId) $builder->where('sm.sm_w_id', $wId);
        $builder->orderBy('sm.sm_date', 'DESC')->orderBy('sm.sm_id', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = \App\Libraries\PageSize::get(15);
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }
}
