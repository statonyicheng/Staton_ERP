<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 檢查 Model 的 $allowedFields 與實際資料表欄位是否一致。
 *
 * 這類漂移最陰險的地方在於：平常不會報錯，只有真的寫入那個欄位時才會炸
 * （Unknown column），或是程式用不存在的欄位當判斷條件而整段被略過。
 *
 * 實際案例（2026-08-08 由 erp:sales-selftest 發現）：
 *   order_items 早期移除了 oi_pi_id 欄位，但 OrderItemModel 仍列著它，
 *   而 OrderModel::saveOrderWithItems() 用 `if (empty($item['oi_pi_id'])) continue;`
 *   當作跳過空白列的條件 —— 結果每一筆訂單明細都被跳過，訂單永遠存不進明細。
 *
 *   php spark erp:schema-drift
 */
class SchemaDrift extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:schema-drift';
    protected $description = '檢查各 Model 的 allowedFields 是否與資料表欄位相符';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $problems = 0;
        $checked  = 0;

        foreach (glob(APPPATH . 'Models/*.php') as $file) {
            $class = 'App\\Models\\' . basename($file, '.php');
            if (!class_exists($class)) continue;

            $ref = new \ReflectionClass($class);
            if ($ref->isAbstract()) continue;

            try {
                $model = new $class();
            } catch (\Throwable $e) {
                continue;
            }

            $tableProp = $ref->getParentClass() ? $ref : $ref;
            $get = function (string $prop) use ($ref, $model) {
                $c = $ref;
                while ($c) {
                    if ($c->hasProperty($prop)) {
                        $p = $c->getProperty($prop);
                        $p->setAccessible(true);
                        return $p->getValue($model);
                    }
                    $c = $c->getParentClass();
                }
                return null;
            };

            $table   = $get('table');
            $allowed = $get('allowedFields');
            $pk      = $get('primaryKey');

            if (!$table || !is_array($allowed) || !$db->tableExists($table)) continue;
            $checked++;

            $cols = array_map(fn($f) => $f->name, $db->getFieldData($table));
            $ghost = array_values(array_diff($allowed, $cols));

            // 資料表有、但 Model 不允許寫入的欄位（排除主鍵與時間戳，那些本來就不該在 allowedFields）
            $ts = array_filter([$get('createdField'), $get('updatedField'), $get('deletedField'), $pk]);
            $unmanaged = array_values(array_diff($cols, $allowed, $ts));

            if ($ghost) {
                $problems++;
                CLI::write("  [FAIL] {$ref->getShortName()}（{$table}）", 'red');
                CLI::write('         allowedFields 有、資料表沒有：' . implode(', ', $ghost), 'red');
                CLI::write('         → 寫入這些欄位會噴 Unknown column；若被當成判斷條件，該段邏輯會整個失效', 'dark_gray');
                if ($unmanaged) {
                    CLI::write('         資料表有、Model 未列入：' . implode(', ', $unmanaged), 'yellow');
                }
            }
        }

        CLI::newLine();
        if ($problems === 0) {
            CLI::write("Model 欄位檢查：{$checked} 個 Model 全部與資料表相符", 'green');
        } else {
            CLI::write("Model 欄位檢查：檢查 {$checked} 個，發現 {$problems} 個有欄位漂移", 'red');
        }
    }
}
