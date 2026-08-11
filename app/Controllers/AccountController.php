<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AccountModel;

class AccountController extends BaseController
{
    private $accountModel;

    public function __construct()
    {
        $this->accountModel = new AccountModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $tier = $this->request->getGet('tier');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->accountModel->getList($keyword, $page, $tier);

        return view('account/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'tier' => $tier,
            'tiers' => AccountModel::TIERS,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('account/form', [
            'isEdit' => false,
            'tiers' => AccountModel::TIERS,
            'categories' => AccountModel::CATEGORIES,
        ]);
    }

    public function store()
    {
        if (!$this->validate($this->accountModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $data = $this->request->getPost();
            $data['ac_is_pl'] = ($data['ac_tier'] ?? '') === '不進損益' ? 0 : 1;
            $data['ac_open_item'] = $this->request->getPost('ac_open_item') ? 1 : 0;
            // 應收付歸屬：空字串存成 null，查詢時才不會把「不列入」的科目撈進來
            $arAp = (string) $this->request->getPost('ac_ar_ap');
            $data['ac_ar_ap'] = in_array($arAp, ['AR', 'AP'], true) ? $arAp : null;
            $this->accountModel->insert($data);
            return redirect()->to('/account')->with('success', '會計科目新增成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '新增失敗：' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $data = $this->accountModel->find($id);
        if (!$data) return redirect()->to('/account')->with('error', '科目不存在');
        return view('account/form', [
            'isEdit' => true,
            'data' => $data,
            'tiers' => AccountModel::TIERS,
            'categories' => AccountModel::CATEGORIES,
        ]);
    }

    public function update($id)
    {
        // 樂觀鎖：這筆資料若在使用者編輯期間被別人改過，就擋下來，不要無聲覆蓋
        if ($msg = \App\Libraries\EditGuard::check('accounts', 'ac_id', $id, 'ac_updated_at', $this->request->getPost(\App\Libraries\EditGuard::FIELD))) {
            return redirect()->back()->withInput()->with('error', $msg);
        }
        if (!$this->accountModel->find($id)) return redirect()->to('/account')->with('error', '科目不存在');
        if (!$this->validate($this->accountModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $data = $this->request->getPost();
            $data['ac_is_pl'] = ($data['ac_tier'] ?? '') === '不進損益' ? 0 : 1;
            $data['ac_open_item'] = $this->request->getPost('ac_open_item') ? 1 : 0;
            // 應收付歸屬：空字串存成 null，查詢時才不會把「不列入」的科目撈進來
            $arAp = (string) $this->request->getPost('ac_ar_ap');
            $data['ac_ar_ap'] = in_array($arAp, ['AR', 'AP'], true) ? $arAp : null;
            $this->accountModel->update($id, $data);
            return redirect()->to('/account')->with('success', '會計科目更新成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '更新失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!$this->accountModel->find($id)) return redirect()->to('/account')->with('error', '科目不存在');
        try {
            $this->accountModel->delete($id);
            return redirect()->to('/account')->with('success', '會計科目刪除成功');
        } catch (\Exception $e) {
            return redirect()->to('/account')->with('error', '刪除失敗：' . $e->getMessage());
        }
    }
}
