<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (is_cli()) {
            return;
        }

        $session = session();

        if (! $session->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        if (! $session->get('isAdmin')) {
            $session->setFlashdata('error', '您沒有權限執行此操作');

            return redirect()->to('/');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}

