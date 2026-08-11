<?php

namespace App\Models;

use App\Models\AuditedModel;

class AccountModel extends AuditedModel
{
    protected $table = 'accounts';
    protected $primaryKey = 'ac_id';
    protected $allowedFields = [
        'ac_code', 'ac_name', 'ac_category', 'ac_tier', 'ac_is_pl', 'ac_open_item', 'ac_ar_ap', 'ac_sort',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'ac_created_at';
    protected $updatedField = 'ac_updated_at';

    protected $validationRules = [
        'ac_name' => 'required|max_length[50]|is_unique[accounts.ac_name,ac_id,{ac_id}]',
        'ac_category' => 'required',
        'ac_tier' => 'required',
    ];
    protected $validationMessages = [
        'ac_name' => ['required' => '科目名稱為必填', 'is_unique' => '此科目名稱已存在'],
        'ac_category' => ['required' => '請選擇類別'],
        'ac_tier' => ['required' => '請選擇損益歸屬'],
    ];

    /** 損益歸屬選項（依【嵐石】財務架構四階模型）＋資產負債表 */
    public const TIERS = ['資產負債表', '營業收入', '一階成本', '二階費用', '三階費用', '四階費用', '不進損益'];
    public const CATEGORIES = ['資產', '負債', '權益', '收入', '支出', '非損益'];

    public function getList($keyword = null, $page = 1, $tier = null)
    {
        $builder = $this->builder();
        if ($keyword) {
            $builder->groupStart()
                ->like('ac_name', $keyword)->orLike('ac_code', $keyword)
                ->groupEnd();
        }
        if ($tier) {
            $builder->where('ac_tier', $tier);
        }
        $builder->orderBy('ac_sort', 'ASC')->orderBy('ac_id', 'ASC');

        $total = $builder->countAllResults(false);
        $perPage = \App\Libraries\PageSize::get(20);
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    public function getAllForDropdown()
    {
        return $this->orderBy('ac_sort', 'ASC')->findAll();
    }
}
