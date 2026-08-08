<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBatches extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'b_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'b_p_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '商品'],
            'b_batch_no' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => '批號'],
            'b_serial' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => '序號'],
            'b_w_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => '倉庫'],
            'b_qty' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '數量'],
            'b_mfg_date' => ['type' => 'DATE', 'null' => true, 'comment' => '製造日'],
            'b_exp_date' => ['type' => 'DATE', 'null' => true, 'comment' => '有效期限'],
            'b_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'b_created_at' => ['type' => 'DATETIME', 'null' => true],
            'b_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('b_id', true);
        $this->forge->addKey('b_p_id');
        $this->forge->createTable('batches', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('batches');
    }
}
