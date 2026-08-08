<?php

namespace App\Libraries;

/**
 * 樂觀鎖：防止兩個人同時編輯同一筆資料時，後存的無聲蓋掉先存的。
 *
 * 情境：A 和 B 同時打開同一張採購單。A 改金額先存，B 改廠商後存
 *       → B 的存檔把 A 的金額改回舊值，而且兩人都不會收到任何提示。
 *
 * 作法：表單帶一個 hidden 欄位記住「打開當下的 updated_at」，
 *       存檔時比對資料庫現值；不一致代表這段期間有別人改過，擋下並提示重新載入。
 *
 * 為什麼不用悲觀鎖（開啟即鎖住）：使用者關掉分頁不會解鎖，單子會卡死沒人能改。
 *
 * 用法：
 *   表單  <?= \App\Libraries\EditGuard::field($data['po_updated_at'] ?? null) ?>
 *   控制器 if ($msg = EditGuard::check('purchase_orders', 'po_id', $id, 'po_updated_at', $this->request->getPost('_version'))) {
 *              return redirect()->back()->withInput()->with('error', $msg);
 *          }
 */
class EditGuard
{
    public const FIELD = '_version';

    /** 產生表單用的 hidden 欄位（新增時 $version 為 null，不會擋） */
    public static function field(?string $version): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . esc((string) $version, 'attr') . '">';
    }

    /**
     * 比對版本。回傳 null 代表可以存檔；回傳字串代表被別人改過（字串為要顯示的訊息）。
     *
     * @param string $table        資料表
     * @param string $pkField      主鍵欄位
     * @param mixed  $id           主鍵值
     * @param string $updatedField 時間戳欄位
     * @param mixed  $posted       表單帶回來的版本值
     */
    public static function check(string $table, string $pkField, $id, string $updatedField, $posted): ?string
    {
        // 新增（沒有 id）或表單沒帶版本（舊表單尚未加上 hidden 欄位）→ 不阻擋，維持相容
        if (empty($id) || $posted === null || $posted === '') {
            return null;
        }

        $row = \Config\Database::connect()->table($table)
            ->select($updatedField)->where($pkField, $id)->get()->getRowArray();

        if (!$row) {
            return '這筆資料已被刪除，無法儲存。';
        }

        $current = (string) ($row[$updatedField] ?? '');
        if ($current === '' || $current === (string) $posted) {
            return null;   // 沒被改過
        }

        return '這筆資料在您編輯期間已被' . self::lastEditor($table, $id) . '修改（'
             . $current . '）。為避免覆蓋對方的變更，請重新載入頁面後再編輯一次。';
    }

    /** 從稽核軌跡查出最後是誰改的，讓提示訊息能指名道姓 */
    private static function lastEditor(string $table, $id): string
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('audit_logs')) return '他人';

            $r = $db->table('audit_logs')
                ->select('al_username')
                ->where('al_table', $table)->where('al_row_id', (string) $id)
                ->orderBy('al_id', 'DESC')->limit(1)->get()->getRowArray();

            $name = $r['al_username'] ?? '';
            return $name !== '' ? '「' . $name . '」' : '他人';
        } catch (\Throwable $e) {
            return '他人';
        }
    }
}
