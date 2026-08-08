<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'u_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'u_username' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => '登入帳號',
            ],
            'u_password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => '登入密碼',
            ],
            'u_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => '使用者姓名',
            ],
            'u_is_admin' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => '是否為管理員 1:是 0:否',
                'after'      => 'u_name',
            ],
            'u_created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '建立時間',
            ],
            'u_updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => '更新時間',
            ],
        ]);

        $this->forge->addKey('u_id', true);
        $this->forge->addUniqueKey('u_username');
        $this->forge->createTable('users', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}

