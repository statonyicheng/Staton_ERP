<?php

namespace App\Models;

use CodeIgniter\Model;

class SettlementModel extends Model
{
    protected $table = 'settlements';
    protected $primaryKey = 'st_id';
    protected $allowedFields = [
        'st_no', 'st_date', 'st_direction', 'st_target', 'st_target_id',
        'st_ref_no', 'st_partner', 'st_amount', 'st_method', 'st_note', 'st_created_at',
    ];
    protected $useTimestamps = false;

    public const METHODS = ['現金', '銀行匯款', '票據', '其他'];

    public function record(array $data): int
    {
        $data['st_no'] = $this->generateNo($data['st_date'] ?? null, $data['st_direction'] ?? '收');
        $data['st_created_at'] = date('Y-m-d H:i:s');
        $this->insert($data);
        return (int) $this->getInsertID();
    }

    public function generateNo(?string $date, string $dir): string
    {
        $date = $date ?: date('Y-m-d');
        $p = ($dir === '付' ? 'PAY' : 'REC') . date('Ymd', strtotime($date)) . '-';
        $last = $this->like('st_no', $p, 'after')->orderBy('st_id', 'DESC')->first();
        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last['st_no'], $m)) $next = (int) $m[1] + 1;
        return $p . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    public function getList($keyword = null, $page = 1, $direction = null)
    {
        $builder = $this->builder();
        if ($keyword) {
            $builder->groupStart()->like('st_no', $keyword)->orLike('st_partner', $keyword)->orLike('st_ref_no', $keyword)->groupEnd();
        }
        if ($direction) $builder->where('st_direction', $direction);
        $builder->orderBy('st_date', 'DESC')->orderBy('st_id', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = 15;
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }
}
