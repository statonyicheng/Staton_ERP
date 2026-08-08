<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\OrderModel;

class OrderModelGetListTotalTonsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    private OrderModel $orderModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderModel = new OrderModel();

        $db = \CodeIgniter\Database\Config::connect();

        // 基礎資料
        $db->table('locations')->insertBatch([
            ['l_id' => 1, 'l_name' => '地點A'],
            ['l_id' => 2, 'l_name' => '地點B'],
        ]);
        $db->table('users')->insert([
            'u_id' => 1,
            'u_name' => '測試用戶',
            'u_account' => 'tester',
            'u_password' => 'pass',
            'u_level' => 1,
        ]);

        // 產品（供 order_details 外鍵使用）
        $db->table('products')->insertBatch([
            ['pr_id' => 1, 'pr_name' => '產品A'],
            ['pr_id' => 2, 'pr_name' => '產品B'],
        ]);

        // 訂單 A：期望總噸數 1.35 噸（800 + 550 公斤）
        $db->table('orders')->insert([
            'o_type' => 0,
            'o_from_location' => 1,
            'o_to_location' => 2,
            'o_date' => date('Y-m-d'),
            'o_car_number' => 'AAA-001',
            'o_driver_phone' => '0912345678',
            'o_loading_time' => '08:00:00',
            'o_unloading_time' => '09:00:00',
            'o_g_id' => null,
            'o_oxygen' => 0,
            'o_acetylene' => 0,
            'o_remark' => '',
            'o_create_by' => 1,
            'o_update_by' => 1,
            'o_status' => 0,
        ]);
        $orderAId = (int) $db->insertID();
        $db->table('order_details')->insertBatch([
            ['od_o_id' => $orderAId, 'od_pr_id' => 1, 'od_qty' => 1, 'od_length' => 0, 'od_weight' => 800],
            ['od_o_id' => $orderAId, 'od_pr_id' => 1, 'od_qty' => 1, 'od_length' => 0, 'od_weight' => 550],
        ]);

        // 訂單 B：干擾資料（總噸數 2.00 噸）
        $db->table('orders')->insert([
            'o_type' => 1,
            'o_from_location' => 2,
            'o_to_location' => 1,
            'o_date' => date('Y-m-d'),
            'o_car_number' => 'BBB-002',
            'o_driver_phone' => '0999999999',
            'o_loading_time' => '10:00:00',
            'o_unloading_time' => '11:00:00',
            'o_g_id' => null,
            'o_oxygen' => 0,
            'o_acetylene' => 0,
            'o_remark' => '',
            'o_create_by' => 1,
            'o_update_by' => 1,
            'o_status' => 0,
        ]);
        $orderBId = (int) $db->insertID();
        $db->table('order_details')->insertBatch([
            ['od_o_id' => $orderBId, 'od_pr_id' => 2, 'od_qty' => 1, 'od_length' => 0, 'od_weight' => 1000],
            ['od_o_id' => $orderBId, 'od_pr_id' => 2, 'od_qty' => 1, 'od_length' => 0, 'od_weight' => 1000],
        ]);
    }

    public function testGetList_ShouldReturnCorrectTotalTons(): void
    {
        $rows = $this->orderModel->getList();

        // 取得兩筆訂單對應的總噸數
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['o_id']] = $r['o_total_tons'] ?? null;
        }

        $this->assertArrayHasKey(1, $map, '找不到訂單 A');
        $this->assertArrayHasKey(2, $map, '找不到訂單 B');

        // 訂單 A：800 + 550 = 1350 kg => 1.35 噸
        $this->assertSame('1.35', (string) $map[1], '訂單 A 噸數計算錯誤');

        // 訂單 B：1000 + 1000 = 2000 kg => 2.00 噸
        $this->assertSame('2.00', (string) $map[2], '訂單 B 噸數計算錯誤');
    }
}


