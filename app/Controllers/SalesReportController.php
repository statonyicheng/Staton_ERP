<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class SalesReportController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $ym = $this->request->getGet('ym') ?: '';

        // 有訂單的年月
        $months = array_column($db->table('orders')->select("DISTINCT LEFT(o_date,7) ym", false)
            ->where('o_status !=', 'cancelled')->orderBy('ym', 'DESC')->get()->getResultArray(), 'ym');

        // 依客戶彙整
        $builder = $db->table('orders o')
            ->select('c.c_name, COUNT(*) cnt, SUM(o.o_total_amount) total')
            ->join('customers c', 'c.c_id = o.o_c_id', 'left')
            ->where('o.o_status !=', 'cancelled');
        if ($ym) $builder->like('o.o_date', $ym, 'after');
        $rows = $builder->groupBy('o.o_c_id')->orderBy('total', 'DESC')->get()->getResultArray();

        return view('sales_report/index', ['rows' => $rows, 'ym' => $ym, 'months' => $months]);
    }
}
