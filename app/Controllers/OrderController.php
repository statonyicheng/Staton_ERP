<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\CustomerModel;
use App\Models\ProductModel;
use App\Models\ProductCategoryModel;
use App\Models\QuoteModel;
use App\Models\CustomerContactModel;
use App\Traits\ProductImageTrait;

class OrderController extends BaseController
{
    use ProductImageTrait;
    private $orderModel;
    private $customerModel;
    private $productModel;
    private $productCategoryModel;
    private $quoteModel;
    private $customerContactModel;
    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->customerModel = new CustomerModel();
        $this->productModel = new ProductModel();
        $this->productCategoryModel = new ProductCategoryModel();
        $this->quoteModel = new QuoteModel();
        $this->customerContactModel = new CustomerContactModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = $this->request->getGet('page') ?: 1;

        $data = $this->orderModel->getList($keyword, $page);
        $pagerData = [
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages']
        ];

        return view('order/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'pager' => $pagerData
        ]);
    }

    public function create()
    {
        $customers = $this->customerModel->findAll();
        $contacts = [];
        return view('order/form', [
            'isEdit' => false,
            'customers' => $customers,
            'contacts' => $contacts,
            'products' => $this->productModel->findAll(),
            'productCategories' => $this->productCategoryModel->getAllForDropdown(),
            'orderNumber' => $this->orderModel->generateOrderNumber()
        ]);
    }

    public function createFromQuote($quoteId)
    {
        $orderId = $this->orderModel->createFromQuote($quoteId);

        if ($orderId) {
            return redirect()->to(url_to('QuoteController::index'))->with('success', '報價單已成功轉換為訂單');
        }

        return redirect()->to(url_to('QuoteController::index'))
            ->with('error', '建立訂單失敗');
    }

    public function view($id)
    {
        $data = $this->orderModel->getOrderWithItems($id);
        if (!$data) {
            return redirect()->to(url_to('OrderController::index'))->with('error', '訂單不存在');
        }
        return view('order/view', [
            'data' => $data
        ]);
    }

    public function edit($id)
    {
        $data = $this->orderModel->getOrderWithItems($id);
        if (!$data) {
            return redirect()->to(url_to('OrderController::index'))->with('error', '訂單不存在');
        }
        $contacts = $this->customerContactModel->getByCustomerId($data['o_c_id']);

        return view('order/form', [
            'isEdit' => true,
            'data' => $data,
            'customers' => $this->customerModel->findAll(),
            'contacts' => $contacts,
            'products' => $this->productModel->findAll(),
            'productCategories' => $this->productCategoryModel->getAllForDropdown(),
        ]);
    }

    public function save()
    {
        $data = $this->request->getPost();
        $items = $this->request->getPost('items');

        $result = $this->orderModel->saveOrderWithItems($data, $items);

        if (!$result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()->to(url_to('OrderController::index'));
    }

    public function delete($id)
    {
        // 檢查訂單是否存在
        $order = $this->orderModel->find($id);
        if (!$order) {
            return redirect()->to(url_to('OrderController::index'))->with('error', '訂單不存在');
        }

        // 清除相關報價單的訂單關聯
        $this->quoteModel->deleteOrderId($id);

        // 因為設定了 CASCADE，刪除訂單時會自動刪除相關的訂單項目
        if ($this->orderModel->delete($id)) {
            return redirect()->to(url_to('OrderController::index'));
        } else {
            return redirect()->to(url_to('OrderController::index'))->with('error', '訂單刪除失敗');
        }
    }

    public function print($id)
    {
        $data = $this->orderModel->getOrderWithItems($id);
        if (!$data) {
            return redirect()->to(url_to('OrderController::index'))->with('error', '訂單不存在');
        }

        return view('order/print', [
            'data' => $data
        ]);
    }

    // getProductImages() 方法由 ProductImageTrait 提供
}
