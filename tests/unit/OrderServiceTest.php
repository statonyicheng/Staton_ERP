<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Libraries\OrderService;
use App\Models\OrderModel;
use App\Models\OrderDetailModel;
use App\Models\InventoryModel;
use App\Services\InventoryService;
use App\Libraries\FileManager;

class OrderServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $orderService;
    protected $orderModel;
    protected $orderDetailModel;
    protected $inventoryModel;
    protected $inventoryService;
    protected $mockFileManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 創建 Mock FileManager (檔案處理不是這次測試重點)
        $this->mockFileManager = $this->createMock(FileManager::class);
        
        // 設定 FileManager Mock 的預設行為
        $this->mockFileManager->method('uploadFiles')->willReturn([
            'o_driver_signature' => null,
            'o_from_signature' => null,
            'o_to_signature' => null,
            'o_img_car_head' => null,
            'o_img_car_tail' => null
        ]);
        
        // 初始化 Models 和 Services (使用真實的 InventoryService)
        $this->orderService = new OrderService();
        $this->orderModel = new OrderModel();
        $this->orderDetailModel = new OrderDetailModel();
        $this->inventoryModel = new InventoryModel();
        $this->inventoryService = new InventoryService();
        
        // 使用反射來注入服務
        $reflection = new \ReflectionClass($this->orderService);
        $inventoryServiceProperty = $reflection->getProperty('inventoryService');
        $inventoryServiceProperty->setAccessible(true);
        $inventoryServiceProperty->setValue($this->orderService, $this->inventoryService);
        
        $fileManagerProperty = $reflection->getProperty('fileManager');
        $fileManagerProperty->setAccessible(true);
        $fileManagerProperty->setValue($this->orderService, $this->mockFileManager);

        // 插入測試用的產品和次要類別 (OrderDetailModel 依賴這些)
        $db = \CodeIgniter\Database\Config::connect();
        $db->table('locations')->insertBatch([
            ['l_id' => 1, 'l_name' => '地點A'],
            ['l_id' => 2, 'l_name' => '地點B'],
        ]);
        $db->table('users')->insertBatch([
            ['u_id' => 1, 'u_name' => '測試用戶', 'u_account' => 'testuser', 'u_password' => 'testpass', 'u_level' => 1],
        ]);
        $db->table('major_categories')->insertBatch([
            ['mac_id' => 1, 'mac_name' => '主要類別1'],
            ['mac_id' => 2, 'mac_name' => '主要類別2'],
        ]);
        $db->table('minor_categories')->insertBatch([
            ['mic_id' => 1, 'mic_name' => '次要類別1', 'mic_mac_id' => 1],
            ['mic_id' => 2, 'mic_name' => '次要類別2', 'mic_mac_id' => 1],
        ]);
        $db->table('products')->insertBatch([
            ['pr_id' => 1, 'pr_name' => '產品A', 'pr_mic_id' => 1],
            ['pr_id' => 2, 'pr_name' => '產品B', 'pr_mic_id' => 1],
            ['pr_id' => 3, 'pr_name' => '產品C', 'pr_mic_id' => 2],
            ['pr_id' => 4, 'pr_name' => '產品D', 'pr_mic_id' => 2], // 添加一個新產品用於新增情境
        ]);
        
        // 準備測試用的訂單資料
        $this->orderModel->insert([
            'o_type' => 0,
            'o_from_location' => 1,
            'o_to_location' => 2,
            'o_date' => date('Y-m-d'),
            'o_car_number' => 'ABC-123',
            'o_driver_phone' => '0912345678',
            'o_loading_time' => '08:00:00',
            'o_unloading_time' => '17:00:00',
            'o_g_id' => null,
            'o_oxygen' => 10,
            'o_acetylene' => 5,
            'o_remark' => '測試訂單',
            'o_driver_signature' => null,
            'o_from_signature' => null,
            'o_to_signature' => null,
            'o_img_car_head' => null,
            'o_img_car_tail' => null,
            'o_number' => 'TEST-001',
            'o_create_at' => date('Y-m-d H:i:s'),
            'o_update_at' => date('Y-m-d H:i:s'),
            'o_create_by' => 1,
            'o_update_by' => 1,
            'o_status' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * 設定測試用的初始庫存
     */
    private function setupInitialInventory(): void
    {
        $db = \CodeIgniter\Database\Config::connect();
        
        // 為每個產品在每個地點建立庫存記錄
        $inventoryData = [
            // 地點1 (出庫地點) - 有足夠庫存
            ['i_pr_id' => 1, 'i_l_id' => 1, 'i_initial' => 100, 'i_qty' => 100, 'i_create_by' => 1, 'i_update_by' => 1, 'i_update_at' => date('Y-m-d H:i:s')],
            ['i_pr_id' => 2, 'i_l_id' => 1, 'i_initial' => 50, 'i_qty' => 50, 'i_create_by' => 1, 'i_update_by' => 1, 'i_update_at' => date('Y-m-d H:i:s')],
            ['i_pr_id' => 3, 'i_l_id' => 1, 'i_initial' => 3, 'i_qty' => 3, 'i_create_by' => 1, 'i_update_by' => 1, 'i_update_at' => date('Y-m-d H:i:s')], // 庫存不足用於測試
            ['i_pr_id' => 4, 'i_l_id' => 1, 'i_initial' => 30, 'i_qty' => 30, 'i_create_by' => 1, 'i_update_by' => 1, 'i_update_at' => date('Y-m-d H:i:s')],
            
            // 地點2 (入庫地點) - 初始庫存為 0
            ['i_pr_id' => 1, 'i_l_id' => 2, 'i_initial' => 0, 'i_qty' => 0, 'i_create_by' => 1, 'i_update_by' => 1, 'i_update_at' => date('Y-m-d H:i:s')],
            ['i_pr_id' => 2, 'i_l_id' => 2, 'i_initial' => 0, 'i_qty' => 0, 'i_create_by' => 1, 'i_update_by' => 1, 'i_update_at' => date('Y-m-d H:i:s')],
            ['i_pr_id' => 3, 'i_l_id' => 2, 'i_initial' => 0, 'i_qty' => 0, 'i_create_by' => 1, 'i_update_by' => 1, 'i_update_at' => date('Y-m-d H:i:s')],
            ['i_pr_id' => 4, 'i_l_id' => 2, 'i_initial' => 0, 'i_qty' => 0, 'i_create_by' => 1, 'i_update_by' => 1, 'i_update_at' => date('Y-m-d H:i:s')],
        ];
        
        $db->table('inventories')->insertBatch($inventoryData);
    }

    /**
     * 取得特定產品在特定地點的庫存數量
     */
    private function getInventoryQty(int $productId, int $locationId): int
    {
        $inventory = $this->inventoryModel
            ->where('i_pr_id', $productId)
            ->where('i_l_id', $locationId)
            ->first();
            
        return $inventory ? (int)$inventory['i_qty'] : 0;
    }

    /**
     * 取得庫存快照 (用於比較前後變化)
     */
    private function getInventorySnapshot(): array
    {
        $inventories = $this->inventoryModel->findAll();
        $snapshot = [];
        
        foreach ($inventories as $inventory) {
            $key = $inventory['i_pr_id'] . '_' . $inventory['i_l_id'];
            $snapshot[$key] = (int)$inventory['i_qty'];
        }
        
        return $snapshot;
    }

    /**
     * 驗證庫存更新是否正確
     */
    private function verifyInventoryUpdates(array $beforeSnapshot, array $orderData, array $detailsData): void
    {
        $afterSnapshot = $this->getInventorySnapshot();
        
        foreach ($detailsData as $detail) {
            $productId = $detail['od_pr_id'];
            $fromLocationId = $orderData['o_from_location'];
            $toLocationId = $orderData['o_to_location'];
            $qty = $detail['od_qty'];
            
            // 檢查出庫地點庫存減少
            $fromKey = $productId . '_' . $fromLocationId;
            $expectedFromQty = $beforeSnapshot[$fromKey] - $qty;
            $actualFromQty = $afterSnapshot[$fromKey];
            $this->assertEquals($expectedFromQty, $actualFromQty, "產品 {$productId} 在地點 {$fromLocationId} 的庫存更新不正確");
            
            // 檢查入庫地點庫存增加
            $toKey = $productId . '_' . $toLocationId;
            $expectedToQty = $beforeSnapshot[$toKey] + $qty;
            $actualToQty = $afterSnapshot[$toKey];
            $this->assertEquals($expectedToQty, $actualToQty, "產品 {$productId} 在地點 {$toLocationId} 的庫存更新不正確");
        }
    }

    /**
     * 測試 createOrder() - 成功建立訂單並驗證庫存正確更新
     */
    public function testCreateOrder_Success()
    {
        // 設定初始庫存
        $this->setupInitialInventory();

        $orderData = [
            'o_type' => 1,
            'o_from_location' => 1,
            'o_to_location' => 2,
            'o_date' => '2024-01-01',
            'o_car_number' => 'TEST-001',
            'o_driver_phone' => '0987654321',
            'o_loading_time' => '2024-01-01 08:00:00',
            'o_unloading_time' => '2024-01-01 17:00:00',
            'o_oxygen' => 15.5,
            'o_acetylene' => 8.2,
            'o_remark' => '測試建立訂單'
        ];

        $detailsData = [
            ['od_pr_id' => 1, 'od_qty' => 10, 'od_length' => 100, 'od_weight' => 500],
            ['od_pr_id' => 2, 'od_qty' => 5, 'od_length' => 50, 'od_weight' => 250]
        ];

        $files = []; // 沒有檔案上傳
        $userId = 1;

        // 記錄庫存更新前的狀態
        $beforeSnapshot = $this->getInventorySnapshot();

        // 執行建立訂單
        $orderId = $this->orderService->createOrder($orderData, $detailsData, $files, $userId);

        // 驗證回傳的訂單 ID
        $this->assertIsInt($orderId);
        $this->assertGreaterThan(0, $orderId);

        // 驗證訂單主表資料
        $savedOrder = $this->orderModel->find($orderId);
        $this->assertNotNull($savedOrder);
        $this->assertEquals($orderData['o_car_number'], $savedOrder['o_car_number']);
        $this->assertEquals($userId, $savedOrder['o_create_by']);
        $this->assertEquals(OrderModel::STATUS_IN_PROGRESS, $savedOrder['o_status']);
        $this->assertNotEmpty($savedOrder['o_number']); // 確認有生成訂單編號

        // 驗證訂單明細資料
        $savedDetails = $this->orderDetailModel->where('od_o_id', $orderId)->findAll();
        $this->assertCount(2, $savedDetails);
        
        foreach ($savedDetails as $detail) {
            $this->assertEquals($orderId, $detail['od_o_id']);
            $this->assertContains($detail['od_pr_id'], [1, 2]);
        }

        // 🔍 驗證庫存正確更新
        $this->verifyInventoryUpdates($beforeSnapshot, $orderData, $detailsData);
        
        // 具體檢查庫存數量
        $this->assertEquals(90, $this->getInventoryQty(1, 1), '產品1在地點1的庫存應該從100減少到90');
        $this->assertEquals(10, $this->getInventoryQty(1, 2), '產品1在地點2的庫存應該從0增加到10');
        $this->assertEquals(45, $this->getInventoryQty(2, 1), '產品2在地點1的庫存應該從50減少到45');
        $this->assertEquals(5, $this->getInventoryQty(2, 2), '產品2在地點2的庫存應該從0增加到5');
    }

    /**
     * 測試 createOrder() - 庫存不足異常
     */
    public function testCreateOrder_InsufficientInventory()
    {
        // 設定初始庫存 (產品3在地點1只有3個庫存)
        $this->setupInitialInventory();

        $orderData = [
            'o_type' => 1,
            'o_from_location' => 1,
            'o_to_location' => 2,
            'o_date' => '2024-01-01',
            'o_car_number' => 'TEST-002',
            'o_driver_phone' => '0987654321',
            'o_loading_time' => '2024-01-01 08:00:00',
            'o_unloading_time' => '2024-01-01 17:00:00',
            'o_oxygen' => 15.5,
            'o_acetylene' => 8.2,
            'o_remark' => '測試庫存不足'
        ];

        $detailsData = [
            ['od_pr_id' => 3, 'od_qty' => 5, 'od_length' => 100, 'od_weight' => 500], // 需要5個，但只有3個庫存
        ];

        $files = [];
        $userId = 1;

        // 記錄庫存更新前的狀態
        $beforeSnapshot = $this->getInventorySnapshot();

        // 期望拋出異常
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/庫存不足/');

        // 執行建立訂單 (應該失敗)
        $this->orderService->createOrder($orderData, $detailsData, $files, $userId);

        // 驗證事務回滾 - 庫存應該沒有變化
        $afterSnapshot = $this->getInventorySnapshot();
        $this->assertEquals($beforeSnapshot, $afterSnapshot, '庫存不足時應該沒有任何庫存變化');

        // 驗證沒有建立訂單記錄
        $orders = $this->orderModel->where('o_car_number', 'TEST-002')->findAll();
        $this->assertCount(0, $orders, '庫存不足時不應該建立訂單');
    }

    /**
     * 測試 updateOrder() - 成功更新訂單
     */
    public function testUpdateOrder_Success()
    {
        // 先建立一個訂單
        $orderId = 1; // 使用 setUp() 中建立的訂單

        $updateData = [
            'o_car_number' => 'UPDATED-123',
            'o_driver_phone' => '0911111111',
            'o_remark' => '更新後的備註'
        ];

        $detailsData = [
            ['od_pr_id' => 3, 'od_qty' => 2, 'od_length' => 80, 'od_weight' => 400] // 使用庫存足夠的數量
        ];

        $files = [];
        $userId = 1;

        // 設定初始庫存
        $this->setupInitialInventory();
        
        // 記錄庫存更新前的狀態
        $beforeSnapshot = $this->getInventorySnapshot();

        // 執行更新
        $result = $this->orderService->updateOrder($orderId, $updateData, $detailsData, $files, $userId);

        // 驗證更新成功
        $this->assertTrue($result);

        // 驗證資料是否正確更新
        $updatedOrder = $this->orderModel->find($orderId);
        $this->assertEquals('UPDATED-123', $updatedOrder['o_car_number']);
        $this->assertEquals('更新後的備註', $updatedOrder['o_remark']);
        $this->assertEquals($userId, $updatedOrder['o_update_by']);
        $this->assertNotNull($updatedOrder['o_update_at']);

        // 驗證庫存正確更新 (UPDATE 操作會先恢復舊影響，再套用新影響)
        $afterSnapshot = $this->getInventorySnapshot();
        $this->assertNotEquals($beforeSnapshot, $afterSnapshot, '庫存應該有變化');
    }

    /**
     * 測試 updateOrder() - 訂單不存在
     */
    public function testUpdateOrder_OrderNotFound()
    {
        $nonExistentId = 9999;
        $updateData = ['o_car_number' => 'TEST-999'];
        $detailsData = [];
        $files = [];
        $userId = 1;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('訂單不存在');

        $this->orderService->updateOrder($nonExistentId, $updateData, $detailsData, $files, $userId);
    }

    /**
     * 測試 deleteOrder() - 成功刪除訂單並驗證庫存恢復
     */
    public function testDeleteOrder_Success()
    {
        // 設定初始庫存
        $this->setupInitialInventory();
        
        // 先為訂單添加一些明細
        $orderId = 1;
        $this->orderDetailModel->insertBatch([
            ['od_o_id' => $orderId, 'od_pr_id' => 1, 'od_qty' => 5, 'od_length' => 50, 'od_weight' => 250],
            ['od_o_id' => $orderId, 'od_pr_id' => 2, 'od_qty' => 3, 'od_length' => 30, 'od_weight' => 150]
        ]);

        // 記錄庫存更新前的狀態
        $beforeSnapshot = $this->getInventorySnapshot();

        // 執行刪除
        $result = $this->orderService->deleteOrder($orderId);

        // 驗證刪除成功
        $this->assertTrue($result);

        // 驗證訂單主表已被刪除
        $deletedOrder = $this->orderModel->find($orderId);
        $this->assertNull($deletedOrder);

        // 驗證訂單明細也被刪除
        $deletedDetails = $this->orderDetailModel->where('od_o_id', $orderId)->findAll();
        $this->assertCount(0, $deletedDetails);

        // 驗證庫存正確恢復 (DELETE 操作會恢復庫存變化)
        $afterSnapshot = $this->getInventorySnapshot();
        $this->assertNotEquals($beforeSnapshot, $afterSnapshot, '刪除訂單時庫存應該恢復');
    }

    /**
     * 測試 deleteOrder() - 訂單不存在
     */
    public function testDeleteOrder_OrderNotFound()
    {
        $nonExistentId = 9999;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('訂單不存在');

        $this->orderService->deleteOrder($nonExistentId);
    }

    /**
     * 測試 createOrder() - 資料庫異常處理
     */
    public function testCreateOrder_DatabaseException()
    {
        $orderData = [
            'o_type' => 1,
            'o_from_location' => 999, // 無效的地點 ID，會造成外鍵約束錯誤
            'o_to_location' => 2,
            'o_date' => '2024-01-01',
            'o_car_number' => 'ERROR-001'
        ];

        $detailsData = [
            ['od_pr_id' => 1, 'od_qty' => 10, 'od_length' => 100, 'od_weight' => 500]
        ];

        $files = [];
        $userId = 1;

        // 記錄訂單數量（用於驗證事務回滾）
        $beforeCount = $this->orderModel->countAll();

        // 預期會拋出異常
        $this->expectException(\Exception::class);

        $this->orderService->createOrder($orderData, $detailsData, $files, $userId);
        
        // 驗證事務回滾，訂單數量不應該增加
        $afterCount = $this->orderModel->countAll();
        $this->assertEquals($beforeCount, $afterCount, '資料庫異常時不應該新增訂單');
    }

    /**
     * 測試庫存服務異常時的處理 (真實庫存不足場景)
     */
    public function testCreateOrder_InventoryServiceException()
    {
        // 設定初始庫存 (產品4在地點1只有30個庫存)
        $this->setupInitialInventory();
        
        $orderData = [
            'o_type' => 1,
            'o_from_location' => 1,
            'o_to_location' => 2,
            'o_date' => '2024-01-01',
            'o_car_number' => 'INVENTORY-ERROR'
        ];

        $detailsData = [
            ['od_pr_id' => 4, 'od_qty' => 50, 'od_length' => 100, 'od_weight' => 500] // 需要50個，但只有30個庫存
        ];

        $files = [];
        $userId = 1;

        // 記錄庫存更新前的狀態
        $beforeSnapshot = $this->getInventorySnapshot();
        $beforeCount = $this->orderModel->countAll();

        // 預期會拋出異常
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('庫存不足');

        $this->orderService->createOrder($orderData, $detailsData, $files, $userId);

        // 驗證事務回滾，訂單不應該被建立
        $afterCount = $this->orderModel->countAll();
        $this->assertEquals($beforeCount, $afterCount, '庫存不足時不應該新增訂單');
        
        // 驗證庫存沒有變化
        $afterSnapshot = $this->getInventorySnapshot();
        $this->assertEquals($beforeSnapshot, $afterSnapshot, '庫存不足時應該沒有任何庫存變化');
    }
}
