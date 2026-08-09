<?php

namespace App\Models;

use App\Models\AuditedModel;

class QuoteModel extends AuditedModel
{
    protected $table = 'quotes';
    protected $primaryKey = 'q_id';
    protected $allowedFields = [
        'q_number',
        'q_date',
        'q_valid_date',
        'q_c_id',
        'q_cc_id',
        'q_delivery_city',
        'q_delivery_address',
        'q_subtotal',
        'q_discount',
        'q_tax_rate',
        'q_shipping_fee',
        'q_tax_amount',
        'q_total_amount',
        'q_notes',
        'q_o_id',
        'q_vendor'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'q_created_at';
    protected $updatedField = 'q_updated_at';

    /**
     * 取得報價單及客戶資料
     */
    public function getWithCustomer($id = null)
    {
        $builder = $this->select('quotes.*, customers.c_name as customer_name, customers.c_contact_person, customers.c_phone')
            ->join('customers', 'customers.c_id = quotes.q_c_id', 'left');

        if ($id !== null) {
            return $builder->where('quotes.q_id', $id)->first();
        }

        return $builder->findAll();
    }

    /**
     * 分頁查詢報價單（含客戶資訊）
     *
     * @param string|null $keyword 搜尋關鍵字
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @return array ['data' => 資料陣列, 'total' => 總筆數, 'totalPages' => 總頁數]
     */
    public function getQuotesWithPagination(?string $keyword = null, int $page = 1, ?int $perPage = null): array
    {
        $perPage = $perPage ?? \App\Libraries\PageSize::get(10);
        $builder = $this->builder()
            ->select('quotes.*, quotes.q_o_id, customers.c_name as customer_name')
            ->join('customers', 'customers.c_id = quotes.q_c_id', 'left');

        if ($keyword) {
            $builder->groupStart()
                ->like('q_number', $keyword)
                ->orLike('c_name', $keyword)
                ->groupEnd();
        }

        $builder->orderBy('q_created_at', 'DESC');

        $total = $builder->countAllResults(false);
        $totalPages = ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return [
            'data' => $data,
            'total' => $total,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * 取得指定客戶的報價單列表（分頁）
     */
    public function getByCustomer(int $customerId, int $page = 1, ?int $perPage = null): array
    {
        $perPage = $perPage ?? \App\Libraries\PageSize::get(10);
        $builder = $this->builder()
            ->select('q_id, q_number, q_date, q_total_amount, q_created_at')
            ->where('q_c_id', $customerId)
            ->orderBy('q_created_at', 'DESC');

        $total = $builder->countAllResults(false);
        $totalPages = ceil($total / $perPage);
        $data = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return [
            'data' => $data,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ];
    }

    /**
     * 生成新的報價單號，格式 Q20260808-001。
     *
     * 走 DocumentNumber 的原子計數器，避免多人同時開單重號。
     */
    public function generateQuoteNumber(): string
    {
        return \App\Libraries\DocumentNumber::daily('Q');
    }

    /**
     * 儲存報價單（含項目）
     * 使用事務確保資料一致性
     * 
     * @param array $quoteData 報價單資料
     * @param array $items 報價單項目
     * @return array ['success' => bool, 'message' => string, 'quoteId' => int|null]
     */
    public function saveQuoteWithItems(array $quoteData, array $items): array
    {
        $quoteItemModel = new QuoteItemModel();
        $quoteId = $quoteData['q_id'] ?? null;

        if (empty($quoteData['q_cc_id'])) {
            $quoteData['q_cc_id'] = null;
        }

        // 品項以「商品」（qi_p_id）為單位；沒選商品的列視為空白列
        $items = array_values(array_filter(
            $items ?: [],
            static fn ($item) => ! empty($item['qi_p_id']) && ! empty($item['qi_quantity'])
        ));

        if ($items === []) {
            return [
                'success' => false,
                'message' => '至少需要新增一個有效的商品項目',
                'quoteId' => null,
            ];
        }

        // 驗證通過才開交易，避免中途 return 留下懸空的交易
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            if ($quoteId) {
                // 更新報價單（直接使用前端傳來的金額）
                $this->update($quoteId, $quoteData);

                // 刪除舊的項目（報價單明細沒有下游單據參照，可整批重建）
                $quoteItemModel->where('qi_q_id', $quoteId)->delete();
            } else {
                // 新增報價單（直接使用前端傳來的金額）
                $quoteId = $this->insert($quoteData);
                if (! $quoteId) {
                    throw new \RuntimeException('無法建立報價單');
                }
            }

            // 新增項目（直接使用前端傳來的金額）
            foreach ($items as $item) {
                unset($item['qi_id']);
                $item['qi_q_id'] = $quoteId;

                if (! $quoteItemModel->insert($item)) {
                    throw new \RuntimeException(implode('、', $quoteItemModel->errors()));
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return [
                    'success' => false,
                    'message' => '儲存失敗，請稍後再試',
                    'quoteId' => null,
                ];
            }

            return [
                'success' => true,
                'message' => '儲存成功',
                'quoteId' => $quoteId,
            ];
        } catch (\Throwable $e) {
            $db->transRollback();
            return [
                'success' => false,
                'message' => '儲存失敗：' . $e->getMessage(),
                'quoteId' => null,
            ];
        }
    }


    /**
     * 驗證項目資料
     * 
     * @param array $items 項目陣列
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateItems(array $items): array
    {
        if (empty($items)) {
            return [
                'valid' => false,
                'message' => '至少需要新增一個商品項目',
            ];
        }

        $validItemCount = count(array_filter($items, function ($item) {
            return !empty($item['qi_p_id']) && !empty($item['qi_quantity']);
        }));

        if ($validItemCount === 0) {
            return [
                'valid' => false,
                'message' => '至少需要新增一個有效的商品項目',
            ];
        }

        return [
            'valid' => true,
            'message' => '',
        ];
    }

    /**
     * 檢查報價單號是否唯一
     *
     * @param string $quoteNumber 報價單號
     * @param int|null $excludeId 要排除的報價單 ID（用於更新時）
     * @return bool
     */
    public function isQuoteNumberUnique(string $quoteNumber, ?int $excludeId = null): bool
    {
        $builder = $this->where('q_number', $quoteNumber);

        if ($excludeId !== null) {
            $builder->where('q_id !=', $excludeId);
        }

        return $builder->countAllResults() === 0;
    }

    /**
     * 取得報價單及其項目
     *
     * @param int $quoteId 報價單ID
     * @return array|null
     */
    public function getQuoteWithItems($quoteId)
    {
        $quote = $this->find($quoteId);
        if (!$quote) {
            return null;
        }

        $quoteItemModel = new QuoteItemModel();
        $quote['items'] = $quoteItemModel->getItemsWithProduct($quoteId);

        return $quote;
    }

    public function deleteOrderId(int $orderId)
    {
        $this->builder()->where('q_o_id', $orderId)->update(['q_o_id' => null]);
    }
}
