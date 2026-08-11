<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 會計科目標記「應收／應付」歸屬。
 *
 * 系統原本有兩套互不相通的「應付」：
 *   1. 應付帳款管理（`payables` 表）—— 採購那條線的操作模組，只吃結案採購單與手動新增
 *   2. 會計上的應付（分錄傳票貸記 2101/2103…）—— 進資產負債表，但那個畫面看不到
 *
 * 結果是：開一張「借薪資費用／貸應付薪資」的傳票，帳是對的，
 * 但應付帳款管理是空的 —— 使用者以為系統漏了這筆。
 *
 * 現在改成應收/應付管理直接讀帳上的未沖銷餘額，需要知道哪些科目算應收、哪些算應付，
 * 因此加上這個欄位（可在會計科目設定維護）。
 *
 * 同時把被標記的科目一併打開 `ac_open_item` —— 沒有立沖帳追蹤就算不出「未沖銷」，
 * 應收付管理會是一張空表。
 */
class AddAccountArApFlag extends Migration
{
    /** 預設歸屬：科目代碼 => AR|AP */
    private const DEFAULTS = [
        '1103' => 'AR',   // 應收帳款
        '2101' => 'AP',   // 應付帳款
        '2102' => 'AP',   // 應付票據
        '2103' => 'AP',   // 應付薪資
    ];

    public function up()
    {
        if (! $this->db->fieldExists('ac_ar_ap', 'accounts')) {
            $this->forge->addColumn('accounts', [
                'ac_ar_ap' => [
                    'type' => 'VARCHAR', 'constraint' => 2, 'null' => true,
                    'comment' => '應收付歸屬：AR=應收、AP=應付、空=不列入應收付管理',
                    'after' => 'ac_open_item',
                ],
            ]);
        }

        $this->db->resetDataCache();

        foreach (self::DEFAULTS as $code => $type) {
            $this->db->table('accounts')
                ->where('ac_code', $code)
                ->update(['ac_ar_ap' => $type, 'ac_open_item' => 1]);
        }

        // 應付稅款（2104）刻意不預設納入：每一筆含稅收入都會產生一筆應付稅款分錄，
        // 納進來會讓應付清單被幾百筆小額稅款淹沒。要納入請自行在會計科目設定改。
    }

    public function down()
    {
        if ($this->db->fieldExists('ac_ar_ap', 'accounts')) {
            $this->forge->dropColumn('accounts', 'ac_ar_ap');
        }
    }
}
