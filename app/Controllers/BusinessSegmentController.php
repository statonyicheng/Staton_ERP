<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\EditGuard;
use App\Models\BusinessSegmentModel;

/**
 * 商業模式設定。
 *
 * 四階損益分析是依商業模式分欄的，所以這裡改動會直接影響報表長相：
 * 「列入四階損益」決定它會不會成為報表的一欄。
 */
class BusinessSegmentController extends BaseController
{
    private $model;

    public function __construct()
    {
        $this->model = new BusinessSegmentModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page = (int) ($this->request->getGet('page') ?: 1);
        $data = $this->model->getList($keyword, $page);

        // 每個商業模式已被幾筆交易使用 —— 停用或改名前要知道影響範圍
        $usage = [];
        foreach ($data['data'] as $row) {
            $usage[$row['bs_code']] = $this->model->usageCount($row['bs_code']);
        }

        return view('business_segment/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'usage' => $usage,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('business_segment/form', ['isEdit' => false]);
    }

    public function edit($id)
    {
        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/business-segment')->with('error', '商業模式不存在');

        return view('business_segment/form', [
            'isEdit' => true,
            'data' => $row,
            'usage' => $this->model->usageCount($row['bs_code']),
        ]);
    }

    public function store()
    {
        $data = $this->payload();
        if (is_string($data)) return redirect()->back()->withInput()->with('error', $data);

        $this->model->insert($data);
        BusinessSegmentModel::clearCache();

        return redirect()->to('/business-segment')->with('success', '商業模式已新增：' . $data['bs_code'] . ' ' . $data['bs_name']);
    }

    public function update($id)
    {
        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/business-segment')->with('error', '商業模式不存在');

        if ($msg = EditGuard::check($this->model, $id, $this->request)) {
            return redirect()->back()->withInput()->with('error', $msg);
        }

        $data = $this->payload($id);
        if (is_string($data)) return redirect()->back()->withInput()->with('error', $data);

        // 代號被既有交易引用時不可更改 —— gl_transactions.t_segment 存的是代號字串，
        // 改了代號那些交易就會變成孤兒，報表直接少一塊
        $used = $this->model->usageCount($row['bs_code']);
        if ($used > 0 && $data['bs_code'] !== $row['bs_code']) {
            return redirect()->back()->withInput()
                ->with('error', "代號 {$row['bs_code']} 已被 {$used} 筆交易使用，不可更改代號；名稱與定義可以修改");
        }

        $this->model->update($id, $data);
        BusinessSegmentModel::clearCache();

        return redirect()->to('/business-segment')->with('success', '商業模式已更新');
    }

    public function delete($id)
    {
        $row = $this->model->find($id);
        if (! $row) return redirect()->to('/business-segment')->with('error', '商業模式不存在');

        $used = $this->model->usageCount($row['bs_code']);
        if ($used > 0) {
            return redirect()->to('/business-segment')
                ->with('error', "「{$row['bs_name']}」已被 {$used} 筆交易使用，不能刪除。若不再使用，請改為「停用」——"
                    . '停用後不會出現在下拉選單，但既有資料與歷史報表維持完整。');
        }

        $this->model->delete($id);
        BusinessSegmentModel::clearCache();

        return redirect()->to('/business-segment')->with('success', '商業模式已刪除');
    }

    /** 整理並驗證表單資料；有問題時回傳錯誤訊息字串 */
    private function payload(?int $excludeId = null)
    {
        $code = trim((string) $this->request->getPost('bs_code'));
        $name = trim((string) $this->request->getPost('bs_name'));

        if ($code === '' || $name === '') {
            return '代號與名稱為必填';
        }
        if (mb_strlen($code) > 12) {
            return '代號請控制在 12 個字以內';
        }

        $dup = $this->model->where('bs_code', $code);
        if ($excludeId) $dup->where('bs_id !=', $excludeId);
        if ($dup->countAllResults() > 0) {
            return "代號 {$code} 已存在";
        }

        return [
            'bs_code' => $code,
            'bs_name' => mb_substr($name, 0, 50),
            'bs_definition' => trim((string) $this->request->getPost('bs_definition')) ?: null,
            'bs_in_pl' => $this->request->getPost('bs_in_pl') ? 1 : 0,
            'bs_is_active' => $this->request->getPost('bs_is_active') ? 1 : 0,
            'bs_sort' => (int) $this->request->getPost('bs_sort'),
        ];
    }
}
