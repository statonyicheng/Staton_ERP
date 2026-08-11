<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'HomeController::index');
// ERP 模組佔位頁（尚未開發的模組統一導向此，受全域 auth 保護）
$routes->get('module/(:segment)', 'ModuleController::show/$1');
$routes->get('login', 'AuthController::showLogin');
$routes->post('login', 'AuthController::login');
$routes->get('logout', 'AuthController::logout');
$routes->group('profile', function ($routes) {
    $routes->get('/', 'ProfileController::edit');
    $routes->post('update', 'ProfileController::update');
});
$routes->group('user', ['filter' => 'admin'], function ($routes) {
    $routes->get('/', 'UserController::index');
    $routes->get('create', 'UserController::create');
    $routes->get('edit/(:num)', 'UserController::edit/$1');
    $routes->post('save', 'UserController::save');
    $routes->get('delete/(:num)', 'UserController::delete/$1');
});

$routes->group('customer', function ($routes) {
    $routes->get('/', 'CustomerController::index');
    $routes->get('show/(:num)', 'CustomerController::show/$1');
    $routes->get('create', 'CustomerController::create');
    $routes->get('edit/(:num)', 'CustomerController::edit/$1');
    $routes->post('save', 'CustomerController::save');
    $routes->get('delete/(:num)', 'CustomerController::delete/$1');
    $routes->get('delivery-addresses/(:num)', 'CustomerController::getDeliveryAddresses/$1');
    $routes->get('contacts/(:num)', 'CustomerController::getContacts/$1');
});

$routes->group('product', function ($routes) {
    $routes->get('/', 'ProductController::index');
    $routes->get('create', 'ProductController::create');
    $routes->get('show/(:num)', 'ProductController::show/$1');
    $routes->get('edit/(:num)', 'ProductController::edit/$1');
    $routes->post('save', 'ProductController::save');
    $routes->get('delete/(:num)', 'ProductController::delete/$1');
    $routes->post('deleteImage/(:num)', 'ProductController::deleteImage/$1');
});

// 產品分類管理
$routes->group('product-category', function ($routes) {
    $routes->get('/', 'ProductCategoryController::index');
    $routes->get('create', 'ProductCategoryController::create');
    $routes->get('edit/(:num)', 'ProductCategoryController::edit/$1');
    $routes->post('save', 'ProductCategoryController::save');
    $routes->get('delete/(:num)', 'ProductCategoryController::delete/$1');
});

$routes->group('quote', function ($routes) {
    $routes->get('/', 'QuoteController::index');
    $routes->get('create', 'QuoteController::create');
    $routes->get('view/(:num)', 'QuoteController::view/$1');
    $routes->get('print/(:num)', 'QuoteController::print/$1');
    $routes->get('edit/(:num)', 'QuoteController::edit/$1');
    $routes->post('save', 'QuoteController::save');
    $routes->get('delete/(:num)', 'QuoteController::delete/$1');
    $routes->get('get-product/(:num)', 'QuoteController::getProduct/$1');
    $routes->get('getProductImages/(:num)', 'QuoteController::getProductImages/$1');
});

$routes->group('order', function ($routes) {
    $routes->get('/', 'OrderController::index');
    $routes->get('create', 'OrderController::create');
    $routes->get('edit/(:num)', 'OrderController::edit/$1');
    $routes->post('save', 'OrderController::save');
    $routes->get('delete/(:num)', 'OrderController::delete/$1');
    $routes->get('create-from-quote/(:num)', 'OrderController::createFromQuote/$1');
    $routes->get('view/(:num)', 'OrderController::view/$1');
    $routes->get('print/(:num)', 'OrderController::print/$1');
    $routes->get('getProductImages/(:num)', 'OrderController::getProductImages/$1');
});

$routes->group('shipment', function ($routes) {
    $routes->get('/', 'ShipmentController::index');
    $routes->get('create/(:num)', 'ShipmentController::create/$1');
    $routes->get('edit/(:num)', 'ShipmentController::edit/$1');
    $routes->post('save', 'ShipmentController::save');
    $routes->get('delete/(:num)', 'ShipmentController::delete/$1');
    $routes->get('view/(:num)', 'ShipmentController::view/$1');
});

$routes->get('ar-ap-analysis', 'ArApAnalysisController::index');

$routes->group('business-segment', function ($routes) {
    $routes->get('/', 'BusinessSegmentController::index');
    $routes->get('create', 'BusinessSegmentController::create');
    $routes->get('edit/(:num)', 'BusinessSegmentController::edit/$1');
    $routes->post('store', 'BusinessSegmentController::store');
    $routes->post('update/(:num)', 'BusinessSegmentController::update/$1');
    $routes->get('delete/(:num)', 'BusinessSegmentController::delete/$1');
});

$routes->group('payment-method', function ($routes) {
    $routes->get('/', 'PaymentMethodController::index');
    $routes->get('create', 'PaymentMethodController::create');
    $routes->get('edit/(:num)', 'PaymentMethodController::edit/$1');
    $routes->post('store', 'PaymentMethodController::store');
    $routes->post('update/(:num)', 'PaymentMethodController::update/$1');
    $routes->get('delete/(:num)', 'PaymentMethodController::delete/$1');
});

// ===== ERP 模組（第 1 批：基本資料 + 會計基礎）=====
$routes->group('supplier', function ($routes) {
    $routes->get('/', 'SupplierController::index');
    $routes->get('create', 'SupplierController::create');
    $routes->get('edit/(:num)', 'SupplierController::edit/$1');
    $routes->post('store', 'SupplierController::store');
    $routes->post('update/(:num)', 'SupplierController::update/$1');
    $routes->get('delete/(:num)', 'SupplierController::delete/$1');
});

$routes->group('warehouse', function ($routes) {
    $routes->get('/', 'WarehouseController::index');
    $routes->get('create', 'WarehouseController::create');
    $routes->get('edit/(:num)', 'WarehouseController::edit/$1');
    $routes->post('store', 'WarehouseController::store');
    $routes->post('update/(:num)', 'WarehouseController::update/$1');
    $routes->get('delete/(:num)', 'WarehouseController::delete/$1');
});

$routes->group('fixed-asset', function ($routes) {
    $routes->get('/', 'FixedAssetController::index');
    $routes->get('create', 'FixedAssetController::create');
    $routes->get('edit/(:num)', 'FixedAssetController::edit/$1');
    $routes->post('store', 'FixedAssetController::store');
    $routes->post('update/(:num)', 'FixedAssetController::update/$1');
    $routes->get('delete/(:num)', 'FixedAssetController::delete/$1');
});

$routes->group('account', function ($routes) {
    $routes->get('/', 'AccountController::index');
    $routes->get('create', 'AccountController::create');
    $routes->get('edit/(:num)', 'AccountController::edit/$1');
    $routes->post('store', 'AccountController::store');
    $routes->post('update/(:num)', 'AccountController::update/$1');
    $routes->get('delete/(:num)', 'AccountController::delete/$1');
});

// ===== ERP 第 2 批：會計核心（交易登錄 + 報表）=====
$routes->group('transaction', function ($routes) {
    $routes->get('/', 'TransactionController::index');
    $routes->get('create', 'TransactionController::create');
    $routes->get('edit/(:num)', 'TransactionController::edit/$1');
    $routes->post('store', 'TransactionController::store');
    $routes->post('update/(:num)', 'TransactionController::update/$1');
    $routes->get('delete/(:num)', 'TransactionController::delete/$1');
});
$routes->get('pnl', 'TransactionController::pnl');
$routes->get('cashflow', 'TransactionController::cashflow');
$routes->get('ledger', 'TransactionController::ledger');

// ===== ERP 第 3 批：採購管理 =====
$routes->group('purchase-order', function ($routes) {
    $routes->get('/', 'PurchaseOrderController::index');
    $routes->get('create', 'PurchaseOrderController::create');
    $routes->get('edit/(:num)', 'PurchaseOrderController::edit/$1');
    $routes->get('view/(:num)', 'PurchaseOrderController::view/$1');
    $routes->post('save', 'PurchaseOrderController::save');
    $routes->get('delete/(:num)', 'PurchaseOrderController::delete/$1');
});
$routes->group('purchase-requisition', function ($routes) {
    $routes->get('/', 'PurchaseRequisitionController::index');
    $routes->get('create', 'PurchaseRequisitionController::create');
    $routes->get('edit/(:num)', 'PurchaseRequisitionController::edit/$1');
    $routes->post('store', 'PurchaseRequisitionController::store');
    $routes->post('update/(:num)', 'PurchaseRequisitionController::update/$1');
    $routes->get('delete/(:num)', 'PurchaseRequisitionController::delete/$1');
});
$routes->get('purchase-report', 'PurchaseOrderController::report');

// ===== ERP 第 4 批：庫存管理 =====
$routes->get('inventory', 'InventoryController::index');
$routes->group('stock-movement', function ($routes) {
    $routes->get('/', 'StockMovementController::index');
    $routes->get('create', 'StockMovementController::create');
    $routes->post('save', 'StockMovementController::save');
});
$routes->group('goods-receipt', function ($routes) {
    $routes->get('/', 'GoodsReceiptController::index');
    $routes->get('receive/(:num)', 'GoodsReceiptController::receive/$1');
    $routes->post('receive/(:num)', 'GoodsReceiptController::doReceive/$1');
});

// ===== ERP 第 5 批：應收 / 應付 / 收付款 =====
$routes->group('payable', function ($routes) {
    $routes->get('/', 'PayableController::index');
    $routes->get('generate', 'PayableController::generate');
    $routes->get('create', 'PayableController::create');
    $routes->get('edit/(:num)', 'PayableController::edit/$1');
    $routes->post('store', 'PayableController::store');
    $routes->post('update/(:num)', 'PayableController::update/$1');
    $routes->get('delete/(:num)', 'PayableController::delete/$1');
    $routes->get('pay/(:num)', 'PayableController::pay/$1');
    $routes->post('pay/(:num)', 'PayableController::doPay/$1');
});
$routes->group('receivable', function ($routes) {
    $routes->get('/', 'ReceivableController::index');
    $routes->get('generate', 'ReceivableController::generate');
    $routes->get('create', 'ReceivableController::create');
    $routes->get('edit/(:num)', 'ReceivableController::edit/$1');
    $routes->post('store', 'ReceivableController::store');
    $routes->post('update/(:num)', 'ReceivableController::update/$1');
    $routes->get('delete/(:num)', 'ReceivableController::delete/$1');
    $routes->get('receive/(:num)', 'ReceivableController::receive/$1');
    $routes->post('receive/(:num)', 'ReceivableController::doReceive/$1');
});
$routes->get('settlement', 'SettlementController::index');

// ===== ERP 第 6 批：生產管理 =====
$routes->group('bom', function ($routes) {
    $routes->get('/', 'BomController::index');
    $routes->get('manage', 'BomController::manage');
    $routes->get('manage/(:num)', 'BomController::manage/$1');
    $routes->post('save', 'BomController::save');
    $routes->get('delete/(:num)', 'BomController::delete/$1');
});
$routes->group('work-order', function ($routes) {
    $routes->get('/', 'WorkOrderController::index');
    $routes->get('create', 'WorkOrderController::create');
    $routes->get('edit/(:num)', 'WorkOrderController::edit/$1');
    $routes->get('view/(:num)', 'WorkOrderController::view/$1');
    $routes->post('store', 'WorkOrderController::store');
    $routes->post('update/(:num)', 'WorkOrderController::update/$1');
    $routes->get('delete/(:num)', 'WorkOrderController::delete/$1');
    $routes->post('complete/(:num)', 'WorkOrderController::complete/$1');
});
$routes->get('mrp', 'MrpController::index');

// ===== 借貸分錄傳票（複式簿記）=====
$routes->group('journal', function ($routes) {
    $routes->get('/', 'JournalController::index');
    $routes->get('create', 'JournalController::create');
    $routes->get('next-no', 'JournalController::nextNo');
    $routes->get('edit/(:num)', 'JournalController::edit/$1');
    $routes->get('view/(:num)', 'JournalController::view/$1');
    $routes->post('save', 'JournalController::save');
    $routes->get('delete/(:num)', 'JournalController::delete/$1');
});
$routes->get('auto-journal', 'AutoJournalController::index');
$routes->get('auto-journal/generate/(:segment)/(:num)', 'AutoJournalController::generate/$1/$2');
$routes->get('auto-journal/generate-gl', 'AutoJournalController::generateGlAll');
$routes->get('auto-journal/clear-gl', 'AutoJournalController::clearGl');

// ===== ERP 第 8 批：成本/價格/發票/統計/庫存進階 =====
$routes->group('pricing', function ($routes) {
    $routes->get('/', 'PricingController::index');
    $routes->get('edit/(:num)', 'PricingController::edit/$1');
    $routes->post('update/(:num)', 'PricingController::update/$1');
});
$routes->group('cost', function ($routes) {
    $routes->get('/', 'CostController::index');
    $routes->get('view/(:num)', 'CostController::view/$1');
    $routes->get('apply/(:num)', 'CostController::apply/$1');
});
$routes->group('invoice', function ($routes) {
    $routes->get('/', 'InvoiceController::index');
    $routes->get('create', 'InvoiceController::create');
    $routes->get('edit/(:num)', 'InvoiceController::edit/$1');
    $routes->post('store', 'InvoiceController::store');
    $routes->post('update/(:num)', 'InvoiceController::update/$1');
    $routes->get('void/(:num)', 'InvoiceController::void/$1');
    $routes->get('delete/(:num)', 'InvoiceController::delete/$1');
});
$routes->get('sales-report', 'SalesReportController::index');
$routes->group('stocktake', function ($routes) {
    $routes->get('/', 'StocktakeController::index');
    $routes->post('save', 'StocktakeController::save');
});
$routes->group('batch', function ($routes) {
    $routes->get('/', 'BatchController::index');
    $routes->get('create', 'BatchController::create');
    $routes->get('edit/(:num)', 'BatchController::edit/$1');
    $routes->post('store', 'BatchController::store');
    $routes->post('update/(:num)', 'BatchController::update/$1');
    $routes->get('delete/(:num)', 'BatchController::delete/$1');
});
$routes->get('inventory-valuation', 'InventoryValuationController::index');

// ===== 立沖帳(開放項目)=====
$routes->group('open-item', function ($routes) {
    $routes->get('balance', 'OpenItemController::balance');
    $routes->get('match', 'OpenItemController::match');
    $routes->get('account/(:num)', 'OpenItemController::account/$1');
    $routes->post('offset/(:num)', 'OpenItemController::doOffset/$1');
});

// ===== 四大財務報表（由複式簿記分錄編製）=====
$routes->group('fs', function ($routes) {
    $routes->get('balance', 'FinancialStatementController::balance');
    $routes->get('income', 'FinancialStatementController::income');
    $routes->get('cashflow', 'FinancialStatementController::cashflow');
    $routes->get('equity', 'FinancialStatementController::equity');
});

// ===== 會計帳簿（日記帳 / 總分類帳 / 明細分類帳；由複式簿記分錄產生，僅供查詢）=====
$routes->group('books', function ($routes) {
    $routes->get('journal', 'AccountingBookController::journal');
    $routes->get('ledger', 'AccountingBookController::ledger');
    $routes->get('detail', 'AccountingBookController::detail');
});

// ===== 操作紀錄（稽核軌跡；僅供檢視，不提供任何寫入端點）=====
$routes->get('audit-log', 'AuditLogController::index');

// ===== 匯出（Excel / PDF）=====
$routes->group('export', function ($routes) {
    $routes->get('xlsx/(:segment)', 'ExportController::xlsx/$1');
    $routes->get('pdf/(:segment)', 'ExportController::pdf/$1');
});
