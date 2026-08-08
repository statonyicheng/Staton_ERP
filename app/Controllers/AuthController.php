<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    protected $helpers = ['form'];
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function showLogin()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        return view('auth/login');
    }

    public function login()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/login');
        }

        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return view('auth/login', [
                'validation' => $this->validator,
            ]);
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = $this->userModel->findByUsername($username);

        if (! $user) {
            return redirect()->back()->withInput()->with('error', '帳號不存在');
        }

        if (! password_verify($password, $user['u_password'])) {
            return redirect()->back()->withInput()->with('error', '密碼錯誤');
        }

        // 寫入 Session
        $session = session();
        $session->set([
            'userId'     => $user['u_id'],
            'username'   => $user['u_username'],
            'displayName'=> $user['u_name'],
            'isAdmin'    => (bool) ($user['u_is_admin'] ?? false),
            'isLoggedIn' => true,
        ]);

        return redirect()->to('/');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('message', '已登出系統');
    }
}

