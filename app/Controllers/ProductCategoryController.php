<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;

class ProductCategoryController extends BaseController
{
    private $productCategoryModel;
    private $productModel;

    public function __construct()
    {
        $this->productCategoryModel = new ProductCategoryModel();
        $this->productModel = new ProductModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $keyword = $keyword !== null ? trim($keyword) : null;
        $keyword = $keyword === '' ? null : $keyword;

        $page = (int) ($this->request->getGet('page') ?: 1);
        $result = $this->productCategoryModel->getList($keyword, $page);

        return view('product_category/index', [
            'data' => $result['data'],
            'keyword' => $keyword,
            'pager' => $result['pager'],
        ]);
    }

    public function create()
    {
        return view('product_category/form', [
            'isEdit' => false,
        ]);
    }

    public function edit($id)
    {
        $data = $this->productCategoryModel->find($id);

        if (!$data) {
            return redirect()->to(url_to('ProductCategoryController::index'))->with('error', '分類不存在');
        }

        return view('product_category/form', [
            'isEdit' => true,
            'data' => $data,
        ]);
    }

    public function save()
    {
        $requestData = $this->request->getPost();
        $categoryId = $requestData['pc_id'] ?? null;
        $categoryId = $categoryId !== null && $categoryId !== '' ? (int) $categoryId : null;

        if ($categoryId && !$this->productCategoryModel->find($categoryId)) {
            return redirect()->to(url_to('ProductCategoryController::index'))->with('error', '分類不存在');
        }

        $rules = $this->productCategoryModel->getValidationRules();
        $messages = $this->productCategoryModel->getValidationMessages();

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $payload = [
            'pc_name' => trim((string) ($requestData['pc_name'] ?? '')),
        ];

        if ($this->productCategoryModel->isNameExists($payload['pc_name'], $categoryId)) {
            return redirect()->back()
                ->withInput()
                ->with('error', '分類名稱已存在');
        }

        try {
            if ($categoryId) {
                $this->productCategoryModel->update($categoryId, $payload);
            } else {
                $this->productCategoryModel->insert($payload);
            }

            return redirect()->to(url_to('ProductCategoryController::index'));
        } catch (\Throwable $th) {
            log_message('error', 'Product category save failed: {message}', [
                'message' => $th->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', '儲存失敗：' . $th->getMessage());
        }
    }

    public function delete($id)
    {
        $data = $this->productCategoryModel->find($id);

        if (!$data) {
            return redirect()->to(url_to('ProductCategoryController::index'))->with('error', '分類不存在');
        }

        $usedCount = $this->productModel->where('p_pc_id', $id)->countAllResults();
        if ($usedCount > 0) {
            return redirect()->to(url_to('ProductCategoryController::index'))->with('error', '此分類正在被 ' . $usedCount . ' 個產品使用，無法刪除');
        }

        try {
            $this->productCategoryModel->delete($id);

            return redirect()->to(url_to('ProductCategoryController::index'));
        } catch (\Throwable $th) {
            log_message('error', 'Product category delete failed: {message}', [
                'message' => $th->getMessage(),
            ]);

            return redirect()->to(url_to('ProductCategoryController::index'))->with('error', '刪除失敗：' . $th->getMessage());
        }
    }
}
