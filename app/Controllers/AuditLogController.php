<?php

namespace App\Controllers;

use Config\Permission;

/**
 * 操作紀錄（稽核軌跡）查詢。
 * 只提供檢視與篩選 —— 稽核紀錄不可修改或刪除，因此不提供任何寫入端點。
 */
class AuditLogController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $user    = $this->request->getGet('user');
        $table   = $this->request->getGet('table');
        $action  = $this->request->getGet('action');
        $from    = $this->request->getGet('from');
        $to      = $this->request->getGet('to');
        $page    = max(1, (int) ($this->request->getGet('page') ?: 1));

        $b = $this->db->table('audit_logs');
        if ($keyword) {
            $b->groupStart()->like('al_summary', $keyword)->orLike('al_changes', $keyword)
              ->orLike('al_row_id', $keyword)->groupEnd();
        }
        if ($user)   $b->where('al_username', $user);
        if ($table)  $b->where('al_table', $table);
        if ($action) $b->where('al_action', $action);
        if ($from)   $b->where('al_at >=', $from . ' 00:00:00');
        if ($to)     $b->where('al_at <=', $to . ' 23:59:59');
        $b->orderBy('al_id', 'DESC');

        $perPage = 30;
        $total = $b->countAllResults(false);
        $rows = $b->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return view('audit_log/index', [
            'rows'    => $rows,
            'keyword' => $keyword, 'user' => $user, 'table' => $table,
            'action'  => $action, 'from' => $from, 'to' => $to,
            'users'   => array_column($this->db->table('audit_logs')->select('al_username')->distinct()
                            ->orderBy('al_username')->get()->getResultArray(), 'al_username'),
            'tables'  => array_column($this->db->table('audit_logs')->select('al_table')->distinct()
                            ->orderBy('al_table')->get()->getResultArray(), 'al_table'),
            'total'   => $total,
            'pager'   => ['currentPage' => $page, 'totalPages' => (int) ceil($total / $perPage)],
            'modules' => Permission::MODULES,
        ]);
    }
}
