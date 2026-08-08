<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'u_id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'u_username',
        'u_password',
        'u_name',
        'u_is_admin',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'u_created_at';
    protected $updatedField     = 'u_updated_at';

    public function findByUsername(string $username): ?array
    {
        return $this->where('u_username', $username)->first();
    }
}

