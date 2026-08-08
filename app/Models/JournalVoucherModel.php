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
        // 原子取號，多人同時開單也不會重號（見 App\Libraries\DocumentNumber）
        return \App\Libraries\DocumentNumber::daily('JV', $date);
    }
}
