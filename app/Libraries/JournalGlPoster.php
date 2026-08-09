<?php

namespace App\Libraries;

/**
 * 複式簿記傳票（journal_vouchers / journal_entries）→ 收付制交易（gl_transactions）
 *
 * 這是 GlJournalPoster 的反向。之所以需要它：四階損益分析與資金餘額表是讀
 * gl_transactions 算出來的，不是讀傳票。當日常輸入改成只開分錄傳票之後，
 * 沒有這座反向橋，那兩張報表會**靜靜地變成 0** —— 不報錯，只是數字消失。
 *
 * 推導規則（正好是 GlJournalPoster 分錄範本的逆運算）：
 *   · 每一筆「損益科目」的分錄 → 一筆收付交易
 *       貸方損益科目（收入增加）→ 收
 *       借方損益科目（費用增加）→ 付
 *   · 未稅金額 = 該筆分錄的金額；稅額 = 傳票上「應付稅款」的金額，
 *     依未稅金額比例分攤到各損益科目（絕大多數傳票只有一個損益科目，全額歸它）
 *   · 收付狀態 = 傳票有動到現金/銀行存款 → 已收付；只掛應收/應付 → 未收付
 *   · 商業模式 = 傳票的 jv_segment
 *
 * 沒有任何損益科目的傳票（例如「借現金／貸應收」的收款分錄、純資產負債調整）
 * **不會產生收付交易** —— 那是資金的移動而非損益的發生，重複產生會讓收入
 * 被計算兩次。這類傳票對資金餘額表的影響要靠立沖帳把原交易改為已收付，
 * 屬於下一階段。
 *
 * 以 gl_transactions.t_jv_id 記住來源傳票，傳票被修改或刪除時同步重建／回收，
 * 因此可安全重複執行。
 */
class JournalGlPoster
{
    /** 由傳票反向產生的收付交易，t_source 一律標這個 */
    public const SOURCE = 'journal';

    private const CASH_NAMES = ['銀行存款', '現金'];
    private const AR_NAME    = '應收帳款';
    private const AP_NAME    = '應付帳款';
    private const TAX_NAME   = '應付稅款';

    private $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * 依傳票重建它對應的收付交易（先清掉舊的再產生，內容才不會殘留）。
     *
     * @return int 產生的收付交易筆數
     */
    public function sync(int $jvId): int
    {
        $voucher = $this->db->table('journal_vouchers')->where('jv_id', $jvId)->get()->getRowArray();
        if (! $voucher) {
            $this->remove($jvId);
            return 0;
        }

        // 由收付交易正向產生的傳票不可以再反推回去 —— 原始交易已經在資料庫裡，
        // 再產生一筆就會變成同一件事被記錄兩次，報表金額直接翻倍。
        if (($voucher['jv_source_type'] ?? '') === GlJournalPoster::SOURCE) {
            $this->remove($jvId);
            return 0;
        }

        $rows = $this->derive($voucher);

        $this->db->table('gl_transactions')->where('t_jv_id', $jvId)->delete();
        if ($rows !== []) {
            $this->db->table('gl_transactions')->insertBatch($rows);
        }

        return count($rows);
    }

    /** 傳票被刪除時，一起回收它產生的收付交易 */
    public function remove(int $jvId): int
    {
        $n = $this->db->table('gl_transactions')->where('t_jv_id', $jvId)->countAllResults(false);
        $this->db->table('gl_transactions')->where('t_jv_id', $jvId)->delete();

        return $n;
    }

    /**
     * 從一張傳票推導出應有的收付交易（純計算，不寫入）。
     * 對外開放是為了讓 round-trip 驗證可以在不碰資料的情況下比對。
     *
     * @return array<int, array<string, mixed>> 可直接 insert 的資料列
     */
    public function derive(array $voucher): array
    {
        $entries = $this->db->table('journal_entries e')
            ->select('e.*, a.ac_name, a.ac_is_pl')
            ->join('accounts a', 'a.ac_id = e.je_ac_id', 'left')
            ->where('e.je_jv_id', (int) $voucher['jv_id'])
            ->orderBy('e.je_sort', 'ASC')->orderBy('e.je_id', 'ASC')
            ->get()->getResultArray();

        if ($entries === []) {
            return [];
        }

        $plLines  = [];
        $taxTotal = 0;
        $hasCash  = false;
        $arApLines = [];

        foreach ($entries as $e) {
            $name  = (string) ($e['ac_name'] ?? '');
            $debit = (int) $e['je_debit'];
            $credit = (int) $e['je_credit'];

            if ($name === self::TAX_NAME) {
                $taxTotal += $debit + $credit;
                continue;
            }
            if (in_array($name, self::CASH_NAMES, true)) {
                $hasCash = true;
                continue;
            }
            if ($name === self::AR_NAME || $name === self::AP_NAME) {
                $arApLines[] = $e;
                continue;
            }
            if (! empty($e['ac_is_pl'])) {
                $plLines[] = $e;
            }
        }

        // 沒有損益科目 → 純資金/資產負債移動，不是損益的發生
        if ($plLines === []) {
            return [];
        }

        $netTotal = 0;
        foreach ($plLines as $e) {
            $netTotal += (int) $e['je_debit'] + (int) $e['je_credit'];
        }
        if ($netTotal <= 0) {
            return [];
        }

        // 收付狀態：
        //   1. 有動到現金/銀行存款 → 當場收付，已收付
        //   2. 掛應收/應付 → 要看立沖帳：全部沖銷完才算已收付，收付日＝最後一次沖銷日
        //      （賒銷 8 月立帳、10 月收到錢，資金餘額表要算在 10 月）
        //   3. 兩者皆無（純轉帳）→ 視為已收付，不影響資金
        $settled = $hasCash;
        $settleDate = $voucher['jv_date'];

        if (! $hasCash && $arApLines !== []) {
            $allOffset = true;
            $lastOffsetDate = null;

            foreach ($arApLines as $e) {
                $amount = (int) $e['je_debit'] + (int) $e['je_credit'];
                if ((int) $e['je_offset'] < $amount) {
                    $allOffset = false;
                    break;
                }
                if (! empty($e['je_offset_date']) && $e['je_offset_date'] > (string) $lastOffsetDate) {
                    $lastOffsetDate = $e['je_offset_date'];
                }
            }

            $settled = $allOffset;
            if ($allOffset && $lastOffsetDate) {
                $settleDate = $lastOffsetDate;
            }
        } elseif (! $hasCash && $arApLines === []) {
            $settled = true;
        }

        $now     = date('Y-m-d H:i:s');
        $rows    = [];
        $taxLeft = $taxTotal;

        foreach ($plLines as $i => $e) {
            $net = (int) $e['je_debit'] + (int) $e['je_credit'];

            // 稅額依未稅比例分攤；最後一筆吃掉除不盡的餘數，總額才不會少一元
            $tax = ($i === count($plLines) - 1)
                ? $taxLeft
                : (int) round($taxTotal * $net / $netTotal);
            $taxLeft -= $tax;

            $rows[] = [
                't_date'          => $voucher['jv_date'],
                't_ym'            => substr((string) $voucher['jv_date'], 0, 7),
                't_summary'       => mb_substr((string) ($e['je_summary'] ?: $voucher['jv_summary'] ?: '分錄傳票'), 0, 255),
                't_partner'       => null,
                // 貸方損益科目＝收入增加＝收；借方＝費用發生＝付
                't_direction'     => ((int) $e['je_credit'] > 0) ? '收' : '付',
                't_segment'       => $voucher['jv_segment'] ?? 'M-0',
                't_ac_id'         => (int) $e['je_ac_id'],
                't_amount'        => $net,
                't_tax'           => $tax,
                't_settle_status' => $settled ? '已收付' : '未收付',
                't_settle_date'   => $settled ? $settleDate : null,
                't_note'          => $voucher['jv_note'] ?? null,
                't_source'        => self::SOURCE,
                't_jv_id'         => (int) $voucher['jv_id'],
                't_created_at'    => $now,
                't_updated_at'    => $now,
            ];
        }

        return $rows;
    }
}
