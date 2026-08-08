<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFixedAssets extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'fa_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'fa_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'comment' => '資產編號'],
            'fa_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'comment' => '資產名稱'],
            'fa_category' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => '資產類別'],
            'fa_acquire_date' => ['type' => 'DATE', 'null' => true, 'comment' => '取得日期'],
            'fa_cost' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '取得成本'],
            'fa_useful_life' => ['type' => 'INT', 'constraint' => 4, 'default' => 0, 'comment' => '耐用年數'],
            'fa_salvage' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '殘值'],
            'fa_depr_method' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '直線法', 'comment' => '折舊方法'],
            'fa_location' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => '存放地點'],
            'fa_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '使用中', 'comment' => '狀態'],
            'fa_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => '備註'],
            'fa_created_at' => ['type' => 'DATETIME', 'null' => true],
            'fa_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('fa_id', true);
        $this->forge->addKey('fa_code');
        $this->forge->createTable('fixed_assets', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('fixed_assets');
    }
}
