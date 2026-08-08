<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\ProductCategoryModel;
use App\Models\ProductImageModel;

class ProductController extends BaseController
{
    private $productModel;
    private $productCategoryModel;
    private $productImageModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->productCategoryModel = new ProductCategoryModel();
        $this->productImageModel = new ProductImageModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = $this->request->getGet('page') ?: 1;

        $result = $this->productModel->getProductsWithCategory($keyword, $page);

        return view('product/index', [
            'data' => $result['data'],
            'keyword' => $keyword,
            'pager' => [
                'currentPage' => $result['currentPage'],
                'totalPages' => $result['totalPages']
            ]
        ]);
    }

    public function create()
    {
        $categories = $this->productCategoryModel->getAllForDropdown();

        return view('product/form', [
            'isEdit' => false,
            'categories' => $categories,
        ]);
    }

    public function edit($id)
    {
        $data = $this->productModel->getProductWithCategory($id);

        if (!$data) {
            return redirect()->to(url_to('ProductController::index'))->with('error', '商品不存在');
        }

        $categories = $this->productCategoryModel->getAllForDropdown();
        $images = $this->productImageModel->getImagesByProductId($id);

        return view('product/form', [
            'isEdit' => true,
            'data' => $data,
            'categories' => $categories,
            'images' => $images,
        ]);
    }

    public function show($id)
    {
        $data = $this->productModel->getProductWithCategory($id);

        if (!$data) {
            return redirect()->to(url_to('ProductController::index'))->with('error', '商品不存在');
        }

        $images = $this->productImageModel->getImagesByProductId($id);

        return view('product/show', [
            'data' => $data,
            'images' => $images,
        ]);
    }

    public function save()
    {
        $requestData = $this->request->getPost();
        $productId = $requestData['p_id'] ?? null;
        $productId = $productId !== null && $productId !== '' ? (int) $productId : null;

        if ($productId && !$this->productModel->find($productId)) {
            return redirect()->to(url_to('ProductController::index'))->with('error', '商品不存在');
        }

        $rules = $this->productModel->getValidationRules();
        $messages = $this->productModel->getValidationMessages();

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $payload = [
            'p_pc_id' => $requestData['p_pc_id'] ?? null,
            'p_name' => trim((string) ($requestData['p_name'] ?? '')),
            'p_supplier' => trim((string) ($requestData['p_supplier'] ?? '')),
            'p_color' => trim((string) ($requestData['p_color'] ?? '')),
            'p_size' => trim((string) ($requestData['p_size'] ?? '')),
            'p_specifications' => trim((string) ($requestData['p_specifications'] ?? '')),
            'p_standard_price' => $requestData['p_standard_price'] ?? 0,
            'p_cost_price' => $requestData['p_cost_price'] ?? 0,
        ];

        try {
            // 儲存商品基本資料
            if ($productId) {
                $this->productModel->update($productId, $payload);
                $savedProductId = $productId;
            } else {
                $savedProductId = $this->productModel->insert($payload);
            }

            // 處理多圖片上傳
            $images = $this->request->getFiles();
            if (isset($images['p_images'])) {
                $uploadedImages = [];
                $uploadDir = FCPATH . 'uploads/products/' . $savedProductId;

                // 建立產品專屬資料夾
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                foreach ($images['p_images'] as $image) {
                    if ($image->isValid() && !$image->hasMoved()) {
                        try {
                            $newName =  $image->getName();
                            $image->move($uploadDir, $newName);

                            $uploadedImages[] = [
                                'pi_p_id' => $savedProductId,
                                'pi_name' => $newName,
                                'pi_created_at' => date('Y-m-d H:i:s'),
                            ];
                        } catch (\Exception $e) {
                            log_message('error', 'Image upload failed: {message}', [
                                'message' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                // 批次儲存圖片記錄
                if (!empty($uploadedImages)) {
                    $this->productImageModel->batchInsert($uploadedImages);
                }
            }

            return redirect()->to(url_to('ProductController::index'))
                ->with('success', '商品儲存成功');
        } catch (\Throwable $th) {
            log_message('error', 'Product save failed: {message}', [
                'message' => $th->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', '儲存失敗：' . $th->getMessage());
        }
    }

    public function delete($id)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return redirect()->to(url_to('ProductController::index'))->with('error', '商品不存在');
        }

        try {
            // 刪除所有產品圖片檔案
            $images = $this->productImageModel->getImagesByProductId($id);
            $uploadDir = FCPATH . 'uploads/products/' . $id;

            foreach ($images as $image) {
                $filePath = $uploadDir . '/' . $image['pi_name'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // 刪除產品資料夾
            if (is_dir($uploadDir)) {
                rmdir($uploadDir);
            }

            // 刪除圖片資料庫記錄
            $this->productImageModel->deleteByProductId($id);

            // 刪除產品
            $this->productModel->delete($id);

            return redirect()->to(url_to('ProductController::index'));
        } catch (\Throwable $th) {
            log_message('error', 'Product delete failed: {message}', [
                'message' => $th->getMessage(),
            ]);

            return redirect()->to(url_to('ProductController::index'))->with('error', '刪除失敗：' . $th->getMessage());
        }
    }

    /**
     * 刪除單張圖片（AJAX）
     */
    public function deleteImage($imageId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '無效的請求'
            ])->setStatusCode(400);
        }

        try {
            $image = $this->productImageModel->find($imageId);

            if (!$image) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '圖片不存在'
                ])->setStatusCode(404);
            }

            // 刪除實體檔案
            $filePath = FCPATH . 'uploads/products/' . $image['pi_p_id'] . '/' . $image['pi_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // 刪除資料庫記錄
            $this->productImageModel->deleteImage($imageId);

            return $this->response->setJSON([
                'success' => true,
                'message' => '圖片刪除成功'
            ]);
        } catch (\Throwable $th) {
            log_message('error', 'Image delete failed: {message}', [
                'message' => $th->getMessage(),
            ]);

            return $this->response->setJSON([
                'success' => false,
                'message' => '刪除失敗：' . $th->getMessage()
            ])->setStatusCode(500);
        }
    }
}
