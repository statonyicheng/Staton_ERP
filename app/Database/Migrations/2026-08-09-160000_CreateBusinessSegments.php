<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 業務別（商業模式）改成可維護的資料表。
 *
 * 原本寫死在 `TransactionModel::SEGMENTS` 常數裡：M-0 共用/總部、M-1 企業管家…
 * 問題是公司的商業模式會變（開新事業線、收掉舊的），每次都要改程式碼重新部署；
 * 而且「這條業務線到底在做什麼」沒地方寫，只有代號跟四個字的名稱。
 *
 * 改成資料表後：基本資料管理裡就能新增/修改，並且可以寫下**商業模式定義**，
 * 讓看報表的人知道 M-3 到底涵蓋哪些服務。
 *
 * `bs_in_pl` 控制是否成為四階損益分析的欄位（非營業就不進）。
 * `bs_code` 沿用既有的 M-0…M-5 字串，`gl_transactions.t_segment` 存的就是它，
 * 所以既有 311 筆內帳資料完全不用動。
 */
class CreateBusinessSegments extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('business_segments')) {
            return;
        }

        $this->forge->addField([
            'bs_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'bs_code' => ['type' => 'VARCHAR', 'constraint' => 12, 'comment' => '代號，對應 gl_transactions.t_segment'],
            'bs_name' => ['type' => 'VARCHAR', 'constraint' => 50, 'comment' => '業務別名稱'],
            'bs_definition' => ['type' => 'TEXT', 'null' => true, 'comment' => '商業模式定義：這條業務線做什麼、收入怎麼來'],
            'bs_in_pl' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => '是否列為四階損益分析的欄位'],
            'bs_is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => '停用後不再出現在下拉選單，既有資料不受影響'],
            'bs_sort' => ['type' => 'INT', 'constraint' => 6, 'default' => 0],
            'bs_created_at' => ['type' => 'DATETIME', 'null' => true],
            'bs_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('bs_id', true);
        $this->forge->addUniqueKey('bs_code');
        $this->forge->createTable('business_segments', false, ['ENGINE' => 'InnoDB']);

        // 把原本寫在程式碼裡的那組搬進來，資料與畫面才不會斷層
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['M-0', '共用/總部', '不專屬於單一業務線的收入與費用，例如總部管理成本、共用的行政支出。四階損益裡當作共用欄位分攤或單列。', 1, 10],
            ['M-1', '企業管家', '長期顧問服務：以月費或年約方式，提供企業日常經營所需的行政、財務、法遵協助。', 1, 20],
            ['M-2', '記帳與稅務', '記帳、帳務整理、營所稅與各項稅務申報等經常性委任服務。', 1, 30],
            ['M-3', '工商登記與智財', '公司設立、變更登記、股權移轉、商標與智慧財產權相關申辦。', 1, 40],
            ['M-4', '財務顧問專案', '專案型委任：內控建置、補助案申請、募資規劃、開辦費規劃等一次性顧問專案。', 1, 50],
            ['M-5', '分潤與代收代付', '案件介紹分潤，以及代墊、代繳、代收代付性質的款項（收支兩端相抵，不是本業收入）。', 1, 60],
            ['非營業', '非營業', '與本業無關的收支：股東往來、押金、利息收入等。不列入四階損益分析的業務別欄位。', 0, 70],
        ];

        $batch = [];
        foreach ($rows as [$code, $name, $definition, $inPl, $sort]) {
            $batch[] = [
                'bs_code' => $code, 'bs_name' => $name, 'bs_definition' => $definition,
                'bs_in_pl' => $inPl, 'bs_is_active' => 1, 'bs_sort' => $sort,
                'bs_created_at' => $now, 'bs_updated_at' => $now,
            ];
        }
        $this->db->table('business_segments')->insertBatch($batch);
    }

    public function down()
    {
        $this->forge->dropTable('business_segments', true);
    }
}
