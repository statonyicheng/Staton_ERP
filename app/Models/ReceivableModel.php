<?php

namespace App\Models;

use CodeIgniter\Model;

class ReceivableModel extends Model
{
    protected $table = 'receivables';
    protected $primaryKey = 'ar_id';
    protected $allowedFields = [
        'ar_no', 'ar_c_id', 'ar_date', 'ar_source', 'ar_ref_no', 'ar_ref_id',
        'ar_amount', 'ar_received', 'ar_due_date', 'ar_status', 'ar_note',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'ar_created_at';
    protected $updatedField = 'ar_updated_at';

    public const STATUSES = ['未收款', '部分收款', '已收款'];

    public function getList($keyword = null, $page = 1, $status = null)
    {
        $builder = $this->db->table('receivables ar')
            ->select('ar.*, c.c_name')
            ->join('customers c', 'c.c_id = ar.ar_c_id', 'left');
        if ($keyword) {
            $builder->groupStart()->like('ar.ar_no', $keyword)->orLike('c.c_name', $keyword)->orLike('ar.ar_ref_no', $keyword)->groupEnd();
        }
        if ($status) $builder->where('ar.ar_status', $status);
        $builder->orderBy('ar.ar_date', 'DESC')->orderBy('ar.ar_id', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = 12;
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    public function summary(): array
    {
        $r = $this->db->table('receivables')
            ->select('SUM(ar_amount) amt, SUM(ar_received) rec')->get()->getRowArray();
        $amt = (int) ($r['amt'] ?? 0); $rec = (int) ($r['rec'] ?? 0);
        return ['amount' => $amt, 'received' => $rec, 'outstanding' => $amt - $rec];
    }

    /** 收款後更新已收與狀態 */
    public function applyReceipt(int $arId, int $amount): bool
    {
        $ar = $this->find($arId);
        if (!$ar) return false;
        $rec = (int) $ar['ar_received'] + $amount;
        $status = $rec <= 0 ? '未收款' : ($rec >= (int) $ar['ar_amount'] ? '已收款' : '部分收款');
        return (bool) $this->update($arId, ['ar_received' => $rec, 'ar_status' => $status]);
    }

    public function generateNo(?string $date = null): string
    {
        // 原子取號，多人同時開單也不會重號（見 App\Libraries\DocumentNumber）
        return \App\Libraries\DocumentNumber::daily('AR', $date);
    }

    /** 從未取消的訂單產生應收憑單 */
    public function generateFromOrders(): int
    {
        $existing = array_column($this->db->table('receivables')->select('ar_ref_id')->where('ar_source', '訂單')->get()->getResultArray(), 'ar_ref_id');
        $orders = $this->db->table('orders')
            ->where('o_status !=', 'cancelled')->where('o_total_amount >', 0)
            ->get()->getResultArray();
        $count = 0;
        foreach ($orders as $o) {
            if (in_array($o['o_id'], $existing)) continue;
            $this->insert([
                'ar_no' => $this->generateNo($o['o_date']),
                'ar_c_id' => $o['o_c_id'],
                'ar_date' => $o['o_date'],
                'ar_source' => '訂單',
                'ar_ref_no' => $o['o_number'],
                'ar_ref_id' => $o['o_id'],
                'ar_amount' => (int) round((float) $o['o_total_amount']),
                'ar_received' => 0,
                'ar_status' => '未收款',
            ]);
            $count++;
        }
        return $count;
    }
}
