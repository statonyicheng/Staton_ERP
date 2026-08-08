<?php

namespace App\Commands;

use App\Models\CustomerModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\QuoteModel;
use App\Models\ShipmentModel;
use App\Models\WarehouseModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 銷售 → 庫存端到端測試。
 *
 * 這條線是整套系統裡問題最多的一段：原始的報價系統賣東西不會扣庫存，
 * 而且訂單/報價的明細根本存不進去（判斷空白列時用了資料表上不存在的欄位，
 * 導致每一筆明細都被跳過），出貨扣庫存的邏輯因此從未被驗證過。
 *
 * 本測試會實際建立 客戶 → 商品 → 報價單 → 訂單 → 出貨單，驗證：
 *   1. 訂單明細真的存得進去，空白列會被略過（不是每一列都被略過）
 *   2. 出貨後在庫量正確減少，並留下「銷貨出庫」異動（含來源單號與單據 id）
 *   3. 編輯訂單不會弄丟已出貨數量，也擋得住會造成帳實不符的修改
 *   4. 報價單轉訂單時明細正確帶過去
 *   5. 刪除出貨單後庫存正確回沖，最後在庫量回到原始值
 *
 * 所有測試資料在結束時清除（含稽核紀錄），可重複執行。
 *
 *   php spark erp:sales-selftest
 */
class SalesSelfTest extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:sales-selftest';
    protected $description = '端到端驗證：訂單明細、出貨扣庫存、編輯防呆、報價轉單';

    /** 測試資料一律用這組代碼辨識，不會誤刪真實資料 */
    private const CODES = ['ZZE2E', 'ZZE2EB'];

    private $db;
    private int $pass = 0;
    private int $fail = 0;

    private function check(string $name, bool $ok, string $detail = ''): void
    {
        if ($ok) { $this->pass++; CLI::write("  [PASS] {$name}", 'green'); }
        else { $this->fail++; CLI::write("  [FAIL] {$name}　{$detail}", 'red'); }
    }

    /** 取某商品在某倉的在庫量 */
    private function qty(int $pId, int $wId): int
    {
        $r = $this->db->table('product_stock')->select('ps_qty')
            ->where('ps_p_id', $pId)->where('ps_w_id', $wId)->get()->getRowArray();
        return (int) ($r['ps_qty'] ?? 0);
    }

    /** 取訂單的明細（依 oi_id 排序） */
    private function itemsOf(int $oId): array
    {
        return $this->db->table('order_items')->where('oi_o_id', $oId)
            ->orderBy('oi_id', 'ASC')->get()->getResultArray();
    }

    public function run(array $params)
    {
        $this->db = \Config\Database::connect();

        $customerModel  = new CustomerModel();
        $productModel   = new ProductModel();
        $orderModel     = new OrderModel();
        $quoteModel     = new QuoteModel();
        $shipmentModel  = new ShipmentModel();
        $warehouseModel = new WarehouseModel();

        $ids = ['c' => null, 'p' => null, 'pB' => null, 'o' => null, 's' => null, 'w' => null];

        try {
            // ---------- 準備 ----------
            CLI::write('準備測試資料', 'light_blue');
            $this->purgeLeftovers();   // 上一輪若中途失敗會留下資料，先清乾淨才能重跑

            $w = $warehouseModel->where('w_is_active', 1)->orderBy('w_id', 'ASC')->first();
            if (!$w) {
                $warehouseModel->insert(['w_code' => 'ZZE2E', 'w_name' => '__E2E測試倉__', 'w_is_active' => 1]);
                $ids['w'] = (int) $warehouseModel->getInsertID();
                $w = $warehouseModel->find($ids['w']);
            }
            $wId = (int) $w['w_id'];
            CLI::write("  出貨倉：{$w['w_name']}（w_id={$wId}）", 'dark_gray');

            $customerModel->insert(['c_code' => 'ZZE2E', 'c_name' => '__E2E測試客戶__']);
            $cId = (int) $customerModel->getInsertID();
            $ids['c'] = $cId;

            $productModel->insert(['p_code' => 'ZZE2E', 'p_name' => '__E2E測試商品__', 'p_standard_price' => 1000, 'p_cost_price' => 600]);
            $pId = (int) $productModel->getInsertID();
            $ids['p'] = $pId;

            $productModel->insert(['p_code' => 'ZZE2EB', 'p_name' => '__E2E測試商品B__', 'p_standard_price' => 500, 'p_cost_price' => 300]);
            $pIdB = (int) $productModel->getInsertID();
            $ids['pB'] = $pIdB;

            // 建立期初庫存 100（走正式的異動流程，不直接寫 product_stock）
            (new \App\Models\StockMovementModel())->apply([
                'sm_date' => date('Y-m-d'), 'sm_type' => '期初存量', 'sm_direction' => '入',
                'sm_p_id' => $pId, 'sm_w_id' => $wId, 'sm_qty' => 100,
                'sm_note' => 'E2E 測試期初',
            ]);
            $qty0 = $this->qty($pId, $wId);
            $this->check('期初庫存建立為 100', $qty0 === 100, "實得 {$qty0}");

            $no1 = $orderModel->generateOrderNumber();
            $no2 = $orderModel->generateOrderNumber();
            $this->check('連續取號不重複（多人同時開單的防線）', $no1 !== $no2, "{$no1} vs {$no2}");

            // ---------- 建立訂單 ----------
            CLI::newLine();
            CLI::write('建立訂單（30 件，另含一列空白列）', 'light_blue');

            $res = $orderModel->saveOrderWithItems(
                [
                    'o_number' => $orderModel->generateOrderNumber(),
                    'o_date' => date('Y-m-d'), 'o_c_id' => $cId,
                    'o_delivery_date' => date('Y-m-d'), 'o_total_amount' => 30000,
                    'o_status' => 'processing',
                ],
                [
                    // 表單預設會多帶一列空的，必須被略過
                    ['oi_p_id' => '', 'oi_quantity' => 1, 'oi_unit_price' => 0, 'oi_discount' => 0, 'oi_amount' => 0],
                    ['oi_p_id' => $pId, 'oi_quantity' => 30, 'oi_unit_price' => 1000,
                     'oi_discount' => 0, 'oi_amount' => 30000, 'oi_color' => '流沙白', 'oi_size' => '600x600'],
                ]
            );
            $oId = (int) ($res['orderId'] ?? 0);
            $ids['o'] = $oId;
            $items = $oId ? $this->itemsOf($oId) : [];
            $this->check('訂單與明細建立成功', $oId > 0 && count($items) === 1,
                'o_id=' . $oId . '　明細 ' . count($items) . ' 筆　' . ($res['message'] ?? ''));
            if (!$oId || !$items) {
                throw new \RuntimeException('訂單建立失敗，後續測試中止：' . ($res['message'] ?? '未知原因'));
            }
            $oi = $items[0];
            $this->check('明細內容正確（商品/數量/規格）',
                (int) $oi['oi_p_id'] === $pId && (int) $oi['oi_quantity'] === 30 && $oi['oi_color'] === '流沙白',
                "p={$oi['oi_p_id']} qty={$oi['oi_quantity']} color={$oi['oi_color']}");
            $this->check('建立訂單不影響庫存', $this->qty($pId, $wId) === 100, '訂單只是承諾，尚未出貨');

            // ---------- 出貨 20 件 ----------
            CLI::newLine();
            CLI::write('出貨 20 件', 'light_blue');

            $shipRes = $shipmentModel->saveShipmentWithItems(
                [
                    's_o_id' => $oId, 's_number' => $shipmentModel->generateShipmentNumber(),
                    's_date' => date('Y-m-d'), 's_status' => 1, 's_after_sales_status' => 1,
                ],
                [['si_oi_id' => (int) $oi['oi_id'], 'si_quantity' => 20]]
            );
            $sId = (int) ($shipRes['shipmentId'] ?? 0);
            $ids['s'] = $sId;
            $this->check('出貨單建立成功', $sId > 0, 's_id=' . $sId . '　' . ($shipRes['message'] ?? ''));
            if (!$sId) {
                throw new \RuntimeException('出貨單建立失敗：' . ($shipRes['message'] ?? '未知原因'));
            }

            $qtyAfter = $this->qty($pId, $wId);
            $this->check('出貨後在庫量 100 → 80', $qtyAfter === 80, "實得 {$qtyAfter}");

            $mv = $this->db->table('stock_movements')
                ->where('sm_p_id', $pId)->where('sm_type', '銷貨出庫')
                ->orderBy('sm_id', 'DESC')->get()->getRowArray();
            $this->check('產生「銷貨出庫」異動', $mv !== null);
            if ($mv) {
                $this->check('異動數量正確（20）', (int) $mv['sm_qty'] === 20, "實得 {$mv['sm_qty']}");
                $this->check('異動方向為「出」', $mv['sm_direction'] === '出', "實得 {$mv['sm_direction']}");
                $this->check('來源單別為「出貨單」', $mv['sm_ref_type'] === '出貨單', "實得 {$mv['sm_ref_type']}");
                $this->check('有回填來源單據 id',
                    (int) $mv['sm_ref_id'] === $sId, "sm_ref_id={$mv['sm_ref_id']} vs s_id={$sId}");
            }

            $oiAfter = $this->db->table('order_items')->where('oi_id', $oi['oi_id'])->get()->getRowArray();
            $this->check('訂單明細已出貨量累加為 20',
                (int) $oiAfter['oi_shipped_quantity'] === 20, "實得 {$oiAfter['oi_shipped_quantity']}");

            $ord = $this->db->table('orders')->where('o_id', $oId)->get()->getRowArray();
            $this->check('訂單出貨狀態更新為「部分出貨」',
                $ord['o_shipment_status'] === 'partial', "實得 {$ord['o_shipment_status']}");

            // ---------- 編輯訂單（不可弄丟已出貨的紀錄） ----------
            CLI::newLine();
            CLI::write('編輯已出貨的訂單', 'light_blue');

            $head = [
                'o_id' => $oId, 'o_number' => $ord['o_number'], 'o_date' => $ord['o_date'],
                'o_c_id' => $cId, 'o_total_amount' => 30000, 'o_status' => 'processing',
            ];
            $line = static fn (array $over = []) => array_merge([
                'oi_id' => $oi['oi_id'], 'oi_p_id' => $pId, 'oi_quantity' => 30,
                'oi_unit_price' => 1000, 'oi_discount' => 0, 'oi_amount' => 30000,
            ], $over);

            $r = $orderModel->saveOrderWithItems($head, [$line(['oi_quantity' => 10])]);
            $this->check('擋下「訂購量小於已出貨量」', $r['success'] === false, $r['message']);

            $r = $orderModel->saveOrderWithItems($head, [$line(['oi_p_id' => $pIdB])]);
            $this->check('擋下「已出貨品項換商品」', $r['success'] === false, $r['message']);

            $r = $orderModel->saveOrderWithItems($head, [
                ['oi_p_id' => $pIdB, 'oi_quantity' => 5, 'oi_unit_price' => 500, 'oi_discount' => 0, 'oi_amount' => 2500],
            ]);
            $this->check('擋下「刪除已出貨的品項」', $r['success'] === false, $r['message']);

            $r = $orderModel->saveOrderWithItems($head, [
                $line(['oi_quantity' => 40, 'oi_amount' => 40000]),
                ['oi_p_id' => $pIdB, 'oi_quantity' => 5, 'oi_unit_price' => 500, 'oi_discount' => 0, 'oi_amount' => 2500],
            ]);
            $this->check('合法編輯（改量 30→40 並加一列）成功', $r['success'] === true, $r['message']);

            $edited = $this->itemsOf($oId);
            $this->check('編輯後明細為 2 筆', count($edited) === 2, '實得 ' . count($edited) . ' 筆');
            $this->check('原品項 oi_id 未變（出貨明細不會失聯）',
                (int) ($edited[0]['oi_id'] ?? 0) === (int) $oi['oi_id'],
                "oi_id {$oi['oi_id']} → " . ($edited[0]['oi_id'] ?? 'null'));
            $this->check('原品項已出貨量仍為 20（沒有被重建歸零）',
                (int) ($edited[0]['oi_shipped_quantity'] ?? 0) === 20,
                '實得 ' . ($edited[0]['oi_shipped_quantity'] ?? 'null'));
            $this->check('原品項數量已更新為 40',
                (int) ($edited[0]['oi_quantity'] ?? 0) === 40, '實得 ' . ($edited[0]['oi_quantity'] ?? 'null'));
            $this->check('編輯訂單不會動到庫存', $this->qty($pId, $wId) === 80, '出貨才會動庫存');

            // 把新加的那列刪掉，回到單一品項，後面的回沖檢查才單純
            $r = $orderModel->saveOrderWithItems($head, [$line(['oi_quantity' => 40, 'oi_amount' => 40000])]);
            $this->check('刪除未出貨的品項成功', $r['success'] === true && count($this->itemsOf($oId)) === 1, $r['message']);

            // ---------- 報價單 → 訂單 ----------
            CLI::newLine();
            CLI::write('報價單轉訂單', 'light_blue');

            $qRes = $quoteModel->saveQuoteWithItems(
                [
                    'q_number' => $quoteModel->generateQuoteNumber(), 'q_date' => date('Y-m-d'),
                    'q_c_id' => $cId, 'q_subtotal' => 2500, 'q_tax_rate' => 5,
                    'q_tax_amount' => 125, 'q_total_amount' => 2625,
                ],
                [
                    ['qi_p_id' => '', 'qi_quantity' => 1],   // 空白列，應被略過
                    ['qi_p_id' => $pIdB, 'qi_quantity' => 5, 'qi_unit_price' => 500,
                     'qi_discount' => 0, 'qi_amount' => 2500, 'qi_size' => '300x300'],
                ]
            );
            $qId = (int) ($qRes['quoteId'] ?? 0);
            $qItems = $qId ? $this->db->table('quote_items')->where('qi_q_id', $qId)->get()->getResultArray() : [];
            $this->check('報價單與明細建立成功', $qId > 0 && count($qItems) === 1,
                'q_id=' . $qId . '　明細 ' . count($qItems) . ' 筆　' . ($qRes['message'] ?? ''));

            if ($qId) {
                $newOrderId = (int) $orderModel->createFromQuote($qId);
                $this->check('轉單成功', $newOrderId > 0);

                if ($newOrderId) {
                    $conv = $this->itemsOf($newOrderId);
                    $this->check('轉單後訂單明細為 1 筆', count($conv) === 1, '實得 ' . count($conv) . ' 筆');
                    $this->check('轉單明細對應同一個商品與數量',
                        (int) ($conv[0]['oi_p_id'] ?? 0) === $pIdB && (int) ($conv[0]['oi_quantity'] ?? 0) === 5,
                        'p=' . ($conv[0]['oi_p_id'] ?? 'null') . ' qty=' . ($conv[0]['oi_quantity'] ?? 'null'));

                    $q = $this->db->table('quotes')->where('q_id', $qId)->get()->getRowArray();
                    $this->check('報價單已標記轉單，不會重複轉',
                        (int) ($q['q_o_id'] ?? 0) === $newOrderId, '實得 ' . ($q['q_o_id'] ?? 'null'));
                    $this->check('重複轉單會被擋下', $orderModel->createFromQuote($qId) === false);
                }
            }

            // ---------- 刪除出貨單，庫存應回沖 ----------
            CLI::newLine();
            CLI::write('刪除出貨單（庫存應回沖）', 'light_blue');

            $shipmentModel->deleteShipment($sId);
            $ids['s'] = null;

            $qtyBack = $this->qty($pId, $wId);
            $this->check('刪除後在庫量 80 → 100', $qtyBack === 100, "實得 {$qtyBack}");

            $rev = $this->db->table('stock_movements')
                ->where('sm_p_id', $pId)->where('sm_type', '退貨入庫')
                ->orderBy('sm_id', 'DESC')->get()->getRowArray();
            $this->check('產生「退貨入庫」回沖異動', $rev !== null);
            if ($rev) {
                $this->check('回沖數量正確（20）', (int) $rev['sm_qty'] === 20, "實得 {$rev['sm_qty']}");
            }

            $oiBack = $this->db->table('order_items')->where('oi_id', $oi['oi_id'])->get()->getRowArray();
            $this->check('訂單明細已出貨量歸零',
                (int) $oiBack['oi_shipped_quantity'] === 0, "實得 {$oiBack['oi_shipped_quantity']}");

            // ---------- 帳實相符 ----------
            CLI::newLine();
            CLI::write('帳實相符檢查', 'light_blue');
            $net = (int) $this->db->query("
                SELECT COALESCE(SUM(CASE WHEN sm_direction='出' THEN -sm_qty ELSE sm_qty END),0) n
                FROM stock_movements WHERE sm_p_id = ? AND sm_w_id = ?", [$pId, $wId])->getRow()->n;
            $this->check('異動淨額 = 在庫量', $net === $this->qty($pId, $wId), "淨額 {$net} vs 在庫 " . $this->qty($pId, $wId));

        } catch (\Throwable $e) {
            $this->fail++;
            CLI::write('  [ERROR] ' . get_class($e) . '：' . $e->getMessage(), 'red');
            CLI::write('          ' . $e->getFile() . ':' . $e->getLine(), 'dark_gray');
        } finally {
            $this->cleanup();
        }

        CLI::newLine();
        CLI::write(str_repeat('=', 58), 'dark_gray');
        CLI::write("銷售→庫存端到端測試：通過 {$this->pass}　失敗 {$this->fail}",
            $this->fail ? 'red' : 'green');
    }

    /**
     * 清掉測試資料（跑之前與跑完各一次），一律以固定代碼辨識，不會誤刪真實資料。
     */
    private function purgeLeftovers(bool $quiet = false): void
    {
        $cIds = array_column($this->db->table('customers')->select('c_id')
            ->whereIn('c_code', self::CODES)->get()->getResultArray(), 'c_id');
        $pIds = array_column($this->db->table('products')->select('p_id')
            ->whereIn('p_code', self::CODES)->get()->getResultArray(), 'p_id');

        if ($cIds) {
            $oIds = array_column($this->db->table('orders')->select('o_id')->whereIn('o_c_id', $cIds)->get()->getResultArray(), 'o_id');
            if ($oIds) {
                $sIds = array_column($this->db->table('shipments')->select('s_id')->whereIn('s_o_id', $oIds)->get()->getResultArray(), 's_id');
                if ($sIds) $this->db->table('shipment_items')->whereIn('si_s_id', $sIds)->delete();
                $this->db->table('shipments')->whereIn('s_o_id', $oIds)->delete();
                $this->db->table('order_items')->whereIn('oi_o_id', $oIds)->delete();
            }

            $qIds = array_column($this->db->table('quotes')->select('q_id')->whereIn('q_c_id', $cIds)->get()->getResultArray(), 'q_id');
            if ($qIds) {
                $this->db->table('quote_items')->whereIn('qi_q_id', $qIds)->delete();
                $this->db->table('quotes')->whereIn('q_id', $qIds)->delete();
            }

            if ($oIds) $this->db->table('orders')->whereIn('o_id', $oIds)->delete();
            $this->db->table('customers')->whereIn('c_id', $cIds)->delete();
        }

        if ($pIds) {
            $this->db->table('stock_movements')->whereIn('sm_p_id', $pIds)->delete();
            $this->db->table('product_stock')->whereIn('ps_p_id', $pIds)->delete();
            $this->db->table('products')->whereIn('p_id', $pIds)->delete();
        }
        $this->db->table('warehouses')->whereIn('w_code', self::CODES)->delete();

        if (($cIds || $pIds) && !$quiet) CLI::write('  （已清掉上一輪殘留的測試資料）', 'dark_gray');
    }

    /** 清除所有測試資料（含其稽核紀錄） */
    private function cleanup(): void
    {
        CLI::newLine();
        CLI::write('清除測試資料', 'light_blue');
        try {
            $this->purgeLeftovers(true);

            // 測試期間產生的稽核紀錄
            $this->db->table('audit_logs')
                ->whereIn('al_table', ['orders', 'order_items', 'quotes', 'quote_items',
                                       'shipments', 'shipment_items', 'products', 'customers',
                                       'warehouses', 'stock_movements', 'product_stock'])
                ->like('al_summary', '__E2E', 'after')->delete();

            CLI::write('  已清除', 'dark_gray');
        } catch (\Throwable $e) {
            CLI::write('  清除時發生錯誤：' . $e->getMessage(), 'yellow');
        }
    }
}
