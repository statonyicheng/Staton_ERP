<?php

namespace App\Commands;

use App\Libraries\JournalGlPoster;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 賒銷 → 收款 → 立沖帳 的端到端測試。
 *
 * 這條線是「日常只開分錄傳票」最容易出錯的地方：
 * 賒銷立帳時錢還沒進來（未收付），收到錢時開的是「借銀行/貸應收」——
 * 那張傳票沒有損益科目，不該再產生一筆收入（否則收入被記兩次），
 * 但資金餘額表又必須看到這筆現金流入。答案是靠立沖帳把兩張傳票勾起來，
 * 沖銷完成時把原本那筆收付交易翻成「已收付」，收付日期＝實際沖銷日。
 *
 * 驗證重點：
 *   1. 賒銷傳票 → 收付交易「未收付」，資金餘額表不動、四階損益已認列
 *   2. 收款傳票 → 不產生收付交易（沒有損益科目）
 *   3. 沖銷後 → 原交易變「已收付」，收付日期是沖銷日（不是立帳日）
 *   4. 資金餘額表在沖銷月份才看到這筆錢
 *
 * 測試資料自建自清，可重複執行。
 *
 *   php spark erp:openitem-selftest
 */
class OpenItemSelfTest extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:openitem-selftest';
    protected $description = '端到端驗證：賒銷立帳 → 收款 → 立沖帳沖銷後收付狀態與資金餘額表';

    private const MARK = '__OI測試__';
    private const BILL_DATE   = '2026-08-15';   // 立帳日
    private const OFFSET_DATE = '2026-10-20';   // 實際收到錢的日子（刻意跨月）

    private $db;
    private int $pass = 0;
    private int $fail = 0;

    private function check(string $name, bool $ok, string $detail = ''): void
    {
        if ($ok) { $this->pass++; CLI::write("  [PASS] {$name}", 'green'); }
        else { $this->fail++; CLI::write("  [FAIL] {$name}　{$detail}", 'red'); }
    }

    public function run(array $params)
    {
        $this->db = \Config\Database::connect();
        $poster = new JournalGlPoster();

        $ids = ['bill' => null, 'receipt' => null];

        try {
            $this->purge();

            $ar = $this->acct('應收帳款');
            $bank = $this->acct('銀行存款');
            $tax = $this->acct('應付稅款');
            $income = $this->db->table('accounts')->where('ac_is_pl', 1)->where('ac_tier', '營業收入')
                ->orderBy('ac_code', 'ASC')->get()->getRowArray();

            if (! $ar || ! $bank || ! $tax || ! $income) {
                CLI::error('缺少必要科目（應收帳款／銀行存款／應付稅款／營業收入），無法測試');
                return;
            }
            $incomeId = (int) $income['ac_id'];

            // ---------- 1. 賒銷立帳 ----------
            CLI::write('賒銷立帳：借 應收 10,500 / 貸 收入 10,000 + 稅 500', 'light_blue');
            $ids['bill'] = $this->voucher(self::BILL_DATE, 'M-1', self::MARK . ' 賒銷立帳', [
                [$ar, 10500, 0], [$incomeId, 0, 10000], [$tax, 0, 500],
            ]);
            $poster->sync($ids['bill']);

            $gl = $this->glOf($ids['bill']);
            $this->check('賒銷產生 1 筆收付交易', count($gl) === 1, '實得 ' . count($gl) . ' 筆');
            if ($gl) {
                $this->check('狀態為「未收付」（錢還沒進來）', $gl[0]['t_settle_status'] === '未收付', '實得 ' . $gl[0]['t_settle_status']);
                $this->check('未稅 10,000／稅 500', (int) $gl[0]['t_amount'] === 10000 && (int) $gl[0]['t_tax'] === 500,
                    "實得 {$gl[0]['t_amount']}／{$gl[0]['t_tax']}");
                $this->check('商業模式沿用傳票（M-1）', $gl[0]['t_segment'] === 'M-1', '實得 ' . $gl[0]['t_segment']);
            }
            $this->check('資金餘額表尚未認列（未收付不計入）', $this->cashIn(self::BILL_DATE) === 0,
                '立帳當月現金流入應為 0，實得 ' . $this->cashIn(self::BILL_DATE));
            $this->check('四階損益已認列收入（權責基礎）', $this->pnlIncome() >= 10000,
                '實得 ' . $this->pnlIncome());

            // ---------- 2. 收款傳票 ----------
            CLI::newLine();
            CLI::write('收款：借 銀行 10,500 / 貸 應收 10,500', 'light_blue');
            $ids['receipt'] = $this->voucher(self::OFFSET_DATE, 'M-1', self::MARK . ' 收款', [
                [$bank, 10500, 0], [$ar, 0, 10500],
            ]);
            $poster->sync($ids['receipt']);

            $this->check('收款傳票不產生收付交易（沒有損益科目，避免收入被記兩次）',
                count($this->glOf($ids['receipt'])) === 0);

            // ---------- 3. 立沖帳 ----------
            CLI::newLine();
            CLI::write('立沖帳：把兩張傳票的應收勾銷，沖銷日 ' . self::OFFSET_DATE, 'light_blue');
            $this->offset($ids['bill'], $ids['receipt'], $ar, 10500, self::OFFSET_DATE);
            $poster->sync($ids['bill']);
            $poster->sync($ids['receipt']);

            $gl = $this->glOf($ids['bill']);
            $this->check('沖銷後狀態變「已收付」', $gl && $gl[0]['t_settle_status'] === '已收付',
                '實得 ' . ($gl[0]['t_settle_status'] ?? '無資料'));
            $this->check('收付日期＝沖銷日（不是立帳日）', $gl && $gl[0]['t_settle_date'] === self::OFFSET_DATE,
                '實得 ' . ($gl[0]['t_settle_date'] ?? '無'));
            $this->check('資金餘額表在沖銷月份才看到這筆錢（10,500 含稅）',
                $this->cashIn(self::OFFSET_DATE) === 10500, '實得 ' . $this->cashIn(self::OFFSET_DATE));
            $this->check('立帳月份仍為 0（沒有被算兩次）', $this->cashIn(self::BILL_DATE) === 0,
                '實得 ' . $this->cashIn(self::BILL_DATE));

        } catch (\Throwable $e) {
            $this->fail++;
            CLI::write('  [ERROR] ' . get_class($e) . '：' . $e->getMessage(), 'red');
            CLI::write('          ' . $e->getFile() . ':' . $e->getLine(), 'dark_gray');
        } finally {
            $this->purge();
            CLI::newLine();
            CLI::write('（測試資料已清除）', 'dark_gray');
        }

        CLI::newLine();
        CLI::write(str_repeat('=', 58), 'dark_gray');
        CLI::write("立沖帳端到端測試：通過 {$this->pass}　失敗 {$this->fail}", $this->fail ? 'red' : 'green');
    }

    private function acct(string $name): ?int
    {
        $r = $this->db->table('accounts')->where('ac_name', $name)->get()->getRowArray();
        return $r ? (int) $r['ac_id'] : null;
    }

    /** 建立一張傳票（entries: [科目id, 借, 貸]） */
    private function voucher(string $date, string $segment, string $summary, array $entries): int
    {
        $amount = array_sum(array_column($entries, 1));
        $this->db->table('journal_vouchers')->insert([
            'jv_no' => \App\Libraries\DocumentNumber::daily('JV', $date),
            'jv_date' => $date, 'jv_type' => '轉帳', 'jv_segment' => $segment,
            'jv_summary' => $summary, 'jv_amount' => $amount,
            'jv_created_at' => date('Y-m-d H:i:s'), 'jv_updated_at' => date('Y-m-d H:i:s'),
        ]);
        $jvId = (int) $this->db->insertID();

        $sort = 10;
        foreach ($entries as [$acId, $debit, $credit]) {
            $this->db->table('journal_entries')->insert([
                'je_jv_id' => $jvId, 'je_ac_id' => $acId,
                'je_debit' => $debit, 'je_credit' => $credit, 'je_sort' => $sort,
            ]);
            $sort += 10;
        }

        return $jvId;
    }

    /** 模擬立沖帳：把兩張傳票中該科目的分錄互相沖銷 */
    private function offset(int $jvA, int $jvB, int $acId, int $amount, string $date): void
    {
        foreach ([$jvA, $jvB] as $jvId) {
            $e = $this->db->table('journal_entries')
                ->where('je_jv_id', $jvId)->where('je_ac_id', $acId)->get()->getRowArray();
            if (! $e) continue;
            $this->db->table('journal_entries')->where('je_id', $e['je_id'])
                ->update(['je_offset' => $amount, 'je_offset_date' => $date]);
        }
    }

    /** 某傳票產生的收付交易 */
    private function glOf(int $jvId): array
    {
        return $this->db->table('gl_transactions')->where('t_jv_id', $jvId)
            ->orderBy('t_id', 'ASC')->get()->getResultArray();
    }

    /** 指定日期所屬月份、由測試傳票造成的現金流入（含稅、僅已收付） */
    private function cashIn(string $date): int
    {
        $ym = substr($date, 0, 7);
        $r = $this->db->query(
            'SELECT COALESCE(SUM(t_amount + t_tax), 0) n FROM gl_transactions
              WHERE t_settle_status = ? AND t_direction = ? AND LEFT(t_settle_date, 7) = ?
                AND t_summary LIKE ?',
            ['已收付', '收', $ym, '%' . self::MARK . '%']
        )->getRow();

        return (int) ($r->n ?? 0);
    }

    /** 測試傳票造成的損益收入（不分收付狀態） */
    private function pnlIncome(): int
    {
        $r = $this->db->query(
            'SELECT COALESCE(SUM(t_amount), 0) n FROM gl_transactions
              WHERE t_direction = ? AND t_summary LIKE ?',
            ['收', '%' . self::MARK . '%']
        )->getRow();

        return (int) ($r->n ?? 0);
    }

    /** 清掉測試傳票與其產生的收付交易 */
    private function purge(): void
    {
        $jvIds = array_column(
            $this->db->table('journal_vouchers')->select('jv_id')->like('jv_summary', self::MARK)->get()->getResultArray(),
            'jv_id'
        );
        if ($jvIds) {
            $this->db->table('gl_transactions')->whereIn('t_jv_id', $jvIds)->delete();
            $this->db->table('journal_entries')->whereIn('je_jv_id', $jvIds)->delete();
            $this->db->table('journal_vouchers')->whereIn('jv_id', $jvIds)->delete();
        }
        $this->db->table('gl_transactions')->like('t_summary', self::MARK)->delete();
    }
}
