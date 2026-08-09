<?php

namespace App\Models;

use App\Models\AuditedModel;

/**
 * 業務別（商業模式）主檔。
 *
 * 取代原本寫死在 `TransactionModel::SEGMENTS` 的常數。所有需要業務別的地方
 * （傳票表單、交易登錄、四階損益的欄位、匯出）都改讀這裡。
 *
 * 查詢結果在單次請求內快取 —— 四階損益一支報表會問很多次，
 * 每次都打資料庫沒有意義。
 */
class BusinessSegmentModel extends AuditedModel
{
    protected $table = 'business_segments';
    protected $primaryKey = 'bs_id';
    protected $allowedFields = ['bs_code', 'bs_name', 'bs_definition', 'bs_in_pl', 'bs_is_active', 'bs_sort'];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'bs_created_at';
    protected $updatedField = 'bs_updated_at';

    /** 單次請求內的快取 */
    private static ?array $cache = null;

    /** 全部（含停用），依排序 */
    public function allSegments(): array
    {
        if (self::$cache === null) {
            self::$cache = $this->orderBy('bs_sort', 'ASC')->orderBy('bs_code', 'ASC')->findAll();
        }

        return self::$cache;
    }

    /** 資料異動後要清掉快取，否則同一次請求後續讀到的是舊的 */
    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * 下拉選單用：[代號 => 名稱]。停用的不出現，但**已選用該代號的既有資料不受影響**。
     */
    public static function map(bool $activeOnly = true): array
    {
        $out = [];
        foreach ((new self())->allSegments() as $s) {
            if ($activeOnly && empty($s['bs_is_active'])) continue;
            $out[$s['bs_code']] = $s['bs_name'];
        }

        return $out;
    }

    /** 四階損益分析的欄位（代號陣列，依排序） */
    public static function plCodes(): array
    {
        $out = [];
        foreach ((new self())->allSegments() as $s) {
            if (! empty($s['bs_in_pl']) && ! empty($s['bs_is_active'])) $out[] = $s['bs_code'];
        }

        // 一個都沒設定時至少給共用，報表才不會變成空表
        return $out ?: ['M-0'];
    }

    /** 落在報表欄位之外的資料要歸到哪一欄（第一個進損益的業務別） */
    public static function fallbackCode(): string
    {
        return self::plCodes()[0];
    }

    public function getList(?string $keyword = null, int $page = 1): array
    {
        $builder = $this->builder();
        if ($keyword) {
            $builder->groupStart()
                ->like('bs_code', $keyword)->orLike('bs_name', $keyword)->orLike('bs_definition', $keyword)
                ->groupEnd();
        }
        $builder->orderBy('bs_sort', 'ASC')->orderBy('bs_code', 'ASC');

        $total = $builder->countAllResults(false);
        $perPage = \App\Libraries\PageSize::get(20);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return [
            'data' => $data,
            'currentPage' => $page,
            'totalPages' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** 該商業模式已經被幾筆交易使用（停用/刪除前要看） */
    public function usageCount(string $code): int
    {
        return (int) $this->db->table('gl_transactions')->where('t_segment', $code)->countAllResults();
    }
}
