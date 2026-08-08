<?php

namespace App\Libraries;

use App\Libraries\DocumentNumber;
use App\Models\JournalVoucherModel;
use App\Models\JournalEntryModel;
use App\Models\AccountModel;

/**
 * 收付制交易（gl_transactions）→ 複式簿記傳票（journal_vouchers / journal_entries）
 *
 * 這是兩套會計之間的橋樑：
 *   收付制那套跑 四階損益 / 資金餘額 / 會計總帳
 *   借貸那套跑 資產負債表 / 損益表 / 現金流量表 / 權益變動表
 * 過帳後兩邊才會看同一份公司內帳。
 *
 * 分錄範本（含稅拆分，確保 Σ借＝Σ貸）：
 *   收・已收付： 借 銀行存款(含稅)                        / 貸 收入科目(未稅) ＋ 貸 應付稅款(銷項稅)
 *   收・未收付： 借 應收帳款(含稅)                        / 貸 收入科目(未稅) ＋ 貸 應付稅款(銷項稅)
 *   付・已收付： 借 費用科目(未稅) ＋ 借 應付稅款(進項稅)  / 貸 銀行存款(含稅)
 *   付・未收付： 借 費用科目(未稅) ＋ 借 應付稅款(進項稅)  / 貸 應付帳款(含稅)
 *
 * 以 jv_source_type='gl' + jv_source_id=t_id 防重複，可安全重複執行。
 */
class GlJournalPoster
{
    public const SOURCE = 'gl';

    private const CASH = '銀行存款';
    private const AR   = '應收帳款';
    private const AP   = '應付帳款';
    private const TAX  = '應付稅款';

    private $db;
    private $jvModel;
    private $jeModel;
    private $accountModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->jvModel = new JournalVoucherModel();
        $this->jeModel = new JournalEntryModel();
        $this->accountModel = new AccountModel();
    }

    public function stat(): array
    {
        $total = (int) $this->db->table('gl_transactions')->countAllResults();
        $posted = (int) $this->db->table('journal_vouchers')->where('jv_source_type', self::SOURCE)->countAllResults();
        return ['total' => $total, 'posted' => $posted, 'pending' => max(0, $total - $posted)];
    }

    private function acctId(string $name): ?int
    {
        $r = $this->accountModel->where('ac_name', $name)->first();
        return $r ? (int) $r['ac_id'] : null;
    }

    /**
     * 過帳所有尚未過帳的收付交易。
     * @return array{ok:int, skipped:int, error:?string}
     */
    public function postAll(): array
    {
        $cashId = $this->acctId(self::CASH);
        $arId   = $this->acctId(self::AR);
        $apId   = $this->acctId(self::AP);
        $taxId  = $this->acctId(self::TAX);
        if (!$cashId || !$arId || !$apId || !$taxId) {
            return ['ok' => 0, 'skipped' => 0, 'error' => '缺少必要科目（銀行存款／應收帳款／應付帳款／應付稅款），請先於會計科目設定建立'];
        }

        $rows = $this->db->table('gl_transactions t')
            ->select('t.*, a.ac_name')
            ->join('accounts a', 'a.ac_id = t.t_ac_id', 'left')
            ->where("NOT EXISTS (SELECT 1 FROM journal_vouchers v WHERE v.jv_source_type = 'gl' AND v.jv_source_id = t.t_id)", null, false)
            ->orderBy('t.t_date', 'ASC')->orderBy('t.t_id', 'ASC')
            ->get()->getResultArray();

        if (!$rows) return ['ok' => 0, 'skipped' => 0, 'error' => '沒有待過帳的交易'];

        $ok = 0; $skipped = 0;
        $this->db->transStart();
        try {
            foreach ($rows as $t) {
                $net = (int) $t['t_amount'];
                $tax = (int) $t['t_tax'];
                $gross = $net + $tax;
                if ($gross <= 0 || empty($t['t_ac_id'])) { $skipped++; continue; }

                $settled = $t['t_settle_status'] === '已收付';
                $entries = [];
                if ($t['t_direction'] === '收') {
                    $entries[] = [$settled ? $cashId : $arId, $gross, 0, $settled ? self::CASH : self::AR];
                    $entries[] = [(int) $t['t_ac_id'], 0, $net, $t['ac_name'] ?? '收入'];
                    if ($tax > 0) $entries[] = [$taxId, 0, $tax, '銷項稅額'];
                } else {
                    $entries[] = [(int) $t['t_ac_id'], $net, 0, $t['ac_name'] ?? '費用'];
                    if ($tax > 0) $entries[] = [$taxId, $tax, 0, '進項稅額'];
                    $entries[] = [$settled ? $cashId : $apId, 0, $gross, $settled ? self::CASH : self::AP];
                }

                // 一律走共用的原子取號，才會同步更新 document_sequences 計數器；
                // 早期版本在這裡自行計算流水號，導致計數器落後於既有單號。
                $jvNo = DocumentNumber::daily('JV', $t['t_date']);

                $jvId = $this->jvModel->insert([
                    'jv_no' => $jvNo,
                    'jv_date' => $t['t_date'],
                    'jv_type' => $t['t_direction'] === '收' ? '收入' : '支出',
                    'jv_source_type' => self::SOURCE, 'jv_source_id' => (int) $t['t_id'],
                    'jv_summary' => mb_substr(trim(($t['t_partner'] ? $t['t_partner'] . ' ' : '') . $t['t_summary']), 0, 255),
                    'jv_amount' => $gross,
                ]);

                $sort = 10;
                foreach ($entries as [$acId, $d, $c, $memo]) {
                    $this->jeModel->insert([
                        'je_jv_id' => $jvId, 'je_ac_id' => $acId,
                        'je_debit' => $d, 'je_credit' => $c,
                        'je_sort' => $sort, 'je_summary' => $memo,
                    ]);
                    $sort += 10;
                }
                $ok++;
            }
            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                return ['ok' => 0, 'skipped' => 0, 'error' => '過帳失敗，已全部回復'];
            }
        } catch (\Exception $e) {
            $this->db->transRollback();
            return ['ok' => 0, 'skipped' => 0, 'error' => '過帳失敗：' . $e->getMessage()];
        }

        return ['ok' => $ok, 'skipped' => $skipped, 'error' => null];
    }

    /** 清除由收付交易自動產生的傳票（手動傳票與其他來源不受影響） */
    public function clear(): int
    {
        $ids = array_column(
            $this->db->table('journal_vouchers')->select('jv_id')->where('jv_source_type', self::SOURCE)->get()->getResultArray(),
            'jv_id'
        );
        if (!$ids) return 0;

        $this->db->transStart();
        foreach (array_chunk($ids, 500) as $chunk) {
            $this->db->table('journal_entries')->whereIn('je_jv_id', $chunk)->delete();
            $this->db->table('journal_vouchers')->whereIn('jv_id', $chunk)->delete();
        }
        $this->db->transComplete();
        return count($ids);
    }
}
