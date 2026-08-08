<?php

namespace App\Models;

use CodeIgniter\Model;

class JournalVoucherModel extends Model
{
    protected $table = 'journal_vouchers';
    protected $primaryKey = 'jv_id';
    protected $allowedFields = ['jv_no', 'jv_date', 'jv_type', 'jv_source_type', 'jv_source_id', 'jv_summary', 'jv_amount', 'jv_note'];
    protected $useTimestamps = true;
    protected $createdField = 'jv_created_at';
    protected $updatedField = 'jv_updated_at';

    public const TYPES = ['轉帳', '現收', '現付'];

    public function getList($keyword = null, $page = 1)
    {
        $builder = $this->builder();
        if ($keyword) {
            $builder->groupStart()->like('jv_no', $keyword)->orLike('jv_summary', $keyword)->groupEnd();
        }
        $builder->orderBy('jv_date', 'DESC')->orderBy('jv_id', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = 12;
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    public function getWithEntries($id)
    {
        $jv = $this->find($id);
        if (!$jv) return null;
        $jv['entries'] = $this->db->table('journal_entries je')
            ->select('je.*, a.ac_code, a.ac_name')
            ->join('accounts a', 'a.ac_id = je.je_ac_id', 'left')
            ->where('je.je_jv_id', $id)->orderBy('je.je_sort', 'ASC')->orderBy('je.je_id', 'ASC')
            ->get()->getResultArray();
        return $jv;
    }

    public function generateNo(?string $date = null): string
    {
        $date = $date ?: date('Y-m-d');
        $prefix = 'JV' . date('Ymd', strtotime($date)) . '-';
        $last = $this->like('jv_no', $prefix, 'after')->orderBy('jv_id', 'DESC')->first();
        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last['jv_no'], $m)) $next = (int) $m[1] + 1;
        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
