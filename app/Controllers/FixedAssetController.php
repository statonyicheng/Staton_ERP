<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FixedAssetModel;

class FixedAssetController extends BaseController
{
    private $assetModel;

    public function __construct()
    {
        $this->assetModel = new FixedAssetModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->assetModel->getList($keyword, $page);

        return view('fixed_asset/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('fixed_asset/form', ['isEdit' => false]);
    }

    public function store()
    {
        if (!$this->validate($this->assetModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $data = $this->request->getPost();
            $data['fa_code'] = $data['fa_code'] ?: $this->assetModel->generateCode();
            $this->assetModel->insert($data);
            return redirect()->to('/fixed-asset')->with('success', '固定資產新增成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '新增失敗：' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $data = $this->assetModel->find($id);
        if (!$data) return redirect()->to('/fixed-asset')->with('error', '資產不存在');
        return view('fixed_asset/form', ['isEdit' => true, 'data' => $data]);
    }

    public function update($id)
    {
        // 樂觀鎖：這筆資料若在使用者編輯期間被別人改過，就擋下來，不要無聲覆蓋
        if ($msg = \App\Libraries\EditGuard::check('fixed_assets', 'fa_id', $id, 'fa_updated_at', $this->request->getPost(\App\Libraries\EditGuard::FIELD))) {
            return redirect()->back()->withInput()->with('error', $msg);
        }
        if (!$this->assetModel->find($id)) return redirect()->to('/fixed-asset')->with('error', '資產不存在');
        if (!$this->validate($this->assetModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $this->assetModel->update($id, $this->request->getPost());
            return redirect()->to('/fixed-asset')->with('success', '固定資產更新成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '更新失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!$this->assetModel->find($id)) return redirect()->to('/fixed-asset')->with('error', '資產不存在');
        try {
            $this->assetModel->delete($id);
            return redirect()->to('/fixed-asset')->with('success', '固定資產刪除成功');
        } catch (\Exception $e) {
            return redirect()->to('/fixed-asset')->with('error', '刪除失敗：' . $e->getMessage());
        }
    }
}
