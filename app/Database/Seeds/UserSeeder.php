<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('users')->insert([
            'u_username'   => 'admin',
            'u_password'   => password_hash('admin123', PASSWORD_DEFAULT),
            'u_name'       => '系統管理員',
            'u_is_admin'   => 1,
            'u_created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

