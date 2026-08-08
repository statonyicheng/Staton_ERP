<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAccounts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ac_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ac_code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => '科目代號'],
            'ac_name' => ['type' => 'VARCHAR', 'constraint' => 50, 'comment' => '科目名稱'],
            'ac_category' => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => '類別: 收入/支出/非損益'],
            'ac_tier' => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => '損益歸屬: 營業收入/一階成本/二階費用/三階費用/四階費用/不進損益'],
            'ac_is_pl' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => '是否進損益表'],
            'ac_sort' => ['type' => 'INT', 'constraint' => 6, 'default' => 0, 'comment' => '排序'],
            'ac_created_at' => ['type' => 'DATETIME', 'null' => true],
            'ac_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('ac_id', true);
        $this->forge->addKey('ac_name');
        $this->forge->createTable('accounts', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('accounts');
    }
}
