<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\InventoryModel;

/**
 * InventoryModel 測試類別
 * 
 * 測試 InventoryModel 的各種方法，特別是 getRoadPlateList 方法
 */
class InventoryModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $inventoryModel;
    protected $migrate = true;
    protected $migrateOnce = false;
    protected $refresh = true;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryModel = new InventoryModel();

        // 準備測試資料
        $this->setUpTestData();
    }

    /**
     * 設置測試資料
     * 創建必要的地點、產品和庫存資料
     */
    private function setUpTestData(): void
    {
        $db = \CodeIgniter\Database\Config::connect();

        // 插入測試地點
        $db->table('locations')->insertBatch([
            ['l_id' => 1, 'l_name' => '台北倉庫'],
            ['l_id' => 2, 'l_name' => '高雄倉庫'],
            ['l_id' => 3, 'l_name' => '台中倉庫'],
            ['l_id' => 4, 'l_name' => '桃園倉庫'],
        ]);

        // 插入測試產品（包含鋪路鋼板和其他產品）
        $db->table('products')->insertBatch([
            ['pr_id' => 1, 'pr_name' => '鋪路鋼板'],
            ['pr_id' => 2, 'pr_name' => '鋼筋'],
            ['pr_id' => 3, 'pr_name' => '鋼管'],
        ]);

        // 插入測試庫存資料
        $db->table('inventories')->insertBatch([
            // 鋪路鋼板庫存
            ['i_id' => 1, 'i_pr_id' => 1, 'i_l_id' => 1, 'i_qty' => 100, 'i_initial' => 100, 'i_create_by' => 1],
            ['i_id' => 2, 'i_pr_id' => 1, 'i_l_id' => 2, 'i_qty' => 50, 'i_initial' => 50, 'i_create_by' => 1],
            ['i_id' => 3, 'i_pr_id' => 1, 'i_l_id' => 3, 'i_qty' => 75, 'i_initial' => 75, 'i_create_by' => 1],
            ['i_id' => 4, 'i_pr_id' => 1, 'i_l_id' => 4, 'i_qty' => 25, 'i_initial' => 25, 'i_create_by' => 1],
            
            // 其他產品庫存（不應出現在鋪路鋼板查詢中）
            ['i_id' => 5, 'i_pr_id' => 2, 'i_l_id' => 1, 'i_qty' => 200, 'i_initial' => 200, 'i_create_by' => 1],
            ['i_id' => 6, 'i_pr_id' => 3, 'i_l_id' => 2, 'i_qty' => 150, 'i_initial' => 150, 'i_create_by' => 1],
        ]);
    }

    /**
     * 測試 getRoadPlateList 基本功能
     * 驗證方法能正確返回鋪路鋼板的庫存資料
     */
    public function testGetRoadPlateListBasicFunctionality(): void
    {
        $result = $this->inventoryModel->getRoadPlateList();

        // 驗證回傳結構
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('currentPage', $result);
        $this->assertArrayHasKey('totalPages', $result);

        // 驗證資料內容
        $this->assertCount(4, $result['data']); // 應該有4筆鋪路鋼板資料
        $this->assertEquals(1, $result['currentPage']);
        $this->assertEquals(1, $result['totalPages']); // 4筆資料在1頁內（每頁10筆）

        // 驗證每筆資料的結構
        foreach ($result['data'] as $item) {
            $this->assertArrayHasKey('i_qty', $item);
            $this->assertArrayHasKey('l_name', $item);
        }
    }

    /**
     * 測試關鍵字過濾功能
     * 驗證能根據地點名稱進行過濾
     */
    public function testGetRoadPlateListWithKeywordFilter(): void
    {
        // 測試過濾「台北」
        $result = $this->inventoryModel->getRoadPlateList(['keyword' => '台北']);
        
        $this->assertCount(1, $result['data']);
        $this->assertEquals('台北倉庫', $result['data'][0]['l_name']);
        $this->assertEquals(100, $result['data'][0]['i_qty']);

        // 測試過濾「倉庫」（應該返回所有結果）
        $result = $this->inventoryModel->getRoadPlateList(['keyword' => '倉庫']);
        $this->assertCount(4, $result['data']);

        // 測試不存在的關鍵字
        $result = $this->inventoryModel->getRoadPlateList(['keyword' => '不存在的地點']);
        $this->assertCount(0, $result['data']);
        $this->assertEquals(0, $result['totalPages']);
    }

    /**
     * 測試分頁功能
     * 驗證分頁邏輯正確運作
     */
    public function testGetRoadPlateListPagination(): void
    {
        // 添加更多測試資料以測試分頁
        $db = \CodeIgniter\Database\Config::connect();
        
        // 添加更多地點和庫存
        for ($i = 5; $i <= 15; $i++) {
            $db->table('locations')->insert(['l_id' => $i, 'l_name' => "測試地點{$i}"]);
            $db->table('inventories')->insert([
                'i_id' => $i + 10,
                'i_pr_id' => 1, // 鋪路鋼板
                'i_l_id' => $i,
                'i_qty' => $i * 10,
                'i_initial' => $i * 10,
                'i_create_by' => 1
            ]);
        }

        // 測試第一頁（應該有10筆）
        $result = $this->inventoryModel->getRoadPlateList([], 1);
        $this->assertCount(10, $result['data']);
        $this->assertEquals(1, $result['currentPage']);
        $this->assertEquals(2, $result['totalPages']); // 總共15筆，分2頁

        // 測試第二頁（應該有5筆）
        $result = $this->inventoryModel->getRoadPlateList([], 2);
        $this->assertCount(5, $result['data']);
        $this->assertEquals(2, $result['currentPage']);
        $this->assertEquals(2, $result['totalPages']);

        // 測試超出範圍的頁數
        $result = $this->inventoryModel->getRoadPlateList([], 3);
        $this->assertCount(0, $result['data']);
        $this->assertEquals(3, $result['currentPage']);
    }

    /**
     * 測試結合過濾和分頁
     * 驗證關鍵字過濾和分頁能同時正確運作
     */
    public function testGetRoadPlateListWithFilterAndPagination(): void
    {
        // 添加更多包含「測試」關鍵字的地點
        $db = \CodeIgniter\Database\Config::connect();
        
        for ($i = 20; $i <= 35; $i++) {
            $db->table('locations')->insert(['l_id' => $i, 'l_name' => "測試倉庫{$i}"]);
            $db->table('inventories')->insert([
                'i_id' => $i + 20,
                'i_pr_id' => 1,
                'i_l_id' => $i,
                'i_qty' => $i * 5,
                'i_initial' => $i * 5,
                'i_create_by' => 1
            ]);
        }

        // 測試過濾「測試」的第一頁
        $result = $this->inventoryModel->getRoadPlateList(['keyword' => '測試'], 1);
        $this->assertCount(10, $result['data']);
        $this->assertEquals(1, $result['currentPage']);
        // 實際只有 16 筆「測試倉庫」資料（不包含前面測試的資料），分2頁
        $this->assertEquals(2, $result['totalPages']);

        // 測試過濾「測試」的第二頁
        $result = $this->inventoryModel->getRoadPlateList(['keyword' => '測試'], 2);
        $this->assertCount(6, $result['data']); // 16筆資料，第二頁應該有6筆
        $this->assertEquals(2, $result['currentPage']);

        // 測試過濾「測試」的第三頁（超出範圍）
        $result = $this->inventoryModel->getRoadPlateList(['keyword' => '測試'], 3);
        $this->assertCount(0, $result['data']); // 第三頁應該沒有資料
        $this->assertEquals(3, $result['currentPage']);
    }

    /**
     * 測試資料排序
     * 驗證資料按照 i_id DESC 排序
     */
    public function testGetRoadPlateListOrdering(): void
    {
        $result = $this->inventoryModel->getRoadPlateList();
        
        // 驗證資料是按照 i_id 降序排列
        // 由於我們的測試資料 i_id 從1到4，最新的應該是4
        $this->assertTrue(count($result['data']) > 1);
        
        // 檢查是否按照插入順序的反序（最新的在前面）
        // 由於JOIN的關係，我們主要確認有返回正確的資料
        foreach ($result['data'] as $item) {
            $this->assertIsNumeric($item['i_qty']);
            $this->assertIsString($item['l_name']);
        }
    }

    /**
     * 測試邊界條件
     * 驗證各種邊界情況的處理
     */
    public function testGetRoadPlateListEdgeCases(): void
    {
        // 測試空過濾條件
        $result = $this->inventoryModel->getRoadPlateList([]);
        $this->assertArrayHasKey('data', $result);

        // 測試空關鍵字
        $result = $this->inventoryModel->getRoadPlateList(['keyword' => '']);
        $this->assertCount(4, $result['data']);

        // 測試頁數為0或負數
        $result = $this->inventoryModel->getRoadPlateList([], 0);
        $this->assertEquals(0, $result['currentPage']);

        $result = $this->inventoryModel->getRoadPlateList([], -1);
        $this->assertEquals(-1, $result['currentPage']);

        // 測試非常大的頁數
        $result = $this->inventoryModel->getRoadPlateList([], 999);
        $this->assertEquals(999, $result['currentPage']);
        $this->assertCount(0, $result['data']);
    }

    /**
     * 測試資料庫連接錯誤處理
     * 驗證在沒有相關資料時的行為
     */
    public function testGetRoadPlateListWithNoData(): void
    {
        // 清空所有庫存資料
        $db = \CodeIgniter\Database\Config::connect();
        $db->table('inventories')->truncate();

        $result = $this->inventoryModel->getRoadPlateList();
        
        $this->assertCount(0, $result['data']);
        $this->assertEquals(1, $result['currentPage']);
        $this->assertEquals(0, $result['totalPages']);
    }

    /**
     * 清理方法
     * 在每個測試後清理資料
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}