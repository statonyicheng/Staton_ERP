<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductImageModel extends Model
{
    protected $table = 'product_images';
    protected $primaryKey = 'pi_id';
    protected $allowedFields = [
        'pi_p_id',
        'pi_name',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField = 'pi_created_at';
    protected $updatedField = null; // 不需要更新時間

    /**
     * 根據產品 ID 獲取所有圖片
     */
    public function getImagesByProductId($productId)
    {
        return $this->where('pi_p_id', $productId)
            ->orderBy('pi_created_at', 'ASC')
            ->findAll();
    }

    /**
     * 根據產品 ID 獲取第一張圖片（用於列表頁）
     */
    public function getFirstImageByProductId($productId)
    {
        return $this->where('pi_p_id', $productId)
            ->orderBy('pi_created_at', 'ASC')
            ->first();
    }

    /**
     * 批次新增圖片
     */
    public function BatchInsert(array $data)
    {
        return $this->builder()->insertBatch($data);
    }

    /**
     * 刪除產品的所有圖片（通常在刪除產品時使用）
     */
    public function deleteByProductId($productId)
    {
        return $this->where('pi_p_id', $productId)->delete();
    }

    /**
     * 刪除特定圖片
     */
    public function deleteImage($imageId)
    {
        return $this->delete($imageId);
    }
}

