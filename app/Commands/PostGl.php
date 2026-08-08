<?php

namespace App\Commands;

use App\Libraries\GlJournalPoster;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 把交易登錄（收付制）過帳成借貸傳票。
 *
 *   php spark erp:post-gl            過帳所有未過帳交易
 *   php spark erp:post-gl --status   只看統計
 *   php spark erp:post-gl --clear    清除自動產生的傳票
 */
class PostGl extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:post-gl';
    protected $description = '把交易登錄（收付制）過帳為複式簿記傳票';
    protected $usage       = 'erp:post-gl [--status] [--clear]';

    public function run(array $params)
    {
        $poster = new GlJournalPoster();
        $opts = CLI::getOptions();

        if (array_key_exists('status', $opts)) {
            $s = $poster->stat();
            CLI::write("交易總數 {$s['total']}　已過帳 {$s['posted']}　待過帳 {$s['pending']}");
            return;
        }

        if (array_key_exists('clear', $opts)) {
            $n = $poster->clear();
            CLI::write($n ? "已清除 {$n} 張自動傳票" : '沒有可清除的自動傳票', $n ? 'green' : 'yellow');
            return;
        }

        $r = $poster->postAll();
        if ($r['error']) { CLI::write($r['error'], 'red'); return; }
        CLI::write("已過帳 {$r['ok']} 筆" . ($r['skipped'] ? "，略過 {$r['skipped']} 筆" : ''), 'green');

        $s = $poster->stat();
        CLI::write("目前：交易 {$s['total']}　已過帳 {$s['posted']}　待過帳 {$s['pending']}", 'dark_gray');
    }
}
