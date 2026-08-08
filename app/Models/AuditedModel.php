<?php

namespace App\Models;

use App\Libraries\AuditLogger;
use CodeIgniter\Model;

/**
 * 會自動留下稽核軌跡的 Model 基底。
 *
 * 用法：把既有 Model 的 `extends Model` 改成 `extends AuditedModel` 即可，
 * 其餘設定完全不用動。新增／修改／刪除都會寫進 audit_logs。
 *
 * 限制（重要）：只有「透過 Model 進出的資料」會被記錄。
 * 本系統有些批次作業是直接用 query builder（進貨過帳、製令完工、內帳整批過帳等），
 * 那些地方要自行呼叫 AuditLogger::log() 補記。
 */
abstract class AuditedModel extends Model
{
    /** 修改前的原始資料，供 afterUpdate 比對 */
    private array $auditBefore = [];

    protected $beforeUpdate = ['auditCaptureBefore'];
    protected $afterInsert  = ['auditAfterInsert'];
    protected $afterUpdate  = ['auditAfterUpdate'];
    protected $beforeDelete = ['auditBeforeDelete'];

    protected function auditCaptureBefore(array $data)
    {
        foreach ($this->auditIds($data) as $id) {
            $row = $this->db->table($this->table)->where($this->primaryKey, $id)->get()->getRowArray();
            if ($row) $this->auditBefore[$id] = $row;
        }
        return $data;
    }

    protected function auditAfterInsert(array $data)
    {
        $id = $data['id'] ?? ($this->db->insertID() ?: null);
        AuditLogger::log($this->table, '新增', $id, null, $data['data'] ?? null, $this->auditSummary($data['data'] ?? []));
        return $data;
    }

    protected function auditAfterUpdate(array $data)
    {
        foreach ($this->auditIds($data) as $id) {
            $after = $this->db->table($this->table)->where($this->primaryKey, $id)->get()->getRowArray();
            AuditLogger::log($this->table, '修改', $id, $this->auditBefore[$id] ?? null, $after,
                $this->auditSummary($after ?? []));
            unset($this->auditBefore[$id]);
        }
        return $data;
    }

    protected function auditBeforeDelete(array $data)
    {
        foreach ($this->auditIds($data) as $id) {
            $row = $this->db->table($this->table)->where($this->primaryKey, $id)->get()->getRowArray();
            AuditLogger::log($this->table, '刪除', $id, $row, null, $this->auditSummary($row ?? []));
        }
        return $data;
    }

    /** 從 callback 參數取出受影響的主鍵值 */
    private function auditIds(array $data): array
    {
        $id = $data['id'] ?? null;
        if ($id === null) return [];
        return is_array($id) ? $id : [$id];
    }

    /**
     * 給人看的摘要：優先取單號或名稱類欄位。
     * 子類別可覆寫成自己的規則。
     */
    protected function auditSummary(array $row): ?string
    {
        foreach ($row as $k => $v) {
            if ($v === null || $v === '' || is_array($v)) continue;
            if (preg_match('/_(no|number|code|name|summary|username)$/', $k)) {
                return (string) $v;
            }
        }
        return null;
    }
}
