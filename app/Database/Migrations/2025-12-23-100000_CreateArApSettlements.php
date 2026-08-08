<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 應收帳款(receivables)、應付帳款(payables)、收付款紀錄(settlements)。
 * 收/付款寫一筆 settlement，並回寫對應憑單的已收/已付與狀態。
 */
class CreateArApSettlements extends Migration
{
    public function up()
    {
        // 應付帳款
        $this->forge->addField([
            'ap_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ap_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'comment' => '應付單號'],
            'ap_s_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => '廠商'],
            'ap_date' => ['type' => 'DATE', 'comment' => '單據日期'],
            'ap_source' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '手動', 'comment' => '來源'],
            'ap_ref_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'comment' => '來源單號'],
            'ap_ref_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'ap_amount' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '應付金額(含稅)'],
            'ap_paid' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '已付'],
            'ap_due_date' => ['type' => 'DATE', 'null' => true, 'comment' => '到期日'],
            'ap_status' => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => '未付款', 'comment' => '狀態'],
            'ap_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ap_created_at' => ['type' => 'DATETIME', 'null' => true],
            'ap_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('ap_id', true);
        $this->forge->addKey('ap_s_id');
        $this->forge->createTable('payables', false, ['ENGINE' => 'InnoDB']);

        // 應收帳款
        $this->forge->addField([
            'ar_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ar_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'comment' => '應收單號'],
            'ar_c_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => '客戶'],
            'ar_date' => ['type' => 'DATE', 'comment' => '單據日期'],
            'ar_source' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '手動', 'comment' => '來源'],
            'ar_ref_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'comment' => '來源單號'],
            'ar_ref_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'ar_amount' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '應收金額(含稅)'],
            'ar_received' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '已收'],
            'ar_due_date' => ['type' => 'DATE', 'null' => true, 'comment' => '到期日'],
            'ar_status' => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => '未收款', 'comment' => '狀態'],
            'ar_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ar_created_at' => ['type' => 'DATETIME', 'null' => true],
            'ar_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('ar_id', true);
        $this->forge->addKey('ar_c_id');
        $this->forge->createTable('receivables', false, ['ENGINE' => 'InnoDB']);

        // 收付款紀錄
        $this->forge->addField([
            'st_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'st_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'comment' => '收付單號'],
            'st_date' => ['type' => 'DATE', 'comment' => '收付日期'],
            'st_direction' => ['type' => 'VARCHAR', 'constraint' => 4, 'comment' => '收/付'],
            'st_target' => ['type' => 'VARCHAR', 'constraint' => 8, 'comment' => '應收/應付'],
            'st_target_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '憑單ID'],
            'st_ref_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'comment' => '憑單號'],
            'st_partner' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => '對象'],
            'st_amount' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => '金額'],
            'st_method' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '現金', 'comment' => '方式'],
            'st_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'st_created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('st_id', true);
        $this->forge->addKey(['st_target', 'st_target_id']);
        $this->forge->createTable('settlements', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('settlements');
        $this->forge->dropTable('receivables');
        $this->forge->dropTable('payables');
    }
}
