<?php

namespace App\Commands;

use App\Models\CustomerModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\ShipmentModel;
use App\Models\WarehouseModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 銷售 → 庫存端到端測試。
 *
 * 這條線是整套系統裡唯一「寫好但從未用真實單據驗證過」的部分：
 * 原始的報價系統賣東西不會扣庫存，後來補了 ShipmentModel::postSalesStock()，
 * 但一直沒有樣本訂單可以跑。
 *
 * 本測試會實際建立 客戶 → 商品 → 訂單 → 出貨單，驗證：
 *   1. 出貨後在庫量正確減少，並留下「銷貨出庫」異動（含來源單號與單據 id）
 *   2. 訂單明細的已出貨數量正確累加
 *   3. 刪除出貨單後庫存正確回沖，並留下「退貨入庫」異動
 *   4. 全程結束後在庫量回到原始值
 *
 * 所有測試資料在結束時清除（含稽核紀錄），可重複執行。
 *
 *   php spark erp:sales-selftest
 */
class SalesSelfTest extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:sales-selftest';
    protected $description = '端到端驗證：出貨是否正確扣庫存、刪除出貨是否正確回沖';

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

    public function run(array $params)
    {
        $this->db = \Config\Database::connect();

        $customerModel = new CustomerModel();
        $productModel  = new ProductModel();
        $orderModel    = new OrderModel();
        $shipmentModel = new ShipmentModel();
        $warehouseModel = new WarehouseModel();

        $ids = ['c' => null, 'p' => null, 'o' => null, 's' => null, 'w' => null];

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

            // 建立期初庫存 100（走正式的異動流程，不直接寫 product_stock）
            (new \App\Models\StockMovementModel())->apply([
                'sm_date' => date('Y-m-d'), 'sm_type' => '期初存量', 'sm_direction' => '入',
                'sm_p_id' => $pId, 'sm_w_id' => $wId, 'sm_qty' => 100,
                'sm_note' => 'E2E 測試期初',
            ]);
            $qty0 = $this->qty($pId, $wId);
            $this->check('期初庫存建立為 100', $qty0 === 100, "實得 {$qty0}");

            // ---------- 建立訂單 ----------
            CLI::newLine();
            CLI::write('建立訂單（30 件）', 'light_blue');

            $res = $orderModel->saveOrderWithItems(
                [
                    'o_number' => $orderModel->generateOrderNumber(),
                    'o_date' => date('Y-m-d'), 'o_c_id' => $cId,
                    'o_delivery_date' => date('Y-m-d'), 'o_total_amount' => 30000,
                    'o_status' => 'processing',
                ],
                [[
                    'oi_p_id' => $pId, 'oi_quantity' => 30,
                    'oi_unit_price' => 1000, 'oi_discount' => 0, 'oi_amount' => 30000,
                ]]
            );
            $oId = (int) ($res['orderId'] ?? 0);
            $ids['o'] = $oId;
            $oi = $oId ? $this->db->table('order_items')->where('oi_o_id', $oId)->get()->getRowArray() : null;
            $this->check('訂單與明細建立成功', $oId > 0 && $oi !== null,
                'o_id=' . $oId . '　' . ($res['message'] ?? ''));
            if (!$oId || !$oi) {
                throw new \RuntimeException('訂單建立失敗，後續測試中止：' . ($res['message'] ?? '未知原因'));
            }
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
                $this->check('有回填來源單據 id（今日修正的缺陷）',
                    (int) $mv['sm_ref_id'] === $sId, "sm_ref_id={$mv['sm_ref_id']} vs s_id={$sId}");
            }

            $oiAfter = $this->db->table('order_items')->where('oi_id', $oi['oi_id'])->get()->getRowArray();
            $this->check('訂單明細已出貨量累加為 20',
                (int) $oiAfter['oi_shipped_quantity'] === 20, "實得 {$oiAfter['oi_shipped_quantity']}");

            $ord = $this->db->table('orders')->where('o_id', $oId)->get()->getRowArray();
            $this->check('訂單出貨狀態更新為「部分出貨」',
                $ord['o_shipment_status'] === 'partial', "實得 {$ord['o_shipment_status']}");

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
            $this->cleanup($ids);
        }

        CLI::newLine();
        CLI::write(str_repeat('=', 58), 'dark_gray');
        CLI::write("銷售→庫存端到端測試：通過 {$this->pass}　失敗 {$this->fail}",
            $this->fail ? 'red' : 'green');
    }

    /**
     * 清掉先前中途失敗留下的測試資料，讓這支指令可以重複執行。
     * 一律以 ZZE2E 這組固定代碼辨識，不會誤刪真實資料。
     */
    private function purgeLeftovers(): void
    {
        $cIds = array_column($this->db->table('customers')->select('c_id')->where('c_code', 'ZZE2E')->get()->getResultArray(), 'c_id');
        $pIds = array_column($this->db->table('products')->select('p_id')->where('p_code', 'ZZE2E')->get()->getResultArray(), 'p_id');

        if ($cIds) {
            $oIds = array_column($this->db->table('orders')->select('o_id')->whereIn('o_c_id', $cIds)->get()->getResultArray(), 'o_id');
            if ($oIds) {
                $sIds = array_column($this->db->table('shipments')->select('s_id')->whereIn('s_o_id', $oIds)->get()->getResultArray(), 's_id');
                if ($sIds) $this->db->table('shipment_items')->whereIn('si_s_id', $sIds)->delete();
                $this->db->table('shipments')->whereIn('s_o_id', $oIds)->delete();
                $this->db->table('order_items')->whereIn('oi_o_id', $oIds)->delete();
                $this->db->table('orders')->whereIn('o_id', $oIds)->delete();
            }
            $this->db->table('customers')->whereIn('c_id', $cIds)->delete();
        }
        if ($pIds) {
            $this->db->table('stock_movements')->whereIn('sm_p_id', $pIds)->delete();
            $this->db->table('product_stock')->whereIn('ps_p_id', $pIds)->delete();
            $this->db->table('products')->whereIn('p_id', $pIds)->delete();
        }
        $this->db->table('warehouses')->where('w_code', 'ZZE2E')->delete();

        if ($cIds || $pIds) CLI::write('  （已清掉上一輪殘留的測試資料）', 'dark_gray');
    }

    /** 清除所有測試資料（含其稽核紀錄） */
    private function cleanup(array $ids): void
    {
        CLI::newLine();
        CLI::write('清除測試資料', 'light_blue');
        try {
            if ($ids['s']) {
                $this->db->table('shipment_items')->where('si_s_id', $ids['s'])->delete();
                $this->db->table('shipments')->where('s_id', $ids['s'])->delete();
            }
            if ($ids['o']) {
                $this->db->table('shipments')->where('s_o_id', $ids['o'])->delete();
                $this->db->table('order_items')->where('oi_o_id', $ids['o'])->delete();
                $this->db->table('orders')->where('o_id', $ids['o'])->delete();
            }
            if ($ids['p']) {
                $this->db->table('stock_movements')->where('sm_p_id', $ids['p'])->delete();
                $this->db->table('product_stock')->where('ps_p_id', $ids['p'])->delete();
                $this->db->table('products')->where('p_id', $ids['p'])->delete();
            }
            if ($ids['c']) $this->db->table('customers')->where('c_id', $ids['c'])->delete();
            if ($ids['w']) $this->db->table('warehouses')->where('w_id', $ids['w'])->delete();

            // 測試期間產生的稽核紀錄
            $this->db->table('audit_logs')
                ->whereIn('al_table', ['orders', 'order_items', 'shipments', 'shipment_items',
                                       'products', 'customers', 'warehouses', 'stock_movements', 'product_stock'])
                ->like('al_summary', '__E2E', 'after')->delete();

            CLI::write('  已清除', 'dark_gray');
        } catch (\Throwable $e) {
            CLI::write('  清除時發生錯誤：' . $e->getMessage(), 'yellow');
        }
    }
}
