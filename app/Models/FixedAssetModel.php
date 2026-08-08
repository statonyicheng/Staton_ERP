<?php

namespace App\Models;

use CodeIgniter\Model;

class FixedAssetModel extends Model
{
    protected $table = 'fixed_assets';
    protected $primaryKey = 'fa_id';
    protected $allowedFields = [
        'fa_code', 'fa_name', 'fa_category', 'fa_acquire_date', 'fa_cost',
        'fa_useful_life', 'fa_salvage', 'fa_depr_method', 'fa_location', 'fa_status', 'fa_note',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'fa_created_at';
    protected $updatedField = 'fa_updated_at';

    protected $validationRules = [
        'fa_name' => 'required|max_length[100]',
        'fa_cost' => 'permit_empty|is_natural',
        'fa_useful_life' => 'permit_empty|is_natural',
        'fa_salvage' => 'permit_empty|is_natural',
    ];
    protected $validationMessages = [
        'fa_name' => ['required' => '資產名稱為必填'],
    ];

    public function getList($keyword = null, $page = 1)
    {
        $builder = $this->builder();
        if ($keyword) {
            $builder->groupStart()
                ->like('fa_name', $keyword)->orLike('fa_code', $keyword)->orLike('fa_category', $keyword)
                ->groupEnd();
        }
        $builder->orderBy('fa_acquire_date', 'DESC')->orderBy('fa_id', 'DESC');

        $total = $builder->countAllResults(false);
        $perPage = 10;
        $totalPages = (int) ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return ['data' => $data, 'currentPage' => (int) $page, 'totalPages' => $totalPages];
    }

    public function generateCode(): string
    {
        $last = $this->select('fa_code')->like('fa_code', 'FA', 'after')
            ->orderBy('fa_id', 'DESC')->first();
        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last['fa_code'], $m)) {
            $next = (int) $m[1] + 1;
        }
        return 'FA' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 直線法年折舊 =（取得成本 − 殘值）÷ 耐用年數
     */
    public static function annualDepreciation(array $asset): int
    {
        $life = (int) ($asset['fa_useful_life'] ?? 0);
        if ($life <= 0) return 0;
        $base = (int) ($asset['fa_cost'] ?? 0) - (int) ($asset['fa_salvage'] ?? 0);
        return $base > 0 ? (int) round($base / $life) : 0;
    }
}
