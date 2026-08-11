<?php

namespace App\Controllers;

use App\Libraries\Exporter;
use App\Models\TransactionModel;

/**
 * 全系統匯出：Excel(.xlsx) / PDF
 *
 *   /export/xlsx/<key>?<原畫面的篩選條件>
 *   /export/pdf/<key>?<原畫面的篩選條件>
 *
 * 每個模組在 catalog() 登錄一次（標題／欄位／SQL），資料管理與報表畫面用
 * components/export_buttons 元件掛上按鈕，並自動把當前的搜尋/期間條件帶進來。
 */
class ExportController extends BaseController
{
    private $db;

    /** 篩選條件；網頁來自 query string，CLI 由 setFilters() 帶入 */
    private array $filters = [];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $req = service('request');
        if ($req instanceof \CodeIgniter\HTTP\IncomingRequest) {
            $this->filters = (array) $req->getGet();
        }
    }

    /** 供 CLI（spark erp:export）指定篩選條件 */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    public function xlsx(string $key)
    {
        $this->render($key, 'xlsx');
    }

    public function pdf(string $key)
    {
        $this->render($key, 'pdf');
    }

    /** $saveTo 有值時存檔（CLI/排程用），否則直接輸出下載 */
    public function render(string $key, string $format, ?string $saveTo = null): void
    {
        [$title, $columns, $rows, $meta, $orientation] = array_pad($this->build($key), 5, 'P');
        if ($format === 'pdf') {
            Exporter::pdf($this->fileName($title), $title, $columns, $rows, $meta, $orientation, $saveTo);
        } else {
            Exporter::xlsx($this->fileName($title), $title, $columns, $rows, $meta, $saveTo);
        }
    }

    /** 已登錄的匯出項目鍵值 */
    public function keys(): array
    {
        return array_keys($this->catalog());
    }

    /**
     * 可匯出的頁面鍵值（版面用來自動判斷目前頁面要不要顯示匯出按鈕）。
     * 每次請求只計算一次。
     */
    public static function exportableKeys(): array
    {
        static $keys = null;
        if ($keys === null) $keys = array_keys((new self())->catalog());
        return $keys;
    }

    private function fileName(string $title): string
    {
        return '仕坦登ERP_' . $title . '_' . date('Ymd_Hi');
    }

    // ==================================================================

    private function build(string $key): array
    {
        $cat = $this->catalog();
        if (!isset($cat[$key])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("未登錄的匯出項目：{$key}");
        }
        $def = $cat[$key];
        $rows = ($def['rows'])();
        return [
            $def['title'],
            $def['columns'],
            $rows,
            [
                'subtitle' => $def['subtitle'] ?? '',
                'note' => $def['note'] ?? '',
                'totals' => $def['totals'] ?? true,   // 報表已含小計時關閉自動合計列
            ],
            $def['orientation'] ?? 'P',
        ];
    }

    /** 小工具：欄位定義 */
    private function col(string $key, string $label, string $type = 'text', $width = null): array
    {
        $c = ['key' => $key, 'label' => $label, 'type' => $type];
        if ($width) $c['width'] = $width;
        return $c;
    }

    /** 小工具：執行 SQL 取全部列 */
    private function sql(string $sql, array $binds = []): array
    {
        return $this->db->query($sql, $binds)->getResultArray();
    }

    /**
     * 應收／應付的未沖銷明細（與畫面同一個來源）。
     * 摘要與科目在這裡先組好字串，匯出的欄位才會跟畫面看到的一致。
     */
    private function arApRows(string $type): array
    {
        $rows = (new \App\Libraries\ArApBook())->items($type);

        return array_map(static function ($r) {
            $r['summary'] = $r['je_summary'] ?: $r['jv_summary'];
            $r['acct'] = trim($r['ac_code'] . ' ' . $r['ac_name']);
            return $r;
        }, $rows);
    }

    /** 財務報表年度（與 FinancialStatementController 一致，預設今年） */
    private function fsYear(): int
    {
        return (int) ($this->get('year') ?: date('Y'));
    }

    private function get(string $k, $default = null)
    {
        $v = $this->filters[$k] ?? null;
        return ($v === null || $v === '') ? $default : $v;
    }

    /**
     * 交易資料的年月區間（記憶化）。
     * catalog() 在每次頁面渲染都會被建立（版面用它判斷要不要顯示匯出按鈕），
     * 若不快取，每頁都會多打一次 MIN/MAX 查詢。
     */
    private function txRange(): array
    {
        static $range = null;
        if ($range === null) $range = (new TransactionModel())->periodRange();
        return $range;
    }

    /** 會計帳簿共用的期間 / 科目條件（與畫面上的篩選一致），記憶化避免重複查詢 */
    private function bookFilters(): array
    {
        static $range = null;
        if ($range === null) $range = (new \App\Libraries\AccountingBooks())->dateRange();

        return [
            $this->get('from', $range['min']),
            $this->get('to', $range['max']),
            (int) $this->get('ac_id', 0) ?: null,
        ];
    }

    private function bookSubtitle(): string
    {
        [$from, $to, $acId] = $this->bookFilters();
        return "期間 {$from} ~ {$to}" . ($acId ? '（單一科目）' : '') . '　依複式簿記分錄編製';
    }

    /** LIKE 關鍵字條件（無關鍵字時回傳恆真條件） */
    private function kw(array $fields): array
    {
        $kw = $this->get('keyword');
        if (!$kw) return ['1=1', []];
        $parts = [];
        $binds = [];
        foreach ($fields as $f) { $parts[] = "{$f} LIKE ?"; $binds[] = '%' . $kw . '%'; }
        return ['(' . implode(' OR ', $parts) . ')', $binds];
    }

    // ==================================================================

    private function catalog(): array
    {
        return array_merge($this->catalogMaster(), $this->catalogDocs(), $this->catalogReports());
    }

    // ---------- 基本資料 ----------
    private function catalogMaster(): array
    {
        return [
            'customer' => [
                'title' => '客戶資料',
                'columns' => [
                    $this->col('c_code', '客戶編號'), $this->col('c_name', '客戶名稱'),
                    $this->col('c_manager', '負責人'), $this->col('c_phone', '電話'),
                    $this->col('c_email', 'Email'), $this->col('c_tax_id', '統一編號'),
                    $this->col('c_city', '縣市'), $this->col('c_address', '地址'),
                    $this->col('pm_name', '結帳方式'),
                ],
                'rows' => function () {
                    [$w, $b] = $this->kw(['c.c_name', 'c.c_code', 'c.c_manager', 'c.c_phone']);
                    return $this->sql("SELECT c.*, pm.pm_name FROM customers c
                        LEFT JOIN payment_methods pm ON pm.pm_id = c.c_pm_id
                        WHERE {$w} ORDER BY c.c_code", $b);
                },
            ],
            'supplier' => [
                'title' => '廠商資料',
                'columns' => [
                    $this->col('s_code', '廠商編號'), $this->col('s_name', '廠商名稱'),
                    $this->col('s_contact', '聯絡人'), $this->col('s_phone', '電話'),
                    $this->col('s_email', 'Email'), $this->col('s_tax_id', '統一編號'),
                    $this->col('s_address', '地址'), $this->col('pm_name', '結帳方式'),
                ],
                'rows' => function () {
                    [$w, $b] = $this->kw(['s.s_name', 's.s_code', 's.s_contact']);
                    return $this->sql("SELECT s.*, pm.pm_name FROM suppliers s
                        LEFT JOIN payment_methods pm ON pm.pm_id = s.s_pm_id
                        WHERE {$w} ORDER BY s.s_code", $b);
                },
            ],
            'product' => [
                'title' => '商品資料',
                'orientation' => 'L',
                'columns' => [
                    $this->col('p_code', '商品編號'), $this->col('p_name', '商品名稱'),
                    $this->col('pc_name', '分類'), $this->col('p_supplier', '供應商'),
                    $this->col('p_color', '顏色'), $this->col('p_size', '尺寸'),
                    $this->col('p_standard_price', '標準售價', 'money'),
                    $this->col('p_cost_price', '成本價', 'money'),
                ],
                'rows' => function () {
                    [$w, $b] = $this->kw(['p.p_name', 'p.p_code', 'p.p_supplier']);
                    return $this->sql("SELECT p.*, pc.pc_name FROM products p
                        LEFT JOIN product_categories pc ON pc.pc_id = p.p_pc_id
                        WHERE {$w} ORDER BY p.p_code", $b);
                },
            ],
            'product-category' => [
                'title' => '產品分類',
                'columns' => [$this->col('pc_id', '編號'), $this->col('pc_name', '分類名稱'), $this->col('cnt', '商品數', 'number')],
                'rows' => fn() => $this->sql("SELECT pc.*, (SELECT COUNT(*) FROM products p WHERE p.p_pc_id = pc.pc_id) cnt
                    FROM product_categories pc ORDER BY pc.pc_id"),
            ],
            'pricing' => [
                'title' => '商品價格',
                'columns' => [
                    $this->col('p_code', '商品編號'), $this->col('p_name', '商品名稱'),
                    $this->col('p_standard_price', '售價', 'money'), $this->col('p_cost_price', '成本', 'money'),
                    $this->col('gross', '毛利', 'money'), $this->col('rate', '毛利率(%)'),
                ],
                'rows' => fn() => array_map(function ($r) {
                    $r['gross'] = (int) $r['p_standard_price'] - (int) $r['p_cost_price'];
                    $r['rate'] = (int) $r['p_standard_price'] > 0
                        ? number_format($r['gross'] / (int) $r['p_standard_price'] * 100, 1) : '—';
                    return $r;
                }, $this->sql("SELECT p_code, p_name, p_standard_price, p_cost_price FROM products ORDER BY p_code")),
            ],
            'payment-method' => [
                'title' => '結帳方式',
                'columns' => [$this->col('pm_id', '編號'), $this->col('pm_name', '結帳方式')],
                'rows' => fn() => $this->sql("SELECT * FROM payment_methods ORDER BY pm_id"),
            ],
            'account' => [
                'title' => '會計科目表',
                'columns' => [
                    $this->col('ac_code', '科目代碼', 'text', 12), $this->col('ac_name', '科目名稱', 'text', 28),
                    $this->col('ac_category', '類別'), $this->col('ac_tier', '損益歸屬'),
                    $this->col('pl', '進損益'), $this->col('oi', '立沖帳'),
                ],
                'rows' => function () {
                    $tier = $this->get('tier');
                    [$w, $b] = $this->kw(['ac_name', 'ac_code']);
                    if ($tier) { $w .= ' AND ac_tier = ?'; $b[] = $tier; }
                    return array_map(function ($r) {
                        $r['pl'] = $r['ac_is_pl'] ? '是' : '';
                        $r['oi'] = $r['ac_open_item'] ? '是' : '';
                        return $r;
                    }, $this->sql("SELECT * FROM accounts WHERE {$w} ORDER BY ac_sort, ac_id", $b));
                },
            ],
            'warehouse' => [
                'title' => '倉庫資料',
                'columns' => [
                    $this->col('w_code', '倉庫代號'), $this->col('w_name', '倉庫名稱'),
                    $this->col('w_location', '位置'), $this->col('w_manager', '管理人'),
                    $this->col('active', '啟用'),
                ],
                'rows' => fn() => array_map(function ($r) { $r['active'] = $r['w_is_active'] ? '啟用' : '停用'; return $r; },
                    $this->sql("SELECT * FROM warehouses ORDER BY w_code")),
            ],
            'fixed-asset' => [
                'title' => '固定資產',
                'orientation' => 'L',
                'columns' => [
                    $this->col('fa_code', '資產編號'), $this->col('fa_name', '資產名稱'),
                    $this->col('fa_category', '類別'), $this->col('fa_acquire_date', '取得日期'),
                    $this->col('fa_cost', '取得成本', 'money'), $this->col('fa_useful_life', '耐用年限', 'number'),
                    $this->col('fa_salvage', '殘值', 'money'), $this->col('depr', '年折舊', 'money'),
                    $this->col('fa_status', '狀態'),
                ],
                'rows' => fn() => array_map(function ($r) {
                    $life = (int) $r['fa_useful_life'];
                    $r['depr'] = $life > 0 ? (int) round(((int) $r['fa_cost'] - (int) $r['fa_salvage']) / $life) : 0;
                    return $r;
                }, $this->sql("SELECT * FROM fixed_assets ORDER BY fa_code")),
            ],
            'batch' => [
                'title' => '批號序號',
                'columns' => [
                    $this->col('b_batch_no', '批號'), $this->col('b_serial', '序號'),
                    $this->col('p_name', '商品'), $this->col('w_name', '倉庫'),
                    $this->col('b_qty', '數量', 'number'),
                    $this->col('b_mfg_date', '製造日期'), $this->col('b_exp_date', '有效日期'),
                ],
                'rows' => fn() => $this->sql("SELECT b.*, p.p_name, w.w_name FROM batches b
                    LEFT JOIN products p ON p.p_id = b.b_p_id
                    LEFT JOIN warehouses w ON w.w_id = b.b_w_id ORDER BY b.b_id DESC"),
            ],
        ];
    }

    // ---------- 單據 ----------
    private function catalogDocs(): array
    {
        return [
            'quote' => [
                'title' => '報價單清單', 'orientation' => 'L',
                'columns' => [
                    $this->col('q_number', '報價單號'), $this->col('q_date', '日期'),
                    $this->col('c_name', '客戶'), $this->col('q_valid_date', '有效期限'),
                    $this->col('q_subtotal', '小計', 'money'), $this->col('q_tax_amount', '稅額', 'money'),
                    $this->col('q_total_amount', '總計', 'money'),
                ],
                'rows' => function () {
                    [$w, $b] = $this->kw(['q.q_number', 'c.c_name']);
                    return $this->sql("SELECT q.*, c.c_name FROM quotes q
                        LEFT JOIN customers c ON c.c_id = q.q_c_id WHERE {$w} ORDER BY q.q_date DESC, q.q_id DESC", $b);
                },
            ],
            'order' => [
                'title' => '訂單清單', 'orientation' => 'L',
                'columns' => [
                    $this->col('o_number', '訂單號'), $this->col('o_date', '日期'),
                    $this->col('c_name', '客戶'), $this->col('o_delivery_date', '交期'),
                    $this->col('o_total_amount', '訂單金額', 'money'),
                    $this->col('ship', '出貨狀態'), $this->col('pay', '付款狀態'), $this->col('st', '訂單狀態'),
                ],
                'rows' => function () {
                    [$w, $b] = $this->kw(['o.o_number', 'c.c_name']);
                    $shipMap = ['preparing' => '備貨中', 'partial' => '部分出貨', 'shipped' => '已出貨'];
                    $payMap  = ['unpaid' => '未付款', 'partial' => '部分付款', 'paid' => '已付款'];
                    $stMap   = ['processing' => '處理中', 'completed' => '已完成', 'cancelled' => '已取消'];
                    return array_map(function ($r) use ($shipMap, $payMap, $stMap) {
                        $r['ship'] = $shipMap[$r['o_shipment_status']] ?? $r['o_shipment_status'];
                        $r['pay']  = $payMap[$r['o_payment_status']] ?? $r['o_payment_status'];
                        $r['st']   = $stMap[$r['o_status']] ?? $r['o_status'];
                        return $r;
                    }, $this->sql("SELECT o.*, c.c_name FROM orders o
                        LEFT JOIN customers c ON c.c_id = o.o_c_id WHERE {$w} ORDER BY o.o_date DESC, o.o_id DESC", $b));
                },
            ],
            'shipment' => [
                'title' => '出貨單清單',
                'columns' => [
                    $this->col('s_number', '出貨單號'), $this->col('s_date', '出貨日期'),
                    $this->col('o_number', '訂單號'), $this->col('c_name', '客戶'),
                    $this->col('qty', '出貨數量', 'number'),
                ],
                'rows' => fn() => $this->sql("SELECT s.*, o.o_number, c.c_name,
                        (SELECT COALESCE(SUM(si.si_quantity),0) FROM shipment_items si WHERE si.si_s_id = s.s_id) qty
                    FROM shipments s
                    LEFT JOIN orders o ON o.o_id = s.s_o_id
                    LEFT JOIN customers c ON c.c_id = o.o_c_id ORDER BY s.s_date DESC, s.s_id DESC"),
            ],
            'purchase-order' => [
                'title' => '採購單清單', 'orientation' => 'L',
                'columns' => [
                    $this->col('po_no', '採購單號'), $this->col('po_date', '日期'),
                    $this->col('s_name', '廠商'), $this->col('po_expected_date', '預計到貨'),
                    $this->col('po_subtotal', '未稅', 'money'), $this->col('po_tax', '稅額', 'money'),
                    $this->col('po_total', '總計', 'money'), $this->col('po_status', '狀態'),
                ],
                'rows' => function () {
                    [$w, $b] = $this->kw(['po.po_no', 's.s_name']);
                    return $this->sql("SELECT po.*, s.s_name FROM purchase_orders po
                        LEFT JOIN suppliers s ON s.s_id = po.po_s_id WHERE {$w} ORDER BY po.po_date DESC, po.po_id DESC", $b);
                },
            ],
            'purchase-requisition' => [
                'title' => '請購單清單',
                'columns' => [
                    $this->col('pr_no', '請購單號'), $this->col('pr_date', '日期'),
                    $this->col('pr_dept', '部門'), $this->col('pr_name', '品名'),
                    $this->col('pr_spec', '規格'), $this->col('pr_qty', '數量', 'number'),
                    $this->col('pr_unit', '單位'), $this->col('pr_need_date', '需求日'), $this->col('pr_status', '狀態'),
                ],
                'rows' => fn() => $this->sql("SELECT * FROM purchase_requisitions ORDER BY pr_date DESC, pr_id DESC"),
            ],
            'invoice' => [
                'title' => '電子發票清單', 'orientation' => 'L',
                'columns' => [
                    $this->col('inv_number', '發票號碼'), $this->col('inv_date', '開立日期'),
                    $this->col('inv_buyer', '買受人'), $this->col('inv_buyer_tax', '統編'),
                    $this->col('inv_amount', '銷售額', 'money'), $this->col('inv_tax', '稅額', 'money'),
                    $this->col('inv_total', '總計', 'money'), $this->col('inv_status', '狀態'),
                ],
                'rows' => fn() => $this->sql("SELECT * FROM invoices ORDER BY inv_date DESC, inv_id DESC"),
            ],
            'transaction' => [
                'title' => '交易登錄（收付）', 'orientation' => 'L',
                'columns' => [
                    $this->col('t_date', '日期', 'text', 12), $this->col('t_summary', '摘要', 'text', 34),
                    $this->col('t_partner', '對象'), $this->col('t_segment', '商業模式'),
                    $this->col('ac_code', '科目代碼'), $this->col('ac_name', '會計科目'),
                    $this->col('t_direction', '收付'),
                    $this->col('t_amount', '未稅', 'money'), $this->col('t_tax', '稅額', 'money'),
                    $this->col('gross', '含稅', 'money'), $this->col('t_settle_status', '收付狀態'),
                ],
                'rows' => function () {
                    $ym = $this->get('ym');
                    [$w, $b] = $this->kw(['t.t_summary', 't.t_partner', 'a.ac_name']);
                    if ($ym) { $w .= ' AND t.t_ym = ?'; $b[] = $ym; }
                    return array_map(function ($r) { $r['gross'] = (int) $r['t_amount'] + (int) $r['t_tax']; return $r; },
                        $this->sql("SELECT t.*, a.ac_code, a.ac_name FROM gl_transactions t
                            LEFT JOIN accounts a ON a.ac_id = t.t_ac_id
                            WHERE {$w} ORDER BY t.t_date DESC, t.t_id DESC", $b));
                },
                'subtitle' => $this->get('ym') ? '期間 ' . $this->get('ym') : '全部期間',
            ],
            'journal' => [
                'title' => '分錄傳票清單', 'orientation' => 'L',
                'columns' => [
                    $this->col('jv_no', '傳票號'), $this->col('jv_date', '日期'),
                    $this->col('jv_type', '類別'), $this->col('jv_summary', '摘要', 'text', 40),
                    $this->col('jv_amount', '金額', 'money'),
                    $this->col('debit', '借方合計', 'money'), $this->col('credit', '貸方合計', 'money'),
                ],
                'rows' => fn() => $this->sql("SELECT v.*,
                        COALESCE((SELECT SUM(e.je_debit) FROM journal_entries e WHERE e.je_jv_id = v.jv_id),0) debit,
                        COALESCE((SELECT SUM(e.je_credit) FROM journal_entries e WHERE e.je_jv_id = v.jv_id),0) credit
                    FROM journal_vouchers v ORDER BY v.jv_date DESC, v.jv_id DESC"),
            ],
            'journal-entry' => [
                'title' => '分錄明細', 'orientation' => 'L',
                'columns' => [
                    $this->col('jv_no', '傳票號'), $this->col('jv_date', '日期'),
                    $this->col('jv_summary', '傳票摘要', 'text', 30),
                    $this->col('ac_code', '科目代碼'), $this->col('ac_name', '會計科目'),
                    $this->col('je_summary', '分錄摘要'),
                    $this->col('je_debit', '借方', 'money'), $this->col('je_credit', '貸方', 'money'),
                    $this->col('je_offset', '已沖銷', 'money'),
                ],
                'rows' => fn() => $this->sql("SELECT v.jv_no, v.jv_date, v.jv_summary, a.ac_code, a.ac_name,
                        e.je_summary, e.je_debit, e.je_credit, e.je_offset
                    FROM journal_entries e
                    JOIN journal_vouchers v ON v.jv_id = e.je_jv_id
                    LEFT JOIN accounts a ON a.ac_id = e.je_ac_id
                    ORDER BY v.jv_date DESC, v.jv_id DESC, e.je_sort"),
            ],
            // 應收/應付已改為直接讀會計帳上的未沖銷分錄（App\Libraries\ArApBook）。
            // 匯出必須跟畫面同一個資料來源，否則會出現「畫面有資料、Excel 是空的」。
            'receivable' => [
                'title' => '應收帳款（未沖銷）', 'orientation' => 'L',
                'columns' => [
                    $this->col('jv_date', '日期'), $this->col('jv_no', '傳票編號'),
                    $this->col('summary', '摘要'), $this->col('acct', '會計科目'),
                    $this->col('amount', '應收金額', 'money'), $this->col('je_offset', '已收', 'money'),
                    $this->col('open_amt', '未收', 'money'), $this->col('je_offset_date', '沖銷日'),
                ],
                'rows' => fn() => $this->arApRows(\App\Libraries\ArApBook::AR),
            ],
            'payable' => [
                'title' => '應付帳款（未沖銷）', 'orientation' => 'L',
                'columns' => [
                    $this->col('jv_date', '日期'), $this->col('jv_no', '傳票編號'),
                    $this->col('summary', '摘要'), $this->col('acct', '會計科目'),
                    $this->col('amount', '應付金額', 'money'), $this->col('je_offset', '已付', 'money'),
                    $this->col('open_amt', '未付', 'money'), $this->col('je_offset_date', '沖銷日'),
                ],
                'rows' => fn() => $this->arApRows(\App\Libraries\ArApBook::AP),
            ],
            'settlement' => [
                'title' => '收付款紀錄',
                'columns' => [
                    $this->col('st_no', '收付單號'), $this->col('st_date', '日期'),
                    $this->col('st_direction', '收/付'), $this->col('st_target', '對象別'),
                    $this->col('st_partner', '對象'), $this->col('st_ref_no', '來源單號'),
                    $this->col('st_amount', '金額', 'money'), $this->col('st_method', '方式'),
                ],
                'rows' => fn() => $this->sql("SELECT * FROM settlements ORDER BY st_date DESC, st_id DESC"),
            ],
            'work-order' => [
                'title' => '製令清單',
                'columns' => [
                    $this->col('wo_no', '製令單號'), $this->col('wo_date', '開單日'),
                    $this->col('p_name', '生產品項'), $this->col('wo_qty', '數量', 'number'),
                    $this->col('w_name', '入庫倉'), $this->col('wo_due_date', '完工期限'), $this->col('wo_status', '狀態'),
                ],
                'rows' => fn() => $this->sql("SELECT w.*, p.p_name, wh.w_name FROM work_orders w
                    LEFT JOIN products p ON p.p_id = w.wo_p_id
                    LEFT JOIN warehouses wh ON wh.w_id = w.wo_w_id ORDER BY w.wo_date DESC, w.wo_id DESC"),
            ],
            'bom' => [
                'title' => '產品結構 BOM',
                'columns' => [
                    $this->col('parent_code', '母件編號'), $this->col('parent_name', '母件名稱'),
                    $this->col('child_code', '子件編號'), $this->col('child_name', '子件名稱'),
                    $this->col('bi_qty', '用量', 'number'), $this->col('bi_unit', '單位'), $this->col('bi_note', '備註'),
                ],
                'rows' => fn() => $this->sql("SELECT b.*, pp.p_code parent_code, pp.p_name parent_name,
                        cp.p_code child_code, cp.p_name child_name
                    FROM bom_items b
                    LEFT JOIN products pp ON pp.p_id = b.bi_parent_p_id
                    LEFT JOIN products cp ON cp.p_id = b.bi_child_p_id
                    ORDER BY pp.p_code, cp.p_code"),
            ],
        ];
    }

    // ---------- 報表 ----------
    private function catalogReports(): array
    {
        $tx = new TransactionModel();

        return [
            'inventory' => [
                'title' => '庫存查詢',
                'columns' => [
                    $this->col('p_code', '商品編號'), $this->col('p_name', '商品名稱'),
                    $this->col('w_name', '倉庫'), $this->col('ps_qty', '在庫量', 'number'),
                    $this->col('p_cost_price', '成本單價', 'money'), $this->col('value', '庫存金額', 'money'),
                ],
                'rows' => fn() => array_map(function ($r) {
                    $r['value'] = (int) $r['ps_qty'] * (int) $r['p_cost_price']; return $r;
                }, $this->sql("SELECT ps.*, p.p_code, p.p_name, p.p_cost_price, w.w_name
                    FROM product_stock ps
                    LEFT JOIN products p ON p.p_id = ps.ps_p_id
                    LEFT JOIN warehouses w ON w.w_id = ps.ps_w_id
                    ORDER BY p.p_code, w.w_name")),
            ],
            'stock-movement' => [
                'title' => '庫存異動明細', 'orientation' => 'L',
                'columns' => [
                    $this->col('sm_date', '日期'), $this->col('sm_type', '異動類型'),
                    $this->col('sm_direction', '進出'), $this->col('p_code', '商品編號'),
                    $this->col('p_name', '商品名稱'), $this->col('w_name', '倉庫'),
                    $this->col('sm_qty', '數量', 'number'),
                    $this->col('sm_ref_type', '來源單別'), $this->col('sm_ref_no', '來源單號'),
                    $this->col('sm_note', '備註'),
                ],
                'rows' => fn() => $this->sql("SELECT m.*, p.p_code, p.p_name, w.w_name
                    FROM stock_movements m
                    LEFT JOIN products p ON p.p_id = m.sm_p_id
                    LEFT JOIN warehouses w ON w.w_id = m.sm_w_id
                    ORDER BY m.sm_date DESC, m.sm_id DESC"),
            ],
            'inventory-valuation' => [
                'title' => '存貨計價結轉',
                'columns' => [
                    $this->col('p_code', '商品編號'), $this->col('p_name', '商品名稱'),
                    $this->col('qty', '總在庫', 'number'), $this->col('p_cost_price', '成本單價', 'money'),
                    $this->col('value', '存貨價值', 'money'),
                ],
                'rows' => fn() => array_map(function ($r) {
                    $r['value'] = (int) $r['qty'] * (int) $r['p_cost_price']; return $r;
                }, $this->sql("SELECT p.p_code, p.p_name, p.p_cost_price,
                        COALESCE((SELECT SUM(ps.ps_qty) FROM product_stock ps WHERE ps.ps_p_id = p.p_id),0) qty
                    FROM products p ORDER BY p.p_code")),
            ],
            'sales-report' => [
                'title' => '銷售統計',
                'columns' => [
                    $this->col('c_name', '客戶'), $this->col('cnt', '訂單數', 'number'),
                    $this->col('amount', '銷售金額', 'money'),
                ],
                'rows' => fn() => $this->sql("SELECT c.c_name, COUNT(o.o_id) cnt, COALESCE(SUM(o.o_total_amount),0) amount
                    FROM orders o LEFT JOIN customers c ON c.c_id = o.o_c_id
                    WHERE o.o_status <> 'cancelled' GROUP BY o.o_c_id, c.c_name ORDER BY amount DESC"),
            ],
            'purchase-report' => [
                'title' => '採購統計',
                'columns' => [
                    $this->col('s_name', '廠商'), $this->col('cnt', '採購單數', 'number'),
                    $this->col('amount', '採購金額', 'money'),
                ],
                'rows' => fn() => $this->sql("SELECT s.s_name, COUNT(po.po_id) cnt, COALESCE(SUM(po.po_total),0) amount
                    FROM purchase_orders po LEFT JOIN suppliers s ON s.s_id = po.po_s_id
                    GROUP BY po.po_s_id, s.s_name ORDER BY amount DESC"),
            ],
            'ledger' => [
                'title' => '會計總帳',
                'columns' => [
                    $this->col('ac_code', '科目代碼'), $this->col('ac_name', '會計科目', 'text', 26),
                    $this->col('ac_category', '類別'), $this->col('ac_tier', '損益歸屬'),
                    $this->col('cnt', '筆數', 'number'),
                    $this->col('debit_in', '收（含稅）', 'money'), $this->col('credit_out', '付（含稅）', 'money'),
                ],
                'rows' => function () use ($tx) { return $tx->ledger($this->get('ym')); },
                'subtitle' => $this->get('ym') ? '期間 ' . $this->get('ym') : '全部期間',
            ],
            'cashflow' => [
                'title' => '資金餘額表',
                'columns' => [
                    $this->col('ym', '月份'), $this->col('open', '期初結餘', 'money'),
                    $this->col('in', '營業收現', 'money'), $this->col('out', '營業付現', 'money'),
                    $this->col('net', '本期淨變動', 'money'), $this->col('close', '期末結餘', 'money'),
                ],
                'rows' => function () use ($tx) {
                    $years = $tx->availableYears();
                    $year = (int) ($this->get('year') ?: ($years[0] ?? date('Y')));
                    return $tx->cashflow($year, $tx->cashOpeningBefore($year));
                },
                'totals' => false,
                'subtitle' => ($this->get('year') ?: date('Y')) . ' 年度（收付實現制、含稅）',
                'note' => '期初結餘承接前一年度累計；僅計入「已收付」交易。',
            ],
            'pnl' => [
                'title' => '四階損益分析', 'orientation' => 'L',
                'columns' => array_merge(
                    [$this->col('item', '項目', 'text', 22)],
                    array_map(fn($s) => $this->col($s, $s . ' ' . (TransactionModel::segments()[$s] ?? ''), 'money'),
                        TransactionModel::plSegments()),
                    [$this->col('total', '合計', 'money'), $this->col('pct', '% 佔收入')]
                ),
                'rows' => function () use ($tx) {
                    $range = $tx->periodRange();
                    $from = $this->get('from', $range['min']);
                    $to = $this->get('to', $range['max']);
                    $rep = $tx->pnl($from, $to, $this->get('basis') === 'gross');
                    $m = $rep['matrix'];
                    $rev = (int) ($m['營業收入']['total'] ?? 0);
                    $pct = fn($v) => $rev ? number_format($v / $rev * 100, 1) . '%' : '—';

                    $lines = [
                        ['一階收入', $m['營業收入']], ['一階成本', $m['一階成本']], ['一階毛利', $rep['gp1']],
                        ['二階費用', $m['二階費用']], ['二階毛利', $rep['gp2']],
                        ['三階費用', $m['三階費用']], ['三階毛利', $rep['gp3']],
                        ['四階費用', $m['四階費用']], ['四階毛利（≈營業利益）', $rep['gp4']],
                    ];
                    $out = [];
                    foreach ($lines as [$label, $vals]) {
                        $row = ['item' => $label];
                        foreach (TransactionModel::plSegments() as $s) $row[$s] = (int) ($vals[$s] ?? 0);
                        $row['total'] = (int) ($vals['total'] ?? 0);
                        $row['pct'] = $pct($row['total']);
                        $out[] = $row;
                    }
                    return $out;
                },
                'subtitle' => '期間 ' . $this->get('from', $this->txRange()['min']) . ' ~ ' . $this->get('to', $this->txRange()['max'])
                    . '（' . ($this->get('basis') === 'gross' ? '含稅' : '未稅') . '、收付實現制）',
                'note' => '共用人事與管理費用歸於 M-0；預算標準：一階毛利率 65%、二階費用率 25%、四階毛利率 12.5%。',
                'totals' => false,
            ],
            // ----- 會計三帳簿（與畫面共用 AccountingBooks 的計算） -----
            'books-journal' => [
                'title' => '日記帳', 'orientation' => 'L',
                'columns' => [
                    $this->col('jv_date', '日期', 'text', 12), $this->col('jv_no', '傳票號', 'text', 16),
                    $this->col('ac_code', '科目代碼'), $this->col('ac_name', '會計科目', 'text', 24),
                    $this->col('summary', '摘要', 'text', 36),
                    $this->col('je_debit', '借方', 'money'), $this->col('je_credit', '貸方', 'money'),
                ],
                'rows' => function () {
                    [$from, $to, $acId] = $this->bookFilters();
                    return array_map(
                        fn($r) => $r + ['summary' => $r['je_summary'] ?: $r['jv_summary']],
                        (new \App\Libraries\AccountingBooks())->journal($from, $to, $acId)
                    );
                },
                'subtitle' => $this->bookSubtitle(),
                'note' => '序時簿：依日期順序記錄每一筆分錄，借貸總額必須相等。',
            ],
            'books-ledger' => [
                'title' => '總分類帳', 'orientation' => 'L',
                'columns' => [
                    $this->col('ac_code', '科目代碼'), $this->col('ac_name', '會計科目', 'text', 26),
                    $this->col('ac_category', '類別'),
                    $this->col('opening', '期初餘額', 'money'), $this->col('opening_side', '借/貸'),
                    $this->col('debit', '本期借方', 'money'), $this->col('credit', '本期貸方', 'money'),
                    $this->col('closing', '期末餘額', 'money'), $this->col('closing_side', '借/貸'),
                ],
                'rows' => function () {
                    [$from, $to] = $this->bookFilters();
                    return array_map(function ($r) {
                        $r['opening'] = abs($r['opening']);
                        $r['closing'] = abs($r['closing']);
                        return $r;
                    }, (new \App\Libraries\AccountingBooks())->ledger($from, $to));
                },
                'subtitle' => $this->bookSubtitle(),
                'note' => '期初餘額 ＋ 本期借方 − 本期貸方 ＝ 期末餘額。金額以絕對值表示，方向見「借/貸」欄。',
                'totals' => false,
            ],
            'books-detail' => [
                'title' => '明細分類帳', 'orientation' => 'L',
                'columns' => [
                    $this->col('ac_code', '科目代碼'), $this->col('ac_name', '會計科目', 'text', 22),
                    $this->col('jv_date', '日期', 'text', 12), $this->col('jv_no', '傳票號', 'text', 16),
                    $this->col('summary', '摘要', 'text', 30),
                    $this->col('je_debit', '借方', 'money'), $this->col('je_credit', '貸方', 'money'),
                    $this->col('balance', '累計餘額', 'money'), $this->col('side', '借/貸'),
                ],
                'rows' => function () {
                    [$from, $to, $acId] = $this->bookFilters();
                    $out = [];
                    foreach ((new \App\Libraries\AccountingBooks())->detail($from, $to, $acId) as $g) {
                        $a = $g['account'];
                        // 每個科目先放一列期初餘額，才看得出累計餘額的起點
                        $out[] = [
                            'ac_code' => $a['ac_code'], 'ac_name' => $a['ac_name'],
                            'jv_date' => '', 'jv_no' => '', 'summary' => '期初餘額',
                            'je_debit' => null, 'je_credit' => null,
                            'balance' => abs($g['opening']), 'side' => $g['opening'] ? $g['opening_side'] : '',
                        ];
                        foreach ($g['rows'] as $r) {
                            $out[] = [
                                'ac_code' => $a['ac_code'], 'ac_name' => $a['ac_name'],
                                'jv_date' => $r['jv_date'], 'jv_no' => $r['jv_no'],
                                'summary' => $r['je_summary'] ?: $r['jv_summary'],
                                'je_debit' => $r['je_debit'], 'je_credit' => $r['je_credit'],
                                'balance' => abs($r['balance']), 'side' => $r['side'],
                            ];
                        }
                    }
                    return $out;
                },
                'subtitle' => $this->bookSubtitle(),
                'note' => '逐科目列出每一筆分錄與累計餘額。餘額以絕對值表示，方向見「借/貸」欄。',
                'totals' => false,
            ],

            // ----- 四大財務報表（與畫面共用 FinancialStatementController 的計算） -----
            'fs-balance' => [
                'title' => '資產負債表',
                'columns' => [
                    $this->col('section', '區段', 'text', 14), $this->col('name', '會計科目', 'text', 30),
                    $this->col('amt', '金額', 'money'),
                ],
                'rows' => function () {
                    $d = (new FinancialStatementController())->balanceData($this->fsYear());
                    $out = [];
                    foreach ($d['assets'] as $r) $out[] = ['section' => '資產', 'name' => $r['name'], 'amt' => $r['amt']];
                    $out[] = ['section' => '資產', 'name' => '　資產總計', 'amt' => $d['tA']];
                    foreach ($d['liab'] as $r) $out[] = ['section' => '負債', 'name' => $r['name'], 'amt' => $r['amt']];
                    $out[] = ['section' => '負債', 'name' => '　負債總計', 'amt' => $d['tL']];
                    foreach ($d['equity'] as $r) $out[] = ['section' => '權益', 'name' => $r['name'], 'amt' => $r['amt']];
                    $out[] = ['section' => '權益', 'name' => '　權益總計', 'amt' => $d['tE']];
                    $out[] = ['section' => '檢核', 'name' => '負債＋權益', 'amt' => $d['tL'] + $d['tE']];
                    return $out;
                },
                'totals' => false,
                'subtitle' => $this->fsYear() . ' 年度（截至 ' . $this->fsYear() . '-12-31）',
                'note' => '資產應等於負債＋權益（含本期損益）。',
            ],
            'fs-income' => [
                'title' => '損益表',
                'columns' => [
                    $this->col('section', '區段', 'text', 16), $this->col('name', '項目', 'text', 30),
                    $this->col('amt', '金額', 'money'),
                ],
                'rows' => function () {
                    $d = (new FinancialStatementController())->incomeData($this->fsYear());
                    $out = [];
                    foreach ($d['revenue'] as $r) $out[] = ['section' => '營業收入', 'name' => $r['name'], 'amt' => $r['amt']];
                    $out[] = ['section' => '營業收入', 'name' => '　收入合計', 'amt' => $d['totRev']];
                    foreach ($d['expenseByTier'] as $tier => $items) {
                        foreach ($items as $r) $out[] = ['section' => $tier ?: '費用', 'name' => $r['name'], 'amt' => $r['amt']];
                    }
                    $out[] = ['section' => '費用', 'name' => '　費用合計', 'amt' => $d['totExp']];
                    $out[] = ['section' => '本期損益', 'name' => '收入 − 費用', 'amt' => $d['net']];
                    return $out;
                },
                'totals' => false,
                'subtitle' => $this->fsYear() . ' 年度',
            ],
            'fs-cashflow' => [
                'title' => '現金流量表',
                'columns' => [$this->col('name', '項目', 'text', 34), $this->col('amt', '金額', 'money')],
                'rows' => function () {
                    $d = (new FinancialStatementController())->cashflowData($this->fsYear());
                    return [
                        ['name' => '營業活動之現金流量', 'amt' => $d['op']],
                        ['name' => '投資活動之現金流量', 'amt' => $d['inv']],
                        ['name' => '籌資活動之現金流量', 'amt' => $d['fin']],
                        ['name' => '本期現金淨變動', 'amt' => $d['netCash']],
                        ['name' => '期初現金及約當現金', 'amt' => $d['openCash']],
                        ['name' => '期末現金及約當現金', 'amt' => $d['closeCash']],
                    ];
                },
                'totals' => false,
                'subtitle' => $this->fsYear() . ' 年度',
                'note' => '依含現金科目之傳票，按對方科目類別歸類為營業／投資／籌資活動。',
            ],
            'fs-equity' => [
                'title' => '權益變動表',
                'columns' => [
                    $this->col('name', '項目', 'text', 30),
                    $this->col('capital', '股本等權益', 'money'), $this->col('re', '保留盈餘', 'money'),
                    $this->col('total', '合計', 'money'),
                ],
                'rows' => function () {
                    $d = (new FinancialStatementController())->equityData($this->fsYear());
                    return [
                        ['name' => '期初餘額', 'capital' => $d['capOpen'], 're' => $d['reOpen'], 'total' => $d['capOpen'] + $d['reOpen']],
                        ['name' => '本期增減資', 'capital' => $d['capChange'], 're' => 0, 'total' => $d['capChange']],
                        ['name' => '本期損益', 'capital' => 0, 're' => $d['curNet'], 'total' => $d['curNet']],
                        ['name' => '期末餘額', 'capital' => $d['capClose'], 're' => $d['reClose'], 'total' => $d['capClose'] + $d['reClose']],
                    ];
                },
                'totals' => false,
                'subtitle' => $this->fsYear() . ' 年度',
            ],
            'open-item-balance' => [
                'title' => '立沖帳未沖銷餘額表', 'orientation' => 'L',
                'columns' => [
                    $this->col('ac_code', '科目代碼'), $this->col('ac_name', '會計科目'),
                    $this->col('jv_no', '傳票號'), $this->col('jv_date', '立帳日期'),
                    $this->col('je_summary', '摘要', 'text', 28),
                    $this->col('je_debit', '借方', 'money'), $this->col('je_credit', '貸方', 'money'),
                    $this->col('je_offset', '已沖銷', 'money'), $this->col('open', '未沖銷', 'money'),
                ],
                'rows' => function () {
                    $from = $this->get('from'); $to = $this->get('to');
                    $w = 'a.ac_open_item = 1 AND (e.je_debit + e.je_credit - e.je_offset) > 0';
                    $b = [];
                    if ($from) { $w .= ' AND v.jv_date >= ?'; $b[] = $from; }
                    if ($to) { $w .= ' AND v.jv_date <= ?'; $b[] = $to; }
                    return array_map(function ($r) {
                        $r['open'] = (int) $r['je_debit'] + (int) $r['je_credit'] - (int) $r['je_offset'];
                        return $r;
                    }, $this->sql("SELECT a.ac_code, a.ac_name, v.jv_no, v.jv_date, e.je_summary,
                            e.je_debit, e.je_credit, e.je_offset
                        FROM journal_entries e
                        JOIN journal_vouchers v ON v.jv_id = e.je_jv_id
                        JOIN accounts a ON a.ac_id = e.je_ac_id
                        WHERE {$w} ORDER BY a.ac_code, v.jv_date, v.jv_id", $b));
                },
            ],
        ];
    }
}
