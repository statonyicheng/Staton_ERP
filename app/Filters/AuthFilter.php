<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Permission;

/**
 * 全域過濾器：先確認已登入，再依角色檢查這個網址能不能進。
 *
 * 權限規則集中在 Config\Permission，這裡只負責攔截。
 * 注意：本系統有不少「刪除／過帳」是 GET（例如 xxx/delete/1、auto-journal/generate-gl），
 * Permission::isWrite() 會依網址中的動作字判定，不能只看 HTTP method。
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (is_cli()) {
            return;
        }

        $session = session();

        if (! $session->get('isLoggedIn')) {
            $session->setFlashdata('error', '請先登入');

            return redirect()->to('/login');
        }

        $role = (string) ($session->get('role') ?: ($session->get('isAdmin') ? 'admin' : 'readonly'));
        $uri  = uri_string();

        if (! Permission::allows($role, $uri, $request->getMethod())) {
            $module = Permission::moduleOf($uri);
            $name   = Permission::MODULES[$module] ?? $module;
            $isWrite = Permission::isWrite($uri, $request->getMethod());

            $session->setFlashdata('error', sprintf(
                '權限不足：您目前的角色（%s）%s「%s」。如需開通請聯絡系統管理員。',
                Permission::ROLES[$role] ?? $role,
                $isWrite ? '沒有異動' : '無法存取',
                $name
            ));

            // 避免權限不足時被導回同一頁造成無限轉址
            return redirect()->to(uri_string() === '' ? '/login' : '/');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
