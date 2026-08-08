<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Services\InventoryService;

class InventoryServiceTotalMetersTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new InventoryService();

        $db = \CodeIgniter\Database\Config::connect();

        // 基礎資料
        $db->table('locations')->insert(['l_id' => 1, 'l_name' => '測試地點']);
        $db->table('users')->insert([
            'u_id' => 1,
            'u_name' => '測試用戶',
            'u_account' => 'tester',
            'u_password' => 'pass',
            'u_level' => 1,
        ]);
        $db->table('major_categories')->insert(['mac_id' => 1, 'mac_name' => '主要類別']);
        $db->table('minor_categories')->insert(['mic_id' => 1, 'mic_name' => '次要類別', 'mic_mac_id' => 1]);
        $db->table('products')->insert(['pr_id' => 1, 'pr_name' => '產品X', 'pr_mic_id' => 1]);
        $db->table('manufacturers')->insert(['ma_id' => 1, 'ma_name' => '製造商A']);

        // 庫存主檔（使其出現在列表）
        $db->table('inventories')->insert([
            'i_pr_id' => 1,
            'i_l_id' => 1,
            'i_initial' => 0,
            'i_qty' => 0,
            'i_create_by' => 1,
        ]);

        // 料單：o_to_location=1，兩筆長度 3.2 與 1.8
        $db->table('orders')->insert([
            'o_type' => 0,
            'o_from_location' => 1,
            'o_to_location' => 1,
            'o_date' => date('Y-m-d'),
            'o_car_number' => 'XYZ-001',
            'o_driver_phone' => '0900000000',
            'o_loading_time' => '08:00:00',
            'o_unloading_time' => '09:00:00',
            'o_oxygen' => 0,
            'o_acetylene' => 0,
            'o_remark' => '',
            'o_create_by' => 1,
            'o_update_by' => 1,
            'o_status' => 0,
        ]);
        $orderId = $db->insertID();
        $db->table('order_details')->insertBatch([
            ['od_o_id' => $orderId, 'od_pr_id' => 1, 'od_qty' => 1, 'od_length' => 3.2, 'od_weight' => 0],
            ['od_o_id' => $orderId, 'od_pr_id' => 1, 'od_qty' => 1, 'od_length' => 1.8, 'od_weight' => 0],
        ]);

        // 租賃：ro_type=0, ro_l_id=1，兩筆長度 2.5 與 1.5
        $db->table('rental_orders')->insert([
            'ro_type' => 0,
            'ro_ma_id' => 1,
            'ro_l_id' => 1,
            'ro_date' => date('Y-m-d')
        ]);
        $rentalId = $db->insertID();
        $db->table('rental_order_details')->insertBatch([
            ['rod_ro_id' => $rentalId, 'rod_pr_id' => 1, 'rod_qty' => 1, 'rod_length' => 2.5, 'rod_weight' => 0],
            ['rod_ro_id' => $rentalId, 'rod_pr_id' => 1, 'rod_qty' => 1, 'rod_length' => 1.5, 'rod_weight' => 0],
        ]);
    }

    public function testTotalMetersAggregation()
    {
        $result = $this->inventoryService->getInventoryList([], 1, false);

        $target = null;
        foreach ($result['data'] as $row) {
            if ((int)($row['i_l_id'] ?? 0) === 1 && (int)($row['i_pr_id'] ?? 0) === 1) {
                $target = $row;
                break;
            }
        }

        $this->assertNotNull($target, '找不到測試用的庫存資料行');
        $this->assertArrayHasKey('totalMeters', $target, '回傳資料缺少 totalMeters 欄位');
        $this->assertEquals(9.0, (float)$target['totalMeters'], 'totalMeters 計算不正確', 0.0001);
    }
}


