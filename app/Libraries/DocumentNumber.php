<?php

namespace App\Libraries;

/**
 * 單號產生器（多人同時使用時仍保證唯一）
 *
 * 原本各 Model 的寫法是「SELECT 最大號 → PHP 加一 → INSERT」，兩個動作之間沒有鎖，
 * 兩人同一秒儲存就會拿到同一個號。這裡改用 MySQL/MariaDB 的原子遞增寫法：
 *
 *   INSERT INTO document_sequences (...) VALUES (..., LAST_INSERT_ID(1))
 *   ON DUPLICATE KEY UPDATE ds_last_no = LAST_INSERT_ID(ds_last_no + 1)
 *
 * 這是「單一 SQL 敘述」，由資料庫保證原子性 —— 不需要外層交易、不會有兩個連線拿到同一個值，
 * 也不會因為某個連線的交易還沒 commit 而讓其他人重號。
 *
 * 加上 migration 為單號欄位建立的 UNIQUE 索引，就算日後有人繞過本類別自己組號，
 * 資料庫仍會擋下重複值。
 */
class DocumentNumber
{
    /**
     * 取得下一個流水號（整數）。
     *
     * @param string $scope  單別，例如 'PO'、'JV'、'PAY'
     * @param string $period 期別，日單號傳 'Ymd'，不分期者傳 ''
     */
    public static function next(string $scope, string $period = ''): int
    {
        $db = \Config\Database::connect();

        $db->query(
            'INSERT INTO document_sequences (ds_scope, ds_period, ds_last_no, ds_updated_at)
             VALUES (?, ?, LAST_INSERT_ID(1), ?)
             ON DUPLICATE KEY UPDATE
                ds_last_no = LAST_INSERT_ID(ds_last_no + 1),
                ds_updated_at = VALUES(ds_updated_at)',
            [$scope, $period, date('Y-m-d H:i:s')]
        );

        return (int) $db->query('SELECT LAST_INSERT_ID() AS n')->getRow()->n;
    }

    /**
     * 預覽「下一個號碼會長怎樣」，**不會佔號**。
     *
     * 給表單即時顯示用（例如改日期時同步更新畫面上的單號）。因為沒有真的遞增，
     * 兩個人同時看可能看到同一個號 —— 這沒關係，實際號碼一律在存檔時用
     * daily() 原子取號決定，畫面上的只是預覽。
     */
    public static function preview(string $scope, ?string $date = null, ?string $prefix = null, int $pad = 3): string
    {
        $ymd = date('Ymd', $date ? strtotime($date) : time());
        $db = \Config\Database::connect();

        $row = $db->query(
            'SELECT ds_last_no FROM document_sequences WHERE ds_scope = ? AND ds_period = ?',
            [$scope, $ymd]
        )->getRow();

        $next = ((int) ($row->ds_last_no ?? 0)) + 1;

        return ($prefix ?? $scope) . $ymd . '-' . str_pad((string) $next, $pad, '0', STR_PAD_LEFT);
    }

    /**
     * 日單號：<PREFIX><Ymd>-<nnn>，例如 PO20260808-001。
     *
     * @param string      $scope  計數範圍（通常等於前綴）
     * @param string|null $date   單據日期，null＝今天
     * @param string|null $prefix 顯示用前綴，null＝與 $scope 相同
     */
    public static function daily(string $scope, ?string $date = null, ?string $prefix = null, int $pad = 3): string
    {
        $ymd = date('Ymd', $date ? strtotime($date) : time());
        $n = self::next($scope, $ymd);

        return ($prefix ?? $scope) . $ymd . '-' . str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
    }

    /**
     * 不分期流水號：<PREFIX><nnnnnnnn>，例如發票 AA00000001。
     */
    public static function serial(string $scope, string $prefix, int $pad = 8): string
    {
        $n = self::next($scope, '');

        return $prefix . str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
    }
}
