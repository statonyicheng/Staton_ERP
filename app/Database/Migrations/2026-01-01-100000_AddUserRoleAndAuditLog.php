<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 多人協作基礎：角色權限 + 稽核軌跡
 *
 * 原本只有 users.u_is_admin 一個布林值，等於「管理員」與「其他所有人」兩種身分，
 * 非管理員一樣進得去會計科目、傳票、庫存。多人共用的財務系統不能這樣。
 *
 *  - users.u_role      角色代碼，實際可用模組由 App\Config\Permission 定義
 *  - audit_logs        誰、何時、改了哪張表哪一筆、動作、變更前後值
 */
class AddUserRoleAndAuditLog extends Migration
{
    public function up()
    {
        // ---------- 角色 ----------
        $this->forge->addColumn('users', [
            'u_role' => [
                'type' => 'VARCHAR', 'constraint' => 20, 'default' => 'readonly',
                'comment' => '角色：admin/accounting/sales/purchasing/readonly',
                'after' => 'u_is_admin',
            ],
        ]);
        // 既有的管理員沿用管理者角色，其餘先給唯讀，由管理員逐一調整
        $this->db->table('users')->where('u_is_admin', 1)->update(['u_role' => 'admin']);

        // ---------- 稽核軌跡 ----------
        $this->forge->addField([
            'al_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'al_at'       => ['type' => 'DATETIME', 'comment' => '發生時間'],
            'al_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => '操作者'],
            'al_username' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => '操作者帳號（保留當下值，使用者被刪也查得到）'],
            'al_table'    => ['type' => 'VARCHAR', 'constraint' => 64, 'comment' => '資料表'],
            'al_row_id'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'comment' => '主鍵值'],
            'al_action'   => ['type' => 'VARCHAR', 'constraint' => 10, 'comment' => '新增/修改/刪除'],
            'al_summary'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => '人看的摘要'],
            'al_changes'  => ['type' => 'TEXT', 'null' => true, 'comment' => '變更前後值 JSON'],
            'al_ip'       => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'al_route'    => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'comment' => '當時的網址路徑'],
        ]);
        $this->forge->addKey('al_id', true);
        $this->forge->addKey('al_at');
        $this->forge->addKey(['al_table', 'al_row_id']);
        $this->forge->addKey('al_user_id');
        $this->forge->createTable('audit_logs', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropColumn('users', 'u_role');
    }
}
