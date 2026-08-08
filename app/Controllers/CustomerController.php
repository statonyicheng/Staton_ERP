<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CustomerModel;
use App\Models\PaymentMethodModel;
use App\Models\CustomerContactModel;
use App\Models\QuoteModel;
use App\Models\OrderModel;


class CustomerController extends BaseController
{
    private $customerModel;
    private $paymentMethodModel;
    private $contactModel;
    private $quoteModel;
    private $orderModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
        $this->paymentMethodModel = new PaymentMethodModel();
        $this->contactModel = new CustomerContactModel();
        $this->quoteModel = new QuoteModel();
        $this->orderModel = new OrderModel();
    }

    public function show($id)
    {
        $customer = $this->customerModel->getDetailWithPayment($id);

        if (!$customer) {
            return redirect()->to(url_to('CustomerController::index'))
                ->with('error', '客戶不存在');
        }

        $contacts = $this->contactModel->getByCustomerId($id);

        // 報價單 / 訂單分頁（各自 10 筆）
        $qPage = (int) ($this->request->getGet('qPage') ?? 1);
        $oPage = (int) ($this->request->getGet('oPage') ?? 1);
        $perPage = 10;

        $quotes = $this->quoteModel->getByCustomer($id, $qPage, $perPage);
        $orders = $this->orderModel->getByCustomer($id, $oPage, $perPage);

        return view('customer/show', [
            'data' => $customer,
            'contacts' => $contacts,
            'quotes' => $quotes,
            'orders' => $orders,
        ]);
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->customerModel->getList($keyword, $page);
        $pagerData = [
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages']
        ];

        return view('customer/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'pager' => $pagerData
        ]);
    }

    public function create()
    {
        return view('customer/form', [
            'isEdit' => false,
            'contacts' => [],
            'paymentMethods' => $this->paymentMethodModel->getAllForDropdown(),
        ]);
    }

    public function edit($id)
    {
        $data = $this->customerModel->find($id);
        $contacts = $this->contactModel->getByCustomerId($id);
        
        return view('customer/form', [
            'isEdit' => true,
            'data' => $data,
            'contacts' => $contacts,
            'paymentMethods' => $this->paymentMethodModel->getAllForDropdown(),
        ]);
    }

    public function save()
    {
        $data = $this->request->getPost();

        // 開始事務
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 儲存客戶基本資料
            $customerId = $data['c_id'] ?? null;

            if ($customerId) {
                // 更新
                $this->customerModel->update($customerId, $data);
            } else {
                // 新增時自動產生客戶編號
                $data['c_code'] = $this->customerModel->generateCustomerCode();
                $customerId = $this->customerModel->insert($data);
            }

            // 處理聯絡人
            $contacts = $this->request->getPost('contacts');
            if ($contacts !== null) {
                // 先刪除指定聯絡人
                $deletedIds = $this->request->getPost('deleted_contact_ids');
                if ($deletedIds) {
                    $deletedIds = explode(',', $deletedIds);
                    foreach ($deletedIds as $deleteId) {
                        if (!empty($deleteId)) {
                            $this->contactModel->delete($deleteId);
                        }
                    }
                }

                foreach ($contacts as $contact) {
                    // 過濾完全空白的聯絡人
                    if (empty($contact['cc_name']) && empty($contact['cc_phone']) && empty($contact['cc_email'])) {
                        continue;
                    }

                    $contact['cc_c_id'] = $customerId;

                    if (!empty($contact['cc_id'])) {
                        $this->contactModel->update($contact['cc_id'], $contact);
                    } else {
                        $this->contactModel->insert($contact);
                    }
                }
            }

            $db->transComplete();

            return redirect()->to(url_to('CustomerController::index'));

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', $e->getMessage());
            return redirect()->back()->withInput()->with('error', '儲存失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        // 因為設定了 CASCADE，刪除客戶時會自動刪除相關的送貨地址
        $this->customerModel->delete($id);
        return redirect()->to(url_to('CustomerController::index'))->with('success', '刪除成功');
    }

    /**
     * AJAX: 取得聯絡人列表
     */
    public function getContacts($customerId)
    {
        if (empty($customerId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '客戶不存在',
                'data' => [],
            ])->setStatusCode(400);
        }

        $contacts = $this->contactModel->getByCustomerId($customerId);

        return $this->response->setJSON([
            'success' => true,
            'data' => $contacts,
        ]);
    }
}
