<?php

namespace App\Controllers;

use App\Models\UserModel;

class ProfileController extends BaseController
{
    protected UserModel $userModel;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function edit()
    {
        $userId = session()->get('userId');
        $user   = $this->userModel->find($userId);

        if (! $user) {
            return redirect()->to('/')->with('error', '使用者不存在');
        }

        return view('profile/edit', [
            'user'        => $user,
            'validation'  => session('validation'),
        ]);
    }

    public function update()
    {
        $userId = session()->get('userId');
        $user   = $this->userModel->find($userId);

        if (! $user) {
            return redirect()->to('/')->with('error', '使用者不存在');
        }

        $password = (string) $this->request->getPost('password');

        $rules = [
            'u_name' => 'required|min_length[2]|max_length[50]',
        ];

        if ($password !== '') {
            $rules['password']         = 'required|matches[password_confirm]';
            $rules['password_confirm'] = 'required';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $data = [
            'u_name' => trim((string) $this->request->getPost('u_name')),
        ];

        if ($password !== '') {
            $data['u_password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->userModel->update($userId, $data);

        session()->set('displayName', $data['u_name']);

        return redirect()->to('/profile')->with('message', '個人資料已更新');
    }
}

