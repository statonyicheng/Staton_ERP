<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TransactionModel;
use App\Models\AccountModel;

class TransactionController extends BaseController
{
    private $txModel;
    private $accountModel;

    public function __construct()
    {
        $this->txModel = new TransactionModel();
        $this->accountModel = new AccountModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $ym = $this->request->getGet('ym');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->txModel->getList($keyword, $page, $ym);

        return view('transaction/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'ym' => $ym,
            'months' => $this->txModel->availableMonths(),
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }

    public function create()
    {
        return view('transaction/form', [
            'isEdit' => false,
            'accounts' => $this->accountModel->getAllForDropdown(),
            'segments' => TransactionModel::SEGMENTS,
        ]);
    }

    public function store()
    {
        if (!$this->validate($this->txModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $this->txModel->insert($this->prepare($this->request->getPost()));
            return redirect()->to('/transaction')->with('success', '交易新增成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '新增失敗：' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $data = $this->txModel->find($id);
        if (!$data) return redirect()->to('/transaction')->with('error', '交易不存在');
        return view('transaction/form', [
            'isEdit' => true,
            'data' => $data,
            'accounts' => $this->accountModel->getAllForDropdown(),
            'segments' => TransactionModel::SEGMENTS,
        ]);
    }

    public function update($id)
    {
        // 樂觀鎖：這筆資料若在使用者編輯期間被別人改過，就擋下來，不要無聲覆蓋
        if ($msg = \App\Libraries\EditGuard::check('gl_transactions', 't_id', $id, 't_updated_at', $this->request->getPost(\App\Libraries\EditGuard::FIELD))) {
            return redirect()->back()->withInput()->with('error', $msg);
        }
        if (!$this->txModel->find($id)) return redirect()->to('/transaction')->with('error', '交易不存在');
        if (!$this->validate($this->txModel->getValidationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $this->txModel->update($id, $this->prepare($this->request->getPost()));
            return redirect()->to('/transaction')->with('success', '交易更新成功');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', '更新失敗：' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!$this->txModel->find($id)) return redirect()->to('/transaction')->with('error', '交易不存在');
        $this->txModel->delete($id);
        return redirect()->to('/transaction')->with('success', '交易刪除成功');
    }

    /** 依日期補上年月、依科目類別補上收付方向 */
    private function prepare(array $data): array
    {
        $data['t_ym'] = substr($data['t_date'] ?? '', 0, 7);
        // 收付方向：收入→收、支出→付、非損益→沿用表單選擇
        $acc = $this->accountModel->find($data['t_ac_id'] ?? 0);
        if ($acc) {
            if ($acc['ac_category'] === '收入') $data['t_direction'] = '收';
            elseif ($acc['ac_category'] === '支出') $data['t_direction'] = '付';
            // 非損益：保留 t_direction（表單提供）
        }
        if (empty($data['t_settle_date']) && ($data['t_settle_status'] ?? '') === '已收付') {
            $data['t_settle_date'] = $data['t_date'];
        }
        return $data;
    }

    // ===== 報表 =====

    public function pnl()
    {
        $months = $this->txModel->availableMonths();
        $range  = $this->txModel->periodRange();

        // 期間：預設涵蓋全部資料（內帳跨年度，只看單月看不出全貌）
        $from = $this->request->getGet('from') ?: $range['min'];
        $to   = $this->request->getGet('to') ?: $range['max'];
        $withTax = $this->request->getGet('basis') === 'gross';

        return view('transaction/pnl', [
            'report'  => $this->txModel->pnl($from, $to, $withTax),
            'from'    => $from,
            'to'      => $to,
            'basis'   => $withTax ? 'gross' : 'net',
            'ym'      => $from === $to ? $from : "{$from} ~ {$to}",
            'months'  => $months,
            'range'   => $range,
            'years'   => $this->txModel->availableYears(),
            'segMap'  => TransactionModel::SEGMENTS,
        ]);
    }

    public function cashflow()
    {
        $years = $this->txModel->availableYears();
        $year = (int) ($this->request->getGet('year') ?: ($years[0] ?? date('Y')));
        $opening = $this->txModel->cashOpeningBefore($year);
        return view('transaction/cashflow', [
            'rows' => $this->txModel->cashflow($year, $opening),
            'year' => $year,
            'years' => $years,
            'opening' => $opening,
        ]);
    }

    public function ledger()
    {
        $months = $this->txModel->availableMonths();
        $ym = $this->request->getGet('ym') ?: '';
        return view('transaction/ledger', [
            'rows' => $this->txModel->ledger($ym ?: null),
            'ym' => $ym,
            'months' => $months,
        ]);
    }
}
