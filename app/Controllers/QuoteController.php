<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\QuoteModel;
use App\Models\QuoteItemModel;
use App\Models\CustomerModel;
use App\Models\ProductModel;
use App\Models\ProductCategoryModel;
use App\Models\CustomerContactModel;
use App\Traits\ProductImageTrait;

class QuoteController extends BaseController
{
    use ProductImageTrait;
    private $quoteModel;
    private $quoteItemModel;
    private $customerModel;
    private $productModel;
    private $productCategoryModel;
    private $contactModel;

    public function __construct()
    {
        $this->quoteModel = new QuoteModel();
        $this->quoteItemModel = new QuoteItemModel();
        $this->customerModel = new CustomerModel();
        $this->productModel = new ProductModel();
        $this->productCategoryModel = new ProductCategoryModel();
        $this->contactModel = new CustomerContactModel();
    }

    /**
     * 顯示報價單列表
     */
    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = $this->request->getGet('page') ?: 1;
        $perPage = 10;

        // 使用 Model 的分頁查詢方法
        $result = $this->quoteModel->getQuotesWithPagination($keyword, $page, $perPage);

        return view('quote/index', [
            'data' => $result['data'],
            'keyword' => $keyword,
            'pager' => [
                'currentPage' => $page,
                'totalPages' => $result['totalPages']
            ]
        ]);
    }

    /**
     * 顯示新增報價單表單
     */
    public function create()
    {
        $customers = $this->customerModel->findAll();
        $products = $this->productModel->findAll();
        $productCategories = $this->productCategoryModel->getAllForDropdown();
        $quoteNumber = $this->quoteModel->generateQuoteNumber();

        return view('quote/form', [
            'isEdit' => false,
            'customers' => $customers,
            'products' => $products,
            'productCategories' => $productCategories,
            'quoteNumber' => $quoteNumber,
        ]);
    }

    /**
     * 顯示編輯報價單表單
     */
    public function edit($id)
    {
        $quote = $this->quoteModel->find($id);

        if (!$quote) {
            return redirect()->to(url_to('QuoteController::index'))
                ->with('error', '報價單不存在');
        }

        // 取得報價單項目
        $items = $this->quoteItemModel->getItemsWithProduct($id);
        $quote['items'] = $items;

        $customers = $this->customerModel->findAll();
        $products = $this->productModel->findAll();
        $productCategories = $this->productCategoryModel->getAllForDropdown();

        return view('quote/form', [
            'isEdit' => true,
            'data' => $quote,
            'customers' => $customers,
            'products' => $products,
            'productCategories' => $productCategories,
        ]);
    }

    /**
     * 顯示報價單詳細頁
     */
    public function view($id)
    {
        $quote = $this->quoteModel->find($id);

        if (!$quote) {
            return redirect()->to(url_to('QuoteController::index'))
                ->with('error', '報價單不存在');
        }

        // 取得關聯資料
        $customer = $this->customerModel->find($quote['q_c_id']);
        $contact = $quote['q_cc_id'] ? $this->contactModel->find($quote['q_cc_id']) : null;
        $items = $this->quoteItemModel->getItemsWithProduct($id);

        // 合併資料
        $quote['customer'] = $customer;
        $quote['contact'] = $contact;
        $quote['items'] = $items;

        return view('quote/view', [
            'data' => $quote,
        ]);
    }

    /**
     * 列印報價單
     */
    public function print($id)
    {
        $quote = $this->quoteModel->find($id);

        if (!$quote) {
            return redirect()->to(url_to('QuoteController::index'))
                ->with('error', '報價單不存在');
        }

        // 取得關聯資料
        $customer = $this->customerModel->find($quote['q_c_id']);
        $contact = $quote['q_cc_id'] ? $this->contactModel->find($quote['q_cc_id']) : null;
        $items = $this->quoteItemModel->getItemsWithProduct($id);

        // 合併資料
        $quote['customer'] = $customer;
        $quote['contact'] = $contact;
        $quote['items'] = $items;

        return view('quote/print', [
            'data' => $quote,
        ]);
    }

    /**
     * 儲存報價單（新增/編輯）
     */
    public function save()
    {
        $quoteData = $this->request->getPost();

        // 檢查報價單號唯一性
        $quoteId = $quoteData['q_id'] ?? null;
        $quoteNumber = $quoteData['q_number'] ?? '';

        if (!$this->quoteModel->isQuoteNumberUnique($quoteNumber, $quoteId)) {
            return redirect()->back()
                ->withInput()
                ->with('error', '報價單號已存在，請使用其他編號');
        }

        // 使用 Model 驗證項目
        $validation = $this->quoteModel->validateItems($quoteData['items']);
        if (!$validation['valid']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $validation['message']);
        }

        // 使用 Model 儲存（含事務處理）
        $result = $this->quoteModel->saveQuoteWithItems($quoteData, $quoteData['items']);

        if (!$result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()->to(url_to('QuoteController::index'));
    }

    /**
     * 刪除報價單
     */
    public function delete($id)
    {
        $quote = $this->quoteModel->find($id);

        if (!$quote) {
            return redirect()->to(url_to('QuoteController::index'))
                ->with('error', '報價單不存在');
        }

        // 因為設定了 CASCADE，刪除報價單時會自動刪除相關的項目
        $this->quoteModel->delete($id);

        return redirect()->to(url_to('QuoteController::index'));
    }

    /**
     * AJAX: 取得商品資料
     */
    public function getProduct($id)
    {
        if ($this->request->isAJAX()) {
            $product = $this->productModel->find($id);
            return $this->response->setJSON($product);
        }

        return $this->response->setStatusCode(400);
    }

    // getProductImages() 方法由 ProductImageTrait 提供
}
