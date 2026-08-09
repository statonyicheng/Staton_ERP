<?php

namespace App\Libraries;

/**
 * 每頁顯示筆數（全站共用）
 *
 * 各列表頁原本把每頁筆數寫死在 Model／Controller 裡（10、12、15、20、30 都有），
 * 使用者無法自己決定一頁要看幾筆。本類別把它統一成：
 *
 *   1. 網址帶 ?per_page=100 → 使用該值，並記進 session
 *   2. 沒帶參數 → 沿用 session 裡上次選的值（換頁、換模組都會延續）
 *   3. 都沒有 → 用呼叫端傳入的預設值（保留各模組原本的手感）
 *
 * 只接受白名單內的數字，避免有人塞 ?per_page=999999 把資料庫拖垮。
 *
 * 用法（Model 或 Controller）：
 *   $perPage = \App\Libraries\PageSize::get(10);
 *
 * 畫面上的下拉選單由 components/pagination.php 產生。
 */
class PageSize
{
    /** 可選的每頁筆數 */
    public const OPTIONS = [10, 25, 50, 100, 200];

    /** 網址參數名稱 */
    public const PARAM = 'per_page';

    private const SESSION_KEY = 'erp_page_size';

    public static function get(int $default = 10): int
    {
        // CLI（spark 指令、匯出）沒有請求與 session，直接用預設值
        if (is_cli()) {
            return $default;
        }

        $request = service('request');
        $fromUrl = $request->getGet(self::PARAM);

        if ($fromUrl !== null && self::isValid($fromUrl)) {
            session()->set(self::SESSION_KEY, (int) $fromUrl);
            return (int) $fromUrl;
        }

        $saved = session()->get(self::SESSION_KEY);
        if ($saved !== null && self::isValid($saved)) {
            return (int) $saved;
        }

        return $default;
    }

    /** 目前生效的值（給畫面上的下拉選單標示選中項用） */
    public static function current(int $default = 10): int
    {
        return self::get($default);
    }

    private static function isValid($value): bool
    {
        return is_numeric($value) && in_array((int) $value, self::OPTIONS, true);
    }
}
