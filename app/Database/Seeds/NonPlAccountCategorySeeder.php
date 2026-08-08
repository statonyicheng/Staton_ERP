<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * 修正「非損益」科目的資產負債表歸類。
 *
 * 四階損益模型把股東往來、押金、投資這類科目標為「不進損益」（ac_tier='不進損益'、ac_is_pl=0），
 * 這對損益分析是對的 —— 它們確實不該influence營業毛利。
 *
 * 但這些科目在複式簿記裡仍然是**資產負債表項目**，必須有明確的 ac_category，
 * 否則四大財務報表（依 ac_category 分類）會把它們整個漏掉，
 * 導致「資產 = 負債 + 權益」不成立。
 *
 * 本 seeder 只改 ac_category，不動 ac_tier 與 ac_is_pl，
 * 因此四階損益分析的結果完全不受影響。
 */
class NonPlAccountCategorySeeder extends Seeder
{
    /** 科目代碼 => 正確的資產負債表歸類 */
    private array $map = [
        '9001' => '負債',   // 非-股東往來：股東借款給公司，對公司是負債
        '9002' => '資產',   // 非-投資：持有的投資部位
        '9003' => '資產',   // 非-押金：存出保證金
        '9004' => '資產',   // 非-稅捐繳納：預付/暫付稅款
        '9005' => '資產',   // 非-稅捐退還：應收退稅
        '9006' => '資產',   // 非-資金移轉：帳戶間調度
        '9007' => '收入',   // 非-收入退回：收入的減項（業外，不進四階損益）
        '9008' => '收入',   // 非-利息收入：業外收入（不進四階損益）
    ];

    public function run()
    {
        $updated = 0;
        foreach ($this->map as $code => $category) {
            $row = $this->db->table('accounts')->where('ac_code', $code)->get()->getRowArray();
            if (!$row) continue;
            if ($row['ac_category'] === $category) continue;

            $this->db->table('accounts')->where('ac_code', $code)->update([
                'ac_category' => $category,
                'ac_updated_at' => date('Y-m-d H:i:s'),
            ]);
            echo "  {$code} {$row['ac_name']}：{$row['ac_category']} → {$category}\n";
            $updated++;
        }
        echo "非損益科目歸類修正：更新 {$updated} 個科目（ac_tier 與 ac_is_pl 未變動，四階損益不受影響）\n";
    }
}
