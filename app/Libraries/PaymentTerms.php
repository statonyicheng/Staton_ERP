<?php

namespace App\Libraries;

/**
 * 付款條件 → 到期日的推算。
 *
 * 帳齡分析的「逾期幾天」不能用傳票日期直接算 —— 月結客戶當月開的帳本來就還沒到期，
 * 用傳票日算會把還沒到期的通通算成逾期。要先依付款條件推出**到期日**，
 * 逾期天數＝今天 − 到期日（未到期為 0）。
 *
 * 三種類型：
 *   immediate  即期／預收付       到期日＝傳票日
 *   net        發票日起算 N 天    到期日＝傳票日 + N
 *   eom        月結、次月 N 日付  到期日＝次月的第 N 日（N 超過該月天數就取月底）
 */
class PaymentTerms
{
    public const TYPES = [
        'immediate' => '即期／預收付',
        'net'       => '發票日起算 N 天',
        'eom'       => '月結，次月 N 日',
    ];

    /**
     * 依付款條件推算到期日。
     *
     * @param string      $baseDate 傳票／帳單日期 Y-m-d
     * @param string|null $type     付款條件類型；null 或未知一律當即期
     * @param int         $days     net＝天數；eom＝次月第幾日
     */
    public static function dueDate(string $baseDate, ?string $type, int $days = 0): string
    {
        $ts = strtotime($baseDate);
        if ($ts === false) {
            return $baseDate;
        }

        switch ($type) {
            case 'net':
                return date('Y-m-d', strtotime("+{$days} days", $ts));

            case 'eom':
                $day = max(1, $days);
                $firstOfNext = strtotime('first day of next month', $ts);
                // 次月沒有第 31 日這種情況就落在月底，不要溢出到再下個月
                $lastDay = (int) date('t', $firstOfNext);

                return date('Y-m-', $firstOfNext) . str_pad((string) min($day, $lastDay), 2, '0', STR_PAD_LEFT);

            case 'immediate':
            default:
                return date('Y-m-d', $ts);
        }
    }

    /** 逾期天數（未到期回 0） */
    public static function overdueDays(string $dueDate, ?string $today = null): int
    {
        $today ??= date('Y-m-d');
        $diff = (strtotime($today) - strtotime($dueDate)) / 86400;

        return $diff > 0 ? (int) floor($diff) : 0;
    }

    /** 帳齡區間標籤（依逾期天數） */
    public static function bucket(int $overdueDays): string
    {
        if ($overdueDays <= 0) return '未到期';
        if ($overdueDays <= 30) return '逾期 1-30 天';
        if ($overdueDays <= 60) return '逾期 31-60 天';
        if ($overdueDays <= 90) return '逾期 61-90 天';

        return '逾期 90 天以上';
    }

    /** 報表欄位順序（固定，缺的區間也要出現，才看得出「這一段沒有」） */
    public const BUCKETS = ['未到期', '逾期 1-30 天', '逾期 31-60 天', '逾期 61-90 天', '逾期 90 天以上'];

    /** 人看的付款條件說明 */
    public static function describe(?string $type, int $days): string
    {
        switch ($type) {
            case 'net': return "發票日起 {$days} 天";
            case 'eom': return "月結，次月 {$days} 日";
            case 'immediate': return '即期／預收付';
            default: return '未設定';
        }
    }
}
