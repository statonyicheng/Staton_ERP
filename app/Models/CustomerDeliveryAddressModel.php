<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerDeliveryAddressModel extends Model
{
    protected $table = 'customer_delivery_addresses';
    protected $primaryKey = 'cda_id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'cda_c_id',
        'cda_name',
        'cda_contact_person',
        'cda_phone',
        'cda_city',
        'cda_address',
        'cda_is_default',
        'cda_notes',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'cda_created_at';
    protected $updatedField = 'cda_updated_at';

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['setDefaultAddress'];
    protected $beforeUpdate = ['handleDefaultAddress'];

    /**
     * 取得客戶的所有送貨地址
     */
    public function getByCustomerId($customerId)
    {
        return $this->where('cda_c_id', $customerId)
            ->orderBy('cda_is_default', 'DESC')
            ->orderBy('cda_created_at', 'ASC')
            ->findAll();
    }

    /**
     * 取得客戶的預設送貨地址
     */
    public function getDefaultAddress($customerId)
    {
        return $this->where('cda_c_id', $customerId)
            ->where('cda_is_default', 1)
            ->first();
    }

    /**
     * 設定預設地址（新增時）
     */
    protected function setDefaultAddress(array $data)
    {
        // 確保 cda_c_id 存在
        if (!isset($data['data']['cda_c_id']) || empty($data['data']['cda_c_id'])) {
            return $data;
        }

        $customerId = $data['data']['cda_c_id'];
        $existingCount = $this->where('cda_c_id', $customerId)->countAllResults();
        
        // 如果是第一筆地址，自動設為預設
        if ($existingCount === 0) {
            $data['data']['cda_is_default'] = 1;
        }
        
        // 如果設定為預設，取消其他預設
        if (isset($data['data']['cda_is_default']) && $data['data']['cda_is_default'] == 1) {
            $this->where('cda_c_id', $customerId)
                ->set(['cda_is_default' => 0])
                ->update();
        }
        
        return $data;
    }

    /**
     * 處理預設地址（更新時）
     */
    protected function handleDefaultAddress(array $data)
    {
        if (isset($data['data']['cda_is_default']) && $data['data']['cda_is_default'] == 1) {
            // 取得要更新的記錄
            if (isset($data['id']) && !empty($data['id'])) {
                $record = $this->find($data['id']);
                if ($record && isset($record['cda_c_id'])) {
                    // 取消同一客戶的其他預設地址
                    $this->where('cda_c_id', $record['cda_c_id'])
                        ->where('cda_id !=', $data['id'])
                        ->set(['cda_is_default' => 0])
                        ->update();
                }
            }
        }
        
        return $data;
    }

    /**
     * 刪除地址（如果是預設，自動指定另一筆為預設）
     */
    public function deleteAddress($addressId)
    {
        $address = $this->find($addressId);
        
        if (!$address) {
            return false;
        }
        
        $customerId = $address['cda_c_id'];
        $wasDefault = $address['cda_is_default'];
        
        // 刪除地址
        $result = $this->delete($addressId);
        
        // 如果刪除的是預設地址，自動指定另一筆為預設
        if ($result && $wasDefault) {
            $newDefault = $this->where('cda_c_id', $customerId)
                ->orderBy('cda_created_at', 'ASC')
                ->first();
                
            if ($newDefault) {
                $this->update($newDefault['cda_id'], ['cda_is_default' => 1]);
            }
        }
        
        return $result;
    }

    /**
     * 批次儲存客戶的送貨地址
     */
    public function saveCustomerAddresses($customerId, $addresses)
    {
        if (empty($addresses)) {
            return false;
        }

        foreach ($addresses as $index => $address) {
            $address['cda_c_id'] = $customerId;
            
            // 如果有 ID 則更新，否則新增
            if (!empty($address['cda_id'])) {
                $this->update($address['cda_id'], $address);
            } else {
                $this->insert($address);
            }
        }

        return true;
    }
}

