<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerContactModel extends Model
{
    protected $table = 'customer_contacts';
    protected $primaryKey = 'cc_id';
    protected $allowedFields = [
        'cc_c_id',
        'cc_name',
        'cc_phone',
        'cc_email',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'cc_created_at';
    protected $updatedField = 'cc_updated_at';

    /**
     * 取得客戶的聯絡人列表
     */
    public function getByCustomerId($customerId)
    {
        return $this->where('cc_c_id', $customerId)
            ->orderBy('cc_created_at', 'ASC')
            ->findAll();
    }
}

