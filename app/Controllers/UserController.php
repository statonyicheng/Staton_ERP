<?php

namespace App\Controllers;

use App\Models\UserModel;

class UserController extends BaseController
{
    protected UserModel $userModel;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $users = $this->userModel->orderBy('u_id', 'DESC')->findAll();

        return view('user/index', [
            'users' => $users,
        ]);
    }

    public function create()
    {
        return view('user/form', [
            'user' => null,
            'validation' => session('validation'),
        ]);
    }

    public function edit(int $id)
    {
        $user = $this->userModel->find($id);

        if (! $user) {
            return redirect()->to('/user')->with('error', '使用者不存在');
        }

        return view('user/form', [
            'user' => $user,
            'validation' => session('validation'),
        ]);
    }

    public function save()
    {
        $id      = $this->request->getPost('u_id');
        $isEdit  = ! empty($id);
        $username = trim((string) $this->request->getPost('u_username'));
        $name     = trim((string) $this->request->getPost('u_name'));
        $password = (string) $this->request->getPost('password');
        $confirm  = (string) $this->request->getPost('password_confirm');

        $uniqueIgnoreId = $isEdit ? $id : '0';

        $rules = [
            'u_username' => 'required|min_length[3]|max_length[50]|is_unique[users.u_username,u_id,' . $uniqueIgnoreId . ']',
            'u_name'     => 'required|min_length[2]|max_length[50]',
        ];

        if (! $isEdit || $password !== '') {
            $rules['password']         = 'required|matches[password_confirm]';
            $rules['password_confirm'] = 'required';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $data = [
            'u_username' => $username,
            'u_name'     => $name,
            'u_is_admin' => $this->request->getPost('u_is_admin') ? 1 : 0,
        ];

        if ($password !== '') {
            $data['u_password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($isEdit) {
            if ((int) $id === (int) session()->get('userId') && $data['u_is_admin'] === 0) {
                return redirect()->back()->withInput()->with('error', '無法取消自身的管理員權限');
            }

            $this->userModel->update($id, $data);

            return redirect()->to('/user')->with('message', '使用者已更新');
        }

        $this->userModel->insert($data);

        return redirect()->to('/user')->with('message', '使用者已新增');
    }

    public function delete(int $id)
    {
        if ((int) $id === (int) session()->get('userId')) {
            return redirect()->to('/user')->with('error', '無法刪除自己');
        }

        if (! $this->userModel->find($id)) {
            return redirect()->to('/user')->with('error', '使用者不存在');
        }

        $this->userModel->delete($id);

        return redirect()->to('/user')->with('message', '使用者已刪除');
    }
}

