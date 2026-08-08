<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductStockModel;
use App\Models\WarehouseModel;

class InventoryController extends BaseController
{
    private $stockModel;
    private $warehouseModel;

    public function __construct()
    {
        $this->stockModel = new ProductStockModel();
        $this->warehouseModel = new WarehouseModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $wId = $this->request->getGet('w');
        $hideZero = $this->request->getGet('hidezero') !== '0';
        $page = $this->request->getGet('page') ?: 1;
        $data = $this->stockModel->getList($keyword, $page, $wId, $hideZero);

        return view('inventory/index', [
            'data' => $data['data'],
            'keyword' => $keyword,
            'wId' => $wId,
            'hideZero' => $hideZero,
            'warehouses' => $this->warehouseModel->getAllForDropdown(),
            'summary' => $this->stockModel->totalValueRows(),
            'pager' => ['currentPage' => $data['currentPage'], 'totalPages' => $data['totalPages']],
        ]);
    }
}
