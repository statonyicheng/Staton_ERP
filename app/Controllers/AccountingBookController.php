<?php

namespace App\Controllers;

use App\Libraries\AccountingBooks;

/**
 * 會計帳簿：日記帳 / 總分類帳 / 明細分類帳
 * 皆由複式簿記分錄（journal_entries）產生，僅供查詢與列印，不提供寫入。
 */
class AccountingBookController extends BaseController
{
    private AccountingBooks $books;

    public function __construct()
    {
        $this->books = new AccountingBooks();
    }

    /** 共用的期間 / 科目篩選 */
    private function filters(): array
    {
        $range = $this->books->dateRange();
        return [
            'from'  => $this->request->getGet('from') ?: $range['min'],
            'to'    => $this->request->getGet('to') ?: $range['max'],
            'acId'  => (int) ($this->request->getGet('ac_id') ?: 0) ?: null,
            'range' => $range,
        ];
    }

    /** 日記帳（序時簿）：期間內每筆分錄依日期排列 */
    public function journal()
    {
        $f = $this->filters();
        return view('books/journal', $f + [
            'rows'     => $this->books->journal($f['from'], $f['to'], $f['acId']),
            'accounts' => $this->books->accountsWithEntries(),
        ]);
    }

    /** 總分類帳：各科目期初＋本期借貸＝期末 */
    public function ledger()
    {
        $f = $this->filters();
        return view('books/ledger', $f + [
            'rows' => $this->books->ledger($f['from'], $f['to']),
        ]);
    }

    /** 明細分類帳：逐科目、逐筆列出並累計餘額 */
    public function detail()
    {
        $f = $this->filters();
        return view('books/detail', $f + [
            'groups'   => $this->books->detail($f['from'], $f['to'], $f['acId']),
            'accounts' => $this->books->accountsWithEntries(),
        ]);
    }
}
