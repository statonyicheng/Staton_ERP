<?php

namespace App\Models;

use CodeIgniter\Model;

class PayableModel extends Model
{
    protected $table = 'payables';
    protected $primaryKey = 'ap_id';
    protected $allowedFields = [
        'ap_no', 'ap_s_id', 'ap_date', 'ap_source', 'ap_ref_no', 'ap_ref_id',
        'ap_amount', 'ap_paid', 'ap_due_date', 'ap_status', 'ap_note',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'ap_created_at';
    protected $updatedField = 'ap_updated_at';

    public const STATUSES = ['未付款', '部分付款', '已付款'];

    public function getList($keyword = null, $page = 1, $status = null)
    {
        $builder = $this->db->table('payables ap')
            ->select('ap.*, s.s_name')
            ->join('suppliers s', 's.s_id = ap.ap_s_id', 'left');
        if ($keyword) {
            $builder->groupStart()->like('ap.ap_no', $keyword)->orLike('s.s_name', $keyword)->orLike('ap.ap_ref_no', $keyword)->groupEnd();
        }
        if ($status) $builder->where('ap.ap_status', $status);
        $builder->orderBy('ap.ap_date', 'DESC')->orderBy('ap.ap_id', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = 12;
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    public function summary(): array
    {
        $r = $this->db->table('payables')
            ->select('SUM(ap_amount) amt, SUM(ap_paid) paid')->get()->getRowArray();
        $amt = (int) ($r['amt'] ?? 0); $paid = (int) ($r['paid'] ?? 0);
        return ['amount' => $amt, 'paid' => $paid, 'outstanding' => $amt - $paid];
    }

    /** 付款後更新已付與狀態 */
    public function applyPayment(int $apId, int $amount): bool
    {
        $ap = $this->find($apId);
        if (!$ap) return false;
        $paid = (int) $ap['ap_paid'] + $amount;
        $status = $paid <= 0 ? '未付款' : ($paid >= (int) $ap['ap_amount'] ? '已付款' : '部分付款');
        return (bool) $this->update($apId, ['ap_paid' => $paid, 'ap_status' => $status]);
    }

    public function generateNo(?string $date = null): string
    {
        $date = $date ?: date('Y-m-d');
        $prefix = 'AP' . date('Ymd', strtotime($date)) . '-';
        $last = $this->like('ap_no', $prefix, 'after')->orderBy('ap_id', 'DESC')->first();
        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last['ap_no'], $m)) $next = (int) $m[1] + 1;
        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    /** 從已結案且尚未建立應付的採購單產生應付憑單 */
    public function generateFromClosedPOs(): int
    {
        $existing = array_column($this->db->table('payables')->select('ap_ref_id')->where('ap_source', '採購單')->get()->getResultArray(), 'ap_ref_id');
        $pos = $this->db->table('purchase_orders')
            ->where('po_status', '已結案')->where('po_total >', 0)
            ->get()->getResultArray();
        $now = date('Y-m-d H:i:s');
        $count = 0;
        foreach ($pos as $po) {
            if (in_array($po['po_id'], $existing)) continue;
            $this->insert([
                'ap_no' => $this->generateNo($po['po_date']),
                'ap_s_id' => $po['po_s_id'],
                'ap_date' => $po['po_date'],
                'ap_source' => '採購單',
                'ap_ref_no' => $po['po_no'],
                'ap_ref_id' => $po['po_id'],
                'ap_amount' => (int) $po['po_total'],
                'ap_paid' => 0,
                'ap_status' => '未付款',
            ]);
            $count++;
        }
        return $count;
    }
}
