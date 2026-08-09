<?php

namespace Config;

/**
 * 角色權限對照（多人協作的核心設定）
 *
 * 一個角色對每個模組只有三種狀態：
 *   'rw' 可讀可寫、'r' 唯讀、未列出＝完全不能進入
 *
 * 「寫入」的判定：POST 請求，或網址中出現 WRITE_VERBS 的動作字（本系統有不少
 * 刪除/過帳動作是 GET，例如 auto-journal/generate-gl、xxx/delete/1，必須一併攔下）。
 *
 * 要調整權限只改這一個檔案，AuthFilter 與側邊欄都會自動跟著變。
 */
class Permission
{
    /** 角色代碼 => 顯示名稱 */
    public const ROLES = [
        'admin'      => '管理者',
        'accounting' => '會計',
        'sales'      => '業務',
        'purchasing' => '採購倉管',
        'readonly'   => '唯讀',
    ];

    /** 模組代碼（＝路由第一段）=> 顯示名稱 */
    public const MODULES = [
        'customer' => '客戶資料', 'supplier' => '廠商資料', 'product' => '商品資料',
        'product-category' => '產品分類', 'pricing' => '商品價格', 'payment-method' => '結帳方式',
        'account' => '會計科目', 'business-segment' => '業務別設定',
        'quote' => '報價單', 'order' => '訂單', 'shipment' => '出貨', 'sales-report' => '銷售統計',
        'purchase-requisition' => '請購', 'purchase-order' => '採購單',
        'goods-receipt' => '進貨退貨', 'purchase-report' => '採購報表',
        'warehouse' => '倉庫', 'inventory' => '庫存查詢', 'stock-movement' => '庫存異動',
        'batch' => '批號序號', 'stocktake' => '庫存盤點', 'inventory-valuation' => '存貨計價',
        'bom' => '產品結構', 'work-order' => '製令', 'mrp' => '批次需求',
        'ledger' => '會計總帳', 'journal' => '分錄傳票', 'transaction' => '交易登錄',
        'pnl' => '四階損益', 'cashflow' => '資金餘額', 'auto-journal' => '自動分錄',
        'open-item' => '立沖帳', 'fs' => '財務報表', 'books' => '會計帳簿',
        'receivable' => '應收帳款', 'payable' => '應付帳款', 'settlement' => '收付款',
        'cost' => '成本計算', 'fixed-asset' => '固定資產', 'invoice' => '電子發票',
        'user' => '使用者管理', 'audit-log' => '操作紀錄',
    ];

    /** 一律放行（不需權限）：儀表板、個人資料、登出 */
    public const ALWAYS_ALLOWED = ['', 'profile', 'logout', 'login'];

    /** 出現這些動作字即視為寫入操作 */
    public const WRITE_VERBS = [
        'create', 'store', 'edit', 'update', 'delete', 'save', 'new',
        'generate', 'generate-gl', 'clear-gl', 'complete', 'receive', 'doreceive',
        'pay', 'dopay', 'offset', 'dooffset', 'apply', 'import', 'post', 'manage',
    ];

    /**
     * 權限矩陣。'*' 代表全部模組可讀可寫。
     * 設計原則：各角色能看到自己工作需要的資料，寫入權限只給該職務真正負責的模組。
     */
    private const MATRIX = [
        'admin' => '*',

        // 會計：帳務全權；營運單據可看不可改（要對帳但不該改業務的單）
        'accounting' => [
            'account' => 'rw', 'business-segment' => 'rw', 'ledger' => 'rw', 'journal' => 'rw', 'transaction' => 'rw',
            'pnl' => 'rw', 'cashflow' => 'rw', 'auto-journal' => 'rw', 'open-item' => 'rw',
            'fs' => 'rw', 'books' => 'rw', 'receivable' => 'rw', 'payable' => 'rw', 'settlement' => 'rw',
            'invoice' => 'rw', 'fixed-asset' => 'rw', 'cost' => 'rw',
            'customer' => 'r', 'supplier' => 'r', 'product' => 'r', 'pricing' => 'r',
            'payment-method' => 'r', 'order' => 'r', 'quote' => 'r', 'shipment' => 'r',
            'purchase-order' => 'r', 'goods-receipt' => 'r', 'inventory' => 'r',
            'inventory-valuation' => 'r', 'sales-report' => 'r', 'purchase-report' => 'r',
            'audit-log' => 'r',
        ],

        // 業務：客戶與銷售流程；看得到庫存與應收，但不能碰帳務
        'sales' => [
            'customer' => 'rw', 'quote' => 'rw', 'order' => 'rw', 'shipment' => 'rw',
            'product' => 'r', 'product-category' => 'r', 'pricing' => 'r',
            'payment-method' => 'r', 'inventory' => 'r', 'sales-report' => 'r',
            'receivable' => 'r', 'invoice' => 'r',
        ],

        // 採購倉管：採購與庫存生產；看得到應付但不能付款
        'purchasing' => [
            'supplier' => 'rw', 'product' => 'rw', 'product-category' => 'rw',
            'purchase-requisition' => 'rw', 'purchase-order' => 'rw', 'goods-receipt' => 'rw',
            'warehouse' => 'rw', 'inventory' => 'rw', 'stock-movement' => 'rw',
            'batch' => 'rw', 'stocktake' => 'rw', 'inventory-valuation' => 'rw',
            'bom' => 'rw', 'work-order' => 'rw', 'mrp' => 'rw',
            'purchase-report' => 'r', 'payable' => 'r', 'pricing' => 'r', 'cost' => 'r',
        ],

        // 唯讀：全部只能看，不能做任何異動（適合老闆、會計師、外部稽核）
        'readonly' => null,   // null = 所有模組唯讀（見 permissions()）
    ];

    /** 唯讀角色也不該碰的模組（帳號管理屬系統管理職權） */
    private const READONLY_EXCLUDE = ['user'];

    /** 取得某角色的完整權限表 [module => 'rw'|'r'] */
    public static function permissions(string $role): array
    {
        // 用 array_key_exists 而非 ??：readonly 的值就是 null，?? 會誤判成「不存在」
        $m = array_key_exists($role, self::MATRIX) ? self::MATRIX[$role] : [];

        if ($m === '*') {
            return array_fill_keys(array_keys(self::MODULES), 'rw');
        }
        if ($m === null) {   // 唯讀角色：除排除清單外，所有模組都能看
            $modules = array_diff(array_keys(self::MODULES), self::READONLY_EXCLUDE);
            return array_fill_keys($modules, 'r');
        }
        return is_array($m) ? $m : [];
    }

    /** 由網址取出模組代碼；匯出網址會對應回其資料來源模組 */
    public static function moduleOf(string $uri): string
    {
        $uri = trim($uri, '/');
        if ($uri === '') return '';

        $segments = explode('/', $uri);
        $first = $segments[0];

        // export/xlsx/<key> → 依 <key> 找出對應模組（fs-balance → fs、journal-entry → journal）
        if ($first === 'export' && isset($segments[2])) {
            $key = $segments[2];
            while ($key !== '') {
                if (isset(self::MODULES[$key])) return $key;
                $pos = strrpos($key, '-');
                if ($pos === false) break;
                $key = substr($key, 0, $pos);
            }
            return 'export';
        }

        return $first;
    }

    /** 這個請求算不算寫入操作 */
    public static function isWrite(string $uri, string $method): bool
    {
        if (strtoupper($method) !== 'GET') return true;

        foreach (explode('/', strtolower(trim($uri, '/'))) as $seg) {
            if (in_array($seg, self::WRITE_VERBS, true)) return true;
        }
        return false;
    }

    /** 判斷某角色能否執行這個請求 */
    public static function allows(string $role, string $uri, string $method): bool
    {
        $module = self::moduleOf($uri);

        if (in_array($module, self::ALWAYS_ALLOWED, true)) return true;
        if (!isset(self::MODULES[$module])) return true;   // 未登錄的路由不擋（例如 module/xxx 說明頁）

        $perm = self::permissions($role)[$module] ?? null;
        if ($perm === null) return false;                  // 沒有這個模組的權限
        if ($perm === 'rw') return true;

        return !self::isWrite($uri, $method);               // 唯讀：只放行讀取
    }

    /** 某角色是否看得到這個模組（用來過濾側邊欄） */
    public static function canView(string $role, string $module): bool
    {
        if (in_array($module, self::ALWAYS_ALLOWED, true)) return true;
        if (!isset(self::MODULES[$module])) return true;

        return isset(self::permissions($role)[$module]);
    }
}
