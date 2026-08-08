<?php

namespace App\Libraries;

/**
 * 稽核軌跡：誰、何時、改了哪張表的哪一筆、動作、變更前後值。
 *
 * 多人共用的財務系統一定要有這個 —— 出事時才查得到人。
 *
 * 兩種寫入方式：
 *  1. 繼承 App\Models\AuditedModel 的 Model，CRUD 會自動記錄。
 *  2. 繞過 Model 的批次作業（進貨、製令完工、整批過帳等直接用 query builder 的地方），
 *     呼叫 AuditLogger::log() 手動補記。
 *
 * 設計原則：記錄失敗絕不能影響主要業務流程，所以全部包在 try/catch 裡靜默處理。
 */
class AuditLogger
{
    /** 不記錄的欄位（雜訊或機密） */
    private const SKIP_FIELDS = ['u_password', 'created_at', 'updated_at'];

    /** 這些資料表不記錄（本身就是紀錄，或高頻雜訊） */
    private const SKIP_TABLES = ['audit_logs', 'document_sequences', 'migrations', 'sessions'];

    public static function log(
        string $table,
        string $action,
        $rowId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $summary = null
    ): void {
        if (in_array($table, self::SKIP_TABLES, true)) {
            return;
        }

        try {
            $changes = self::diff($before, $after);

            $session = null;
            $ip = null;
            $route = null;
            if (! is_cli()) {
                $session = session();
                $req = service('request');
                $ip = method_exists($req, 'getIPAddress') ? $req->getIPAddress() : null;
                $route = uri_string();
            }

            \Config\Database::connect()->table('audit_logs')->insert([
                'al_at'       => date('Y-m-d H:i:s'),
                'al_user_id'  => $session ? ($session->get('userId') ?: null) : null,
                'al_username' => $session ? ($session->get('username') ?: null) : 'CLI',
                'al_table'    => $table,
                'al_row_id'   => $rowId === null ? null : (string) $rowId,
                'al_action'   => $action,
                'al_summary'  => $summary ? mb_substr($summary, 0, 255) : null,
                'al_changes'  => $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
                'al_ip'       => $ip,
                'al_route'    => $route ? mb_substr($route, 0, 191) : null,
            ]);
        } catch (\Throwable $e) {
            // 稽核記錄失敗不可影響業務流程
            log_message('error', '[AuditLogger] ' . $e->getMessage());
        }
    }

    /** 只留下真正變動的欄位，格式 [欄位 => [舊值, 新值]] */
    private static function diff(?array $before, ?array $after): array
    {
        $out = [];

        if ($before === null && $after !== null) {          // 新增
            foreach ($after as $k => $v) {
                if (in_array($k, self::SKIP_FIELDS, true)) continue;
                if ($v === null || $v === '') continue;
                $out[$k] = [null, self::clip($v)];
            }
            return $out;
        }

        if ($before !== null && $after === null) {          // 刪除
            foreach ($before as $k => $v) {
                if (in_array($k, self::SKIP_FIELDS, true)) continue;
                if ($v === null || $v === '') continue;
                $out[$k] = [self::clip($v), null];
            }
            return $out;
        }

        foreach (($after ?? []) as $k => $v) {              // 修改
            if (in_array($k, self::SKIP_FIELDS, true)) continue;
            $old = $before[$k] ?? null;
            if ((string) $old === (string) $v) continue;
            $out[$k] = [self::clip($old), self::clip($v)];
        }
        return $out;
    }

    private static function clip($v)
    {
        if (is_scalar($v) && is_string($v) && mb_strlen($v) > 200) {
            return mb_substr($v, 0, 200) . '…';
        }
        return $v;
    }
}
