<?php
/**
 * 匯出按鈕（Excel / PDF）
 *
 * 用法（在 index / 報表畫面）：
 *   <?= view('components/export_buttons', ['key' => 'customer']) ?>
 *
 * 會自動把目前網址的查詢字串（搜尋關鍵字、期間、年度…）帶到匯出網址，
 * 所以匯出的內容 = 畫面上正在看的內容。
 *
 * 參數：
 *   key   必填，對應 ExportController::catalog() 的登錄鍵
 *   only  選填，'xlsx' 或 'pdf'，只顯示其中一個
 *   size  選填，按鈕大小，預設 'sm'
 */
$key  = $key ?? null;
$only = $only ?? null;
$size = $size ?? 'sm';

if ($key):
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    $suffix = $qs !== '' ? '?' . $qs : '';
    $btn = 'btn btn-outline-primary' . ($size ? ' btn-' . $size : '');
?>
<div class="btn-group export-buttons" role="group">
    <?php if ($only !== 'pdf'): ?>
        <a href="<?= site_url('export/xlsx/' . $key) . $suffix ?>" class="<?= $btn ?>" title="匯出目前畫面的資料為 Excel">
            <i class="bi bi-file-earmark-spreadsheet"></i> Excel
        </a>
    <?php endif; ?>
    <?php if ($only !== 'xlsx'): ?>
        <a href="<?= site_url('export/pdf/' . $key) . $suffix ?>" class="<?= $btn ?>" target="_blank" title="匯出目前畫面的資料為 PDF">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>
