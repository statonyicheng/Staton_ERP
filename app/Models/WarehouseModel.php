<?php

namespace App\Models;

use CodeIgniter\Model;

class WarehouseModel extends Model
{
    protected $table = 'warehouses';
    protected $primaryKey = 'w_id';
    protected $allowedFields = [
        'w_code', 'w_name', 'w_location', 'w_manager', 'w_is_active', 'w_note',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'w_created_at';
    protected $updatedField = 'w_updated_at';

    protected $validationRules = [
        'w_name' => 'required|max_length[100]',
        'w_code' => 'permit_empty|max_length[50]',
    ];
    protected $validationMessages = [
        'w_name' => ['required' => '倉庫名稱為必填'],
    ];

    public function getAllForDropdown()
    {
        return $this->select('w_id, w_code, w_name')
            ->where('w_is_active', 1)->orderBy('w_name', 'ASC')->findAll();
    }

    public function getList($keyword = null, $page = 1)
    {
        $builder = $this->builder();
        if ($keyword) {
            $builder->groupStart()
                ->like('w_name', $keyword)->orLike('w_code', $keyword)->orLike('w_manager', $keyword)
                ->groupEnd();
        }
        $builder->orderBy('w_created_at', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = 10;
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    public function generateCode(): string
    {
        $last = $this->select('w_code')->like('w_code', 'WH', 'after')
            ->orderBy('w_id', 'DESC')->first();
        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last['w_code'], $m)) {
            $next = (int) $m[1] + 1;
        }
        return 'WH' . str_pad((string) $next, 2, '0', STR_PAD_LEFT);
    }
}
