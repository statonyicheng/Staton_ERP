<?php

namespace App\Models;

use App\Models\AuditedModel;

class JournalEntryModel extends AuditedModel
{
    protected $table = 'journal_entries';
    protected $primaryKey = 'je_id';
    protected $allowedFields = ['je_jv_id', 'je_ac_id', 'je_summary', 'je_debit', 'je_credit', 'je_sort'];
    protected $useTimestamps = false;

    public function deleteByVoucher($jvId)
    {
        return $this->where('je_jv_id', $jvId)->delete();
    }
}
