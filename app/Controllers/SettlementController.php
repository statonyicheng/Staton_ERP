<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SettlementModel;

class SettlementController extends BaseController
{
    private $stModel;

    public function __construct()
    {
        $this->stModel = new SettlementModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $direction = $this->request->getGet('dir');
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->stModel->getList($keyword, $page, $direction);

        return view('settlement/index', [
            'data' => $data['data'],
            'keyword' => $keyword, 'direction' => $direction,
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }
}
