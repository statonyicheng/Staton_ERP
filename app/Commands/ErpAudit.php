<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * 仕坦登 ERP 底層資料一致性稽核
 *
 *   php spark erp:audit
 *
 * 檢查「資料異動後，下游是否真的跟著更新」：
 *   庫存在庫量 vs 異動紀錄、單據 vs 庫存、應收應付 vs 收付款、
 *   四階損益/資金餘額/總帳 vs 交易明細、傳票借貸平衡、資產負債表平衡、立沖帳沖銷額。
 */
class ErpAudit extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:audit';
    protected $description = '稽核 ERP 底層資料一致性（庫存、進銷存連動、應收付、會計報表）';

    private $db;
    private int $pass = 0;
    private int $fail = 0;
    private int $warn = 0;

    public function run(array $params)
    {
        $this->db = Database::connect();

        $this->section('一、庫存正確性');
        $this->checkStockVsMovements();
        $this->checkNegativeStock();
        $this->checkMovementDirection();

        $this->section('二、單據 → 庫存連動');
        $this->checkPurchaseReceipt();
        $this->checkShipmentStock();
        $this->checkWorkOrderStock();
        $this->checkShippedQty();

        $this->section('三、單號唯一性防護');
        $this->checkDocNoUnique();

        $this->section('四、應收 / 應付 / 收付款');
        $this->checkArAp('payables', 'ap', '應付');
        $this->checkArAp('receivables', 'ar', '應收');
        $this->checkSettlementTie();

        $this->section('五、會計報表勾稽');
        $this->checkPnlTie();
        $this->checkCashflowTie();
        $this->checkLedgerTie();
        $this->checkJournalBalance();
        $this->checkBalanceSheet();
        $this->checkOpenItem();
        $this->checkBooks();

        CLI::newLine();
        CLI::write(str_repeat('=', 62), 'dark_gray');
        CLI::write(sprintf('稽核結果：通過 %d　警告 %d　失敗 %d', $this->pass, $this->warn, $this->fail),
            $this->fail > 0 ? 'red' : ($this->warn > 0 ? 'yellow' : 'green'));
    }

    // ===================== 一、庫存 =====================

    /** 在庫量必須等於所有異動的淨額（入 − 出），這是庫存正確性的根本檢查 */
    private function checkStockVsMovements(): void
    {
        $mv = $this->db->query("
            SELECT sm_p_id p, sm_w_id w,
                   SUM(CASE WHEN sm_direction = '出' THEN -sm_qty ELSE sm_qty END) net
            FROM stock_movements GROUP BY sm_p_id, sm_w_id
        ")->getResultArray();
        $ps = $this->db->query("SELECT ps_p_id p, ps_w_id w, ps_qty q FROM product_stock")->getResultArray();

        $expect = [];
        foreach ($mv as $r) $expect[$r['p'] . '|' . $r['w']] = (int) $r['net'];
        $actual = [];
        foreach ($ps as $r) $actual[$r['p'] . '|' . $r['w']] = (int) $r['q'];

        $bad = [];
        foreach ($expect as $k => $v) {
            $a = $actual[$k] ?? 0;
            if ($a !== $v) $bad[] = "品號{$k}：異動淨額 {$v}，在庫檔 {$a}";
        }
        foreach ($actual as $k => $v) {
            if (!isset($expect[$k]) && $v !== 0) $bad[] = "品號{$k}：無異動紀錄卻有在庫 {$v}";
        }

        $this->result('在庫量 = 異動淨額（入−出）', empty($bad), $bad,
            count($expect) . ' 組 品號×倉庫比對一致');
    }

    private function checkNegativeStock(): void
    {
        $rows = $this->db->query("SELECT ps_p_id p, ps_w_id w, ps_qty q FROM product_stock WHERE ps_qty < 0")->getResultArray();
        $msgs = array_map(fn($r) => "品號{$r['p']} 倉庫{$r['w']} = {$r['q']}", $rows);
        $this->result('無負庫存', empty($rows), $msgs, '所有在庫量 ≥ 0', true);
    }

    /** 出庫類型不該被記成「入」，反之亦然 */
    private function checkMovementDirection(): void
    {
        $outTypes = ['銷貨出庫', '領料', '盤虧', '其他出庫'];
        $inTypes  = ['進貨', '退貨入庫', '盤盈', '其他入庫', '完工入庫'];
        $bad = [];
        $rows = $this->db->query("SELECT sm_id, sm_type, sm_direction FROM stock_movements")->getResultArray();
        foreach ($rows as $r) {
            if (in_array($r['sm_type'], $outTypes, true) && $r['sm_direction'] !== '出') {
                $bad[] = "#{$r['sm_id']} {$r['sm_type']} 記為「{$r['sm_direction']}」";
            }
            if (in_array($r['sm_type'], $inTypes, true) && $r['sm_direction'] !== '入') {
                $bad[] = "#{$r['sm_id']} {$r['sm_type']} 記為「{$r['sm_direction']}」";
            }
        }
        $this->result('異動方向與異動類型相符', empty($bad), $bad, count($rows) . ' 筆異動方向正確');
    }

    // ===================== 二、單據 → 庫存 =====================

    private function checkPurchaseReceipt(): void
    {
        $rows = $this->db->query("
            SELECT po.po_id, po.po_no FROM purchase_orders po
            WHERE po.po_status = '已結案'
              AND NOT EXISTS (SELECT 1 FROM stock_movements m
                              WHERE m.sm_ref_type = '採購單'
                                AND (m.sm_ref_id = po.po_id OR m.sm_ref_no = po.po_no))
        ")->getResultArray();
        $msgs = array_map(fn($r) => "採購單 {$r['po_no']} 已結案但查無進貨異動", $rows);
        $total = (int) $this->db->query("SELECT COUNT(*) c FROM purchase_orders WHERE po_status='已結案'")->getRow()->c;
        $this->result('已結案採購單皆已進貨入庫', empty($rows), $msgs, "{$total} 張已結案採購單皆有對應入庫");
    }

    private function checkShipmentStock(): void
    {
        $total = (int) $this->db->query("SELECT COUNT(*) c FROM shipments")->getRow()->c;
        if ($total === 0) {
            $this->info('出貨 → 銷貨出庫', '目前無出貨單，無法驗證（ShipmentModel::postSalesStock 尚未有實際樣本）');
            return;
        }
        $rows = $this->db->query("
            SELECT s.s_id, s.s_number FROM shipments s
            WHERE NOT EXISTS (SELECT 1 FROM stock_movements m
                              WHERE m.sm_ref_type = '出貨單'
                                AND (m.sm_ref_id = s.s_id OR m.sm_ref_no = s.s_number))
        ")->getResultArray();
        $msgs = array_map(fn($r) => "出貨單 {$r['s_number']} 查無銷貨出庫異動", $rows);
        $this->result('出貨單皆已扣庫存', empty($rows), $msgs, "{$total} 張出貨單皆有銷貨出庫");
    }

    private function checkWorkOrderStock(): void
    {
        $total = (int) $this->db->query("SELECT COUNT(*) c FROM work_orders WHERE wo_status='已完工'")->getRow()->c;
        if ($total === 0) { $this->info('製令完工 → 領料/入庫', '目前無已完工製令'); return; }
        $rows = $this->db->query("
            SELECT w.wo_id, w.wo_no FROM work_orders w
            WHERE w.wo_status = '已完工'
              AND NOT EXISTS (SELECT 1 FROM stock_movements m
                              WHERE m.sm_ref_type = '製令'
                                AND (m.sm_ref_id = w.wo_id OR m.sm_ref_no = w.wo_no)
                                AND m.sm_type = '完工入庫')
        ")->getResultArray();
        $msgs = array_map(fn($r) => "製令 {$r['wo_no']} 已完工但查無完工入庫", $rows);
        $this->result('已完工製令皆有成品入庫', empty($rows), $msgs, "{$total} 張已完工製令皆有入庫");
    }

    /** 訂單明細的已出貨量必須等於出貨單明細的加總 */
    private function checkShippedQty(): void
    {
        $rows = $this->db->query("
            SELECT oi.oi_id, oi.oi_shipped_quantity sq,
                   COALESCE((SELECT SUM(si.si_quantity) FROM shipment_items si WHERE si.si_oi_id = oi.oi_id), 0) actual
            FROM order_items oi
        ")->getResultArray();
        if (!$rows) { $this->info('訂單已出貨量 = 出貨明細加總', '目前無訂單明細'); return; }
        $bad = [];
        foreach ($rows as $r) {
            if ((int) $r['sq'] !== (int) $r['actual']) {
                $bad[] = "訂單明細#{$r['oi_id']}：欄位記 {$r['sq']}，出貨明細合計 {$r['actual']}";
            }
        }
        $this->result('訂單已出貨量 = 出貨明細加總', empty($bad), $bad, count($rows) . ' 筆訂單明細一致');
    }

    // ===================== 三、單號唯一性 =====================

    /**
     * 多人同時開單時單號不可重複。三道防線都要在：
     * 1) 資料庫 UNIQUE 索引  2) 實際無重複值  3) 計數器不低於既有最大號（否則新號會撞舊號）
     */
    private function checkDocNoUnique(): void
    {
        $targets = [
            ['purchase_orders', 'po_no', 'PO'], ['journal_vouchers', 'jv_no', 'JV'],
            ['payables', 'ap_no', 'AP'], ['receivables', 'ar_no', 'AR'],
            ['settlements', 'st_no', null], ['work_orders', 'wo_no', 'WO'],
            ['purchase_requisitions', 'pr_no', 'PR'], ['invoices', 'inv_number', 'INV'],
            ['orders', 'o_number', 'O'], ['quotes', 'q_number', 'Q'],
            ['shipments', 's_number', 'S'],
        ];

        // 1) UNIQUE 索引
        $missing = [];
        foreach ($targets as [$table, $col]) {
            $c = (int) $this->db->query(
                "SELECT COUNT(*) c FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? AND non_unique = 0",
                [$table, 'uniq_' . $col]
            )->getRow()->c;
            if ($c === 0) $missing[] = "{$table}.{$col} 缺少 UNIQUE 索引";
        }
        $this->result('單號欄位皆有 UNIQUE 索引', empty($missing), $missing,
            count($targets) . ' 個單號欄位皆受資料庫層保護');

        // 2) 實際重複值
        $dups = [];
        foreach ($targets as [$table, $col]) {
            $rows = $this->db->query(
                "SELECT `{$col}` v, COUNT(*) n FROM `{$table}` GROUP BY `{$col}` HAVING n > 1"
            )->getResultArray();
            foreach ($rows as $r) $dups[] = "{$table}：{$r['v']} 出現 {$r['n']} 次";
        }
        $this->result('無重複單號', empty($dups), $dups, '所有單據單號皆唯一');

        // 3) 計數器 vs 既有最大號
        if (!$this->db->tableExists('document_sequences')) {
            $this->info('單號計數器', 'document_sequences 尚未建立');
            return;
        }
        $behind = [];
        foreach ($targets as [$table, $col]) {
            $rows = $this->db->query("SELECT `{$col}` v FROM `{$table}`")->getResultArray();
            foreach ($rows as $r) {
                $no = (string) $r['v'];
                if (preg_match('/^([A-Z]+)(\d{8})-(\d+)$/', $no, $m)) {
                    [$scope, $period, $n] = [$m[1], $m[2], (int) $m[3]];
                } elseif (preg_match('/^([A-Z]+)(\d{8})(\d{3})$/', $no, $m)) {
                    // 舊格式（沒有連字號）：訂單/報價/出貨在改用計數器之前的單號
                    [$scope, $period, $n] = [$m[1], $m[2], (int) $m[3]];
                } elseif (preg_match('/^([A-Z]{2})(\d+)$/', $no, $m)) {
                    [$scope, $period, $n] = ['INV', '', (int) $m[2]];
                } else {
                    continue;
                }
                $cur = $this->db->query(
                    'SELECT ds_last_no FROM document_sequences WHERE ds_scope = ? AND ds_period = ?',
                    [$scope, $period]
                )->getRow();
                if (!$cur || (int) $cur->ds_last_no < $n) {
                    $behind[] = "{$scope}/{$period}：計數器 " . ($cur ? $cur->ds_last_no : '不存在') . "，既有最大 {$n}";
                }
            }
        }
        $behind = array_values(array_unique($behind));
        $this->result('單號計數器不低於既有最大號', empty($behind), $behind, '新單號不會與既有單號相撞');
    }

    // ===================== 四、應收 / 應付 =====================

    private function checkArAp(string $table, string $p, string $label): void
    {
        $amtCol  = $p === 'ap' ? 'ap_amount' : 'ar_amount';
        $paidCol = $p === 'ap' ? 'ap_paid' : 'ar_received';
        $rows = $this->db->query("SELECT {$p}_id id, {$p}_no no, {$amtCol} amt, {$paidCol} paid, {$p}_status st FROM {$table}")->getResultArray();
        if (!$rows) { $this->info("{$label}帳款一致性", "目前無{$label}帳款資料"); return; }

        $bad = [];
        foreach ($rows as $r) {
            $amt = (int) $r['amt']; $paid = (int) $r['paid'];
            if ($paid > $amt) $bad[] = "{$r['no']}：已{$label} {$paid} 超過應{$label} {$amt}";
            $expect = $paid <= 0 ? '未' : ($paid >= $amt ? '已' : '部分');
            $actual = mb_substr($r['st'], 0, 1) === '未' ? '未' : (mb_strpos($r['st'], '部分') !== false ? '部分' : '已');
            if ($expect !== $actual) $bad[] = "{$r['no']}：金額顯示應為「{$expect}」但狀態是「{$r['st']}」";
        }
        $this->result("{$label}帳款金額與狀態一致", empty($bad), $bad, count($rows) . " 筆{$label}帳款一致");
    }

    /** 收付款單合計必須等於應收/應付上already記錄的已收付金額 */
    private function checkSettlementTie(): void
    {
        $stPay = (int) $this->db->query("SELECT COALESCE(SUM(st_amount),0) s FROM settlements WHERE st_target='應付'")->getRow()->s;
        $stRec = (int) $this->db->query("SELECT COALESCE(SUM(st_amount),0) s FROM settlements WHERE st_target='應收'")->getRow()->s;
        $apPaid = (int) $this->db->query("SELECT COALESCE(SUM(ap_paid),0) s FROM payables")->getRow()->s;
        $arRecv = (int) $this->db->query("SELECT COALESCE(SUM(ar_received),0) s FROM receivables")->getRow()->s;

        $bad = [];
        if ($stPay !== $apPaid) $bad[] = "付款單合計 " . number_format($stPay) . " ≠ 應付已付合計 " . number_format($apPaid);
        if ($stRec !== $arRecv) $bad[] = "收款單合計 " . number_format($stRec) . " ≠ 應收已收合計 " . number_format($arRecv);
        $this->result('收付款單 = 應收付已收付金額', empty($bad), $bad,
            '付款 ' . number_format($stPay) . '／收款 ' . number_format($stRec) . ' 皆吻合');
    }

    // ===================== 四、會計報表 =====================

    /** 四階損益各階金額必須等於該階層科目的交易加總 */
    private function checkPnlTie(): void
    {
        $rows = $this->db->query("
            SELECT a.ac_tier tier, SUM(t.t_amount) amt
            FROM gl_transactions t JOIN accounts a ON a.ac_id = t.t_ac_id
            WHERE a.ac_is_pl = 1 GROUP BY a.ac_tier
        ")->getResultArray();
        $tier = [];
        foreach ($rows as $r) $tier[$r['tier']] = (int) $r['amt'];

        $model = new \App\Models\TransactionModel();
        $range = $model->periodRange();
        $rep = $model->pnl($range['min'], $range['max']);

        $bad = [];
        foreach (['營業收入', '一階成本', '二階費用', '三階費用', '四階費用'] as $t) {
            $expect = $tier[$t] ?? 0;
            $got = (int) ($rep['matrix'][$t]['total'] ?? 0);
            if ($expect !== $got) $bad[] = "{$t}：科目加總 " . number_format($expect) . " ≠ 報表 " . number_format($got);
        }
        $gp4 = (int) $rep['gp4']['total'];
        $calc = ($tier['營業收入'] ?? 0) - ($tier['一階成本'] ?? 0) - ($tier['二階費用'] ?? 0)
              - ($tier['三階費用'] ?? 0) - ($tier['四階費用'] ?? 0);
        if ($gp4 !== $calc) $bad[] = '四階毛利 ' . number_format($gp4) . ' ≠ 逐階相減 ' . number_format($calc);

        $this->result('四階損益 = 交易明細加總', empty($bad), $bad,
            "期間 {$range['min']}~{$range['max']}：收入 " . number_format($tier['營業收入'] ?? 0)
            . '／四階毛利 ' . number_format($gp4));
    }

    private function checkCashflowTie(): void
    {
        $model = new \App\Models\TransactionModel();
        $years = $model->availableYears();
        sort($years);
        $bad = [];
        $prevClose = 0;
        foreach ($years as $y) {
            $opening = $model->cashOpeningBefore($y);
            if ($opening !== $prevClose) {
                $bad[] = "{$y} 年期初 " . number_format($opening) . " ≠ 前一年期末 " . number_format($prevClose);
            }
            $rows = $model->cashflow($y, $opening);
            $prevClose = (int) end($rows)['close'];
        }
        $grand = (int) $this->db->query("
            SELECT COALESCE(SUM(CASE WHEN t_direction='收' THEN t_amount+t_tax ELSE -(t_amount+t_tax) END),0) s
            FROM gl_transactions WHERE t_settle_status='已收付'
        ")->getRow()->s;
        if ($prevClose !== $grand) {
            $bad[] = '最終期末 ' . number_format($prevClose) . ' ≠ 已收付交易淨額 ' . number_format($grand);
        }
        $this->result('資金餘額表逐年承接且期末吻合', empty($bad), $bad,
            '累計期末結餘 ' . number_format($grand) . '（' . implode('→', $years) . '）');
    }

    private function checkLedgerTie(): void
    {
        $model = new \App\Models\TransactionModel();
        $rows = $model->ledger();
        $ledgerIn = $ledgerOut = 0;
        foreach ($rows as $r) { $ledgerIn += (int) $r['debit_in']; $ledgerOut += (int) $r['credit_out']; }

        $raw = $this->db->query("
            SELECT COALESCE(SUM(CASE WHEN t_direction='收' THEN t_amount+t_tax ELSE 0 END),0) i,
                   COALESCE(SUM(CASE WHEN t_direction='付' THEN t_amount+t_tax ELSE 0 END),0) o
            FROM gl_transactions
        ")->getRow();
        $bad = [];
        if ($ledgerIn !== (int) $raw->i) $bad[] = '總帳收入 ' . number_format($ledgerIn) . ' ≠ 明細 ' . number_format((int) $raw->i);
        if ($ledgerOut !== (int) $raw->o) $bad[] = '總帳支出 ' . number_format($ledgerOut) . ' ≠ 明細 ' . number_format((int) $raw->o);
        $this->result('會計總帳 = 交易明細', empty($bad), $bad,
            '收 ' . number_format($ledgerIn) . '／付 ' . number_format($ledgerOut));
    }

    private function checkJournalBalance(): void
    {
        $rows = $this->db->query("
            SELECT v.jv_no, COALESCE(SUM(e.je_debit),0) d, COALESCE(SUM(e.je_credit),0) c
            FROM journal_vouchers v LEFT JOIN journal_entries e ON e.je_jv_id = v.jv_id
            GROUP BY v.jv_id, v.jv_no
        ")->getResultArray();
        if (!$rows) { $this->info('傳票借貸平衡', '目前無分錄傳票'); return; }
        $bad = [];
        foreach ($rows as $r) {
            if ((int) $r['d'] !== (int) $r['c']) {
                $bad[] = "{$r['jv_no']}：借 " . number_format((int) $r['d']) . " ≠ 貸 " . number_format((int) $r['c']);
            }
        }
        $this->result('每張傳票 Σ借 = Σ貸', empty($bad), $bad, count($rows) . ' 張傳票全部平衡');
    }

    private function checkBalanceSheet(): void
    {
        $q = fn($cat) => (int) $this->db->query("
            SELECT COALESCE(SUM(e.je_debit - e.je_credit),0) s
            FROM journal_entries e JOIN accounts a ON a.ac_id = e.je_ac_id
            WHERE a.ac_category = ?", [$cat])->getRow()->s;

        $asset = $q('資產');
        $liab  = -$q('負債');
        $eq    = -$q('權益');
        $rev   = -$q('收入');
        $exp   = $q('支出');
        $net   = $rev - $exp;

        $diff = $asset - ($liab + $eq + $net);
        $this->result('資產 = 負債 + 權益 + 本期損益', $diff === 0,
            $diff === 0 ? [] : ['差額 ' . number_format($diff)],
            '資產 ' . number_format($asset) . ' = 負債 ' . number_format($liab)
            . ' + 權益 ' . number_format($eq) . ' + 本期損益 ' . number_format($net));
    }

    private function checkOpenItem(): void
    {
        $rows = $this->db->query("
            SELECT e.je_id, e.je_debit d, e.je_credit c, e.je_offset o
            FROM journal_entries e JOIN accounts a ON a.ac_id = e.je_ac_id
            WHERE a.ac_open_item = 1
        ")->getResultArray();
        if (!$rows) { $this->info('立沖帳沖銷額', '目前無立沖帳科目分錄'); return; }
        $bad = [];
        foreach ($rows as $r) {
            $base = (int) $r['d'] + (int) $r['c'];
            if ((int) $r['o'] > $base) $bad[] = "分錄#{$r['je_id']}：已沖 {$r['o']} 超過本金 {$base}";
            if ((int) $r['o'] < 0) $bad[] = "分錄#{$r['je_id']}：已沖金額為負";
        }
        $totD = $totC = 0;
        foreach ($rows as $r) { $totD += (int) $r['d']; $totC += (int) $r['c']; }
        $this->result('立沖帳已沖金額未超額', empty($bad), $bad,
            count($rows) . ' 筆開放項目（借 ' . number_format($totD) . '／貸 ' . number_format($totC) . '）');
    }

    /**
     * 會計三帳簿勾稽：
     *  日記帳 Σ借 = Σ貸；總分類帳 期末借餘合計 = 貸餘合計；
     *  明細分類帳每個科目的期末餘額必須等於總分類帳同科目的期末餘額。
     */
    private function checkBooks(): void
    {
        $books = new \App\Libraries\AccountingBooks();
        $range = $books->dateRange();
        [$from, $to] = [$range['min'], $range['max']];

        // 日記帳借貸平衡
        $j = $books->journal($from, $to);
        if (!$j) { $this->info('會計帳簿', '目前無傳票分錄，無法驗證'); return; }
        $jd = $jc = 0;
        foreach ($j as $r) { $jd += (int) $r['je_debit']; $jc += (int) $r['je_credit']; }
        $this->result('日記帳 Σ借 = Σ貸', $jd === $jc,
            $jd === $jc ? [] : ['借 ' . number_format($jd) . ' ≠ 貸 ' . number_format($jc)],
            count($j) . ' 筆分錄，借貸各 ' . number_format($jd));

        // 總分類帳期末借貸平衡
        $led = $books->ledger($from, $to);
        $bd = $bc = 0;
        foreach ($led as $r) { $r['closing'] > 0 ? $bd += $r['closing'] : $bc += -$r['closing']; }
        $this->result('總分類帳期末借餘 = 貸餘', $bd === $bc,
            $bd === $bc ? [] : ['借餘 ' . number_format($bd) . ' ≠ 貸餘 ' . number_format($bc)],
            count($led) . ' 個科目，借貸餘各 ' . number_format($bd));

        // 明細分類帳 vs 總分類帳
        $byAcc = [];
        foreach ($led as $r) $byAcc[$r['ac_id']] = $r['closing'];
        $bad = [];
        foreach ($books->detail($from, $to) as $g) {
            $id = (int) $g['account']['ac_id'];
            if (!isset($byAcc[$id])) continue;
            if ($byAcc[$id] !== $g['closing']) {
                $bad[] = $g['account']['ac_code'] . ' ' . $g['account']['ac_name']
                       . '：總帳 ' . number_format($byAcc[$id]) . ' ≠ 明細 ' . number_format($g['closing']);
            }
        }
        $this->result('明細分類帳期末餘額 = 總分類帳', empty($bad), $bad,
            count($byAcc) . ' 個科目逐一核對一致');
    }

    // ===================== 輸出 =====================

    private function section(string $t): void
    {
        CLI::newLine();
        CLI::write($t, 'light_blue');
        CLI::write(str_repeat('-', 62), 'dark_gray');
    }

    private function result(string $name, bool $ok, array $details, string $okMsg, bool $warnOnly = false): void
    {
        if ($ok) {
            $this->pass++;
            CLI::write('  [PASS] ' . $name, 'green');
            if ($okMsg) CLI::write('         ' . $okMsg, 'dark_gray');
            return;
        }
        if ($warnOnly) { $this->warn++; CLI::write('  [WARN] ' . $name, 'yellow'); }
        else { $this->fail++; CLI::write('  [FAIL] ' . $name, 'red'); }
        foreach (array_slice($details, 0, 10) as $d) CLI::write('         · ' . $d, 'dark_gray');
        if (count($details) > 10) CLI::write('         · …另有 ' . (count($details) - 10) . ' 項', 'dark_gray');
    }

    private function info(string $name, string $msg): void
    {
        $this->warn++;
        CLI::write('  [SKIP] ' . $name, 'yellow');
        CLI::write('         ' . $msg, 'dark_gray');
    }
}
