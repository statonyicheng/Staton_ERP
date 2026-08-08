<?php

namespace App\Traits;

use App\Models\ProductImageModel;

trait ProductImageTrait
{
    /**
     * AJAX: 取得商品的所有圖片
     * 可在任何 Controller 中使用
     */
    public function getProductImages($productId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => '無效的請求'
            ]);
        }

        try {
            $productImageModel = new ProductImageModel();
            $images = $productImageModel->getImagesByProductId($productId);

            return $this->response->setJSON([
                'success' => true,
                'images' => $images
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => '載入圖片失敗：' . $e->getMessage()
            ]);
        }
    }
}

