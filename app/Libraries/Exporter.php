<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * 通用匯出：把「欄位定義 + 資料列」輸出成 Excel(.xlsx) 或 PDF。
 *
 * 欄位定義格式：
 *   ['key' => 'ac_code', 'label' => '科目代碼', 'type' => 'text'|'number'|'money'|'date', 'width' => 14]
 *
 * 兩種輸出都套用仕坦登品牌（藏青表頭 + 金色分隔線 + 公司頁尾）。
 */
class Exporter
{
    public const NAVY = '000E2F';
    public const GOLD = 'F4B702';

    private const FONT_KEY  = 'notoseriftc';
    private const FONT_FILE = 'NotoSerifTC-VF.ttf';

    /** 產生安全的下載檔名（中文檔名以 RFC 5987 編碼送出） */
    private static function sendHeaders(string $filename, string $mime): void
    {
        $fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
        header('Content-Type: ' . $mime);
        header("Content-Disposition: attachment; filename=\"{$fallback}\"; filename*=UTF-8''" . rawurlencode($filename));
        header('Cache-Control: max-age=0, must-revalidate');
        header('Pragma: public');
    }

    private static function value(array $row, array $col)
    {
        $v = $row[$col['key']] ?? null;
        return $v;
    }

    private static function display(array $row, array $col): string
    {
        $v = self::value($row, $col);
        if ($v === null || $v === '') return '';
        switch ($col['type'] ?? 'text') {
            case 'number':
            case 'money':
                return number_format((float) $v);
            default:
                return (string) $v;
        }
    }

    // ============================== Excel ==============================

    /**
     * @param array $columns 欄位定義
     * @param array $rows    資料列（關聯陣列）
     * @param array $meta    ['subtitle' => '期間 2026-01 ~ 2026-12', 'note' => '...']
     */
    public static function xlsx(string $filename, string $title, array $columns, array $rows, array $meta = [], ?string $saveTo = null): void
    {
        $ss = new Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $title), 0, 31) ?: '報表');

        $ss->getProperties()
            ->setCreator('仕坦登 ERP')
            ->setCompany('仕坦登企業管理顧問有限公司')
            ->setTitle($title);

        $lastCol = Coordinate::stringFromColumnIndex(max(1, count($columns)));
        $r = 1;

        // 標題列
        $sh->setCellValue("A{$r}", $title);
        $sh->mergeCells("A{$r}:{$lastCol}{$r}");
        $sh->getStyle("A{$r}")->getFont()->setBold(true)->setSize(15)->getColor()->setARGB('FF' . self::NAVY);
        $sh->getRowDimension($r)->setRowHeight(24);
        $r++;

        if (!empty($meta['subtitle'])) {
            $sh->setCellValue("A{$r}", $meta['subtitle']);
            $sh->mergeCells("A{$r}:{$lastCol}{$r}");
            $sh->getStyle("A{$r}")->getFont()->setSize(10)->getColor()->setARGB('FF6B7280');
            $r++;
        }
        $sh->setCellValue("A{$r}", '仕坦登企業管理顧問有限公司　匯出時間 ' . date('Y-m-d H:i'));
        $sh->mergeCells("A{$r}:{$lastCol}{$r}");
        $sh->getStyle("A{$r}")->getFont()->setSize(9)->getColor()->setARGB('FF9AA1AE');
        $r += 2;

        // 表頭
        $headRow = $r;
        foreach ($columns as $i => $col) {
            $c = Coordinate::stringFromColumnIndex($i + 1);
            $sh->setCellValue("{$c}{$headRow}", $col['label']);
        }
        $sh->getStyle("A{$headRow}:{$lastCol}{$headRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . self::NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sh->getRowDimension($headRow)->setRowHeight(22);
        $r++;

        // 資料
        $firstData = $r;
        foreach ($rows as $row) {
            foreach ($columns as $i => $col) {
                $c = Coordinate::stringFromColumnIndex($i + 1);
                $type = $col['type'] ?? 'text';
                $v = self::value($row, $col);
                if ($type === 'number' || $type === 'money') {
                    $sh->setCellValue("{$c}{$r}", $v === null || $v === '' ? null : (float) $v);
                    $sh->getStyle("{$c}{$r}")->getNumberFormat()->setFormatCode('#,##0');
                    $sh->getStyle("{$c}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } else {
                    $sh->setCellValueExplicit("{$c}{$r}", (string) ($v ?? ''),
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
            $r++;
        }
        $lastData = $r - 1;

        if ($lastData >= $firstData) {
            $sh->getStyle("A{$firstData}:{$lastCol}{$lastData}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE6E8EE']]],
            ]);
            // 合計列（數值欄位）；報表本身已含小計/毛利時以 meta['totals']=false 關閉
            $hasNumeric = false;
            foreach ($columns as $col) if (in_array($col['type'] ?? '', ['number', 'money'], true)) $hasNumeric = true;
            if ($hasNumeric && ($meta['totals'] ?? true)) {
                $totalRow = $lastData + 1;
                $sh->setCellValue("A{$totalRow}", '合計');
                foreach ($columns as $i => $col) {
                    if (!in_array($col['type'] ?? '', ['number', 'money'], true)) continue;
                    $c = Coordinate::stringFromColumnIndex($i + 1);
                    $sh->setCellValue("{$c}{$totalRow}", "=SUM({$c}{$firstData}:{$c}{$lastData})");
                    $sh->getStyle("{$c}{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $sh->getStyle("{$c}{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
                $sh->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF' . self::NAVY]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFDF3D4']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF' . self::GOLD]]],
                ]);
            }
        }

        // 欄寬 + 凍結
        foreach ($columns as $i => $col) {
            $c = Coordinate::stringFromColumnIndex($i + 1);
            if (!empty($col['width'])) $sh->getColumnDimension($c)->setWidth((float) $col['width']);
            else $sh->getColumnDimension($c)->setAutoSize(true);
        }
        $sh->freezePane('A' . ($headRow + 1));
        $sh->setAutoFilter("A{$headRow}:{$lastCol}" . max($headRow, $lastData));

        if ($saveTo !== null) {          // CLI / 排程：存檔而非下載
            (new Xlsx($ss))->save($saveTo);
            return;
        }
        self::sendHeaders($filename . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        (new Xlsx($ss))->save('php://output');
        exit;
    }

    // =============================== PDF ===============================

    public static function pdf(string $filename, string $title, array $columns, array $rows, array $meta = [], string $orientation = 'P', ?string $saveTo = null): void
    {
        $tmp = WRITEPATH . 'mpdf';
        if (!is_dir($tmp)) @mkdir($tmp, 0777, true);

        $defaults     = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDefaults = (new \Mpdf\Config\FontVariables())->getDefaults();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4' . ($orientation === 'L' ? '-L' : ''),
            'tempDir' => $tmp,
            'fontDir' => array_merge($defaults['fontDir'], [APPPATH . 'Fonts']),
            'fontdata' => $fontDefaults['fontdata'] + [self::FONT_KEY => ['R' => self::FONT_FILE]],
            'default_font' => self::FONT_KEY,
            'default_font_size' => 9,
            'margin_top' => 18, 'margin_bottom' => 16,
            'margin_left' => 10, 'margin_right' => 10,
        ]);
        $mpdf->SetTitle($title);
        $mpdf->SetAuthor('仕坦登企業管理顧問有限公司');
        $mpdf->SetHTMLFooter(
            '<div style="border-top:1px solid #' . self::GOLD . ';padding-top:4px;font-size:7pt;color:#6b7280;">'
            . '<span style="float:left;">仕坦登企業管理顧問有限公司 · STATON Enterprise ERP</span>'
            . '<span style="float:right;">第 {PAGENO} / {nbpg} 頁</span></div>'
        );

        $mpdf->WriteHTML(self::pdfHtml($title, $columns, $rows, $meta));

        if ($saveTo !== null) {          // CLI / 排程：存檔而非下載
            $mpdf->Output($saveTo, \Mpdf\Output\Destination::FILE);
            return;
        }
        self::sendHeaders($filename . '.pdf', 'application/pdf');
        $mpdf->Output($filename . '.pdf', \Mpdf\Output\Destination::INLINE);
        exit;
    }

    private static function pdfHtml(string $title, array $columns, array $rows, array $meta): string
    {
        $navy = '#' . self::NAVY;
        $gold = '#' . self::GOLD;
        $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

        $css = "
        <style>
            body { font-size: 9pt; color: #1c2230; }
            .hd { border-bottom: 2px solid {$gold}; padding-bottom: 6px; margin-bottom: 10px; }
            .hd h1 { color: {$navy}; font-size: 15pt; margin: 0 0 3px; }
            .hd .sub { color: #6b7280; font-size: 8.5pt; }
            .hd .co { color: #9aa1ae; font-size: 7.5pt; }
            table { width: 100%; border-collapse: collapse; }
            thead th { background: {$navy}; color: #fff; font-size: 8.5pt; padding: 5px 4px; border: 0.4pt solid {$navy}; }
            tbody td { padding: 4px; border: 0.4pt solid #e6e8ee; font-size: 8pt; }
            tbody tr:nth-child(even) td { background: #f7f8fa; }
            tfoot td { background: #fdf3d4; color: {$navy}; font-weight: bold; border-top: 1pt solid {$gold}; padding: 5px 4px; }
            .r { text-align: right; }
            .c { text-align: center; }
            .note { margin-top: 8px; color: #6b7280; font-size: 7.5pt; }
        </style>";

        $head = '<div class="hd"><h1>' . $h($title) . '</h1>';
        if (!empty($meta['subtitle'])) $head .= '<div class="sub">' . $h($meta['subtitle']) . '</div>';
        $head .= '<div class="co">仕坦登企業管理顧問有限公司　匯出時間 ' . date('Y-m-d H:i') . '</div></div>';

        $th = '';
        foreach ($columns as $col) $th .= '<th>' . $h($col['label']) . '</th>';

        $tb = '';
        $totals = [];
        $wantTotals = $meta['totals'] ?? true;
        foreach ($rows as $row) {
            $tb .= '<tr>';
            foreach ($columns as $i => $col) {
                $type = $col['type'] ?? 'text';
                $cls = in_array($type, ['number', 'money'], true) ? ' class="r"' : (($col['align'] ?? '') === 'center' ? ' class="c"' : '');
                if ($wantTotals && in_array($type, ['number', 'money'], true)) {
                    $totals[$i] = ($totals[$i] ?? 0) + (float) (self::value($row, $col) ?: 0);
                }
                $tb .= "<td{$cls}>" . $h(self::display($row, $col)) . '</td>';
            }
            $tb .= '</tr>';
        }
        if (!$rows) $tb = '<tr><td colspan="' . count($columns) . '" class="c">查無資料</td></tr>';

        $tf = '';
        if ($totals) {
            $tf = '<tfoot><tr>';
            foreach ($columns as $i => $col) {
                if (isset($totals[$i])) $tf .= '<td class="r">' . number_format($totals[$i]) . '</td>';
                elseif ($i === 0) $tf .= '<td>合計</td>';
                else $tf .= '<td></td>';
            }
            $tf .= '</tr></tfoot>';
        }

        $note = !empty($meta['note']) ? '<div class="note">' . $h($meta['note']) . '</div>' : '';

        return $css . $head
            . '<table><thead><tr>' . $th . '</tr></thead><tbody>' . $tb . '</tbody>' . $tf . '</table>'
            . $note;
    }
}
