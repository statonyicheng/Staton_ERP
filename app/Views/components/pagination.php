<?php
/**
 * 通用分頁組件
 * 
 * 使用方式：
 * echo view('components/pagination', [
 *     'pager' => $pager,
 *     'baseUrl' => 'user',
 *     'params' => $_GET  // 保留所有搜尋參數
 * ]);
 */

// 建立 URL 參數的函數
function buildPagingUrl($baseUrl, $page, $params = [], $pageParam = 'page') {
    // 移除原有的 page 參數，避免重複
    unset($params['page']);
    unset($params[$pageParam]);
    
    // 加入新的 page 參數
    $params[$pageParam] = $page;
    
    // 過濾空值參數
    $params = array_filter($params, function($value) {
        return $value !== '' && $value !== null;
    });
    
    return $baseUrl . '?' . http_build_query($params);
}

// 取得當前的 GET 參數
$currentParams = $params ?? $_GET ?? [];
$pageParam = $pageParam ?? 'page';

// 每頁筆數：換筆數時一律回到第 1 頁，否則可能跳到不存在的頁碼。
// 同一頁面有多個子列表時（例如客戶明細頁的報價／訂單），只讓主列表顯示這個下拉，
// 否則會出現兩個控制同一個設定的選單。
$showPageSize = $showPageSize ?? true;
$pageSizeOptions = \App\Libraries\PageSize::OPTIONS;
$currentPageSize = \App\Libraries\PageSize::current();
$pageSizeParam = \App\Libraries\PageSize::PARAM;
$pageSizeBase = $currentParams;
unset($pageSizeBase['page'], $pageSizeBase[$pageParam], $pageSizeBase[$pageSizeParam]);
$pageSizeBase = array_filter($pageSizeBase, fn($v) => $v !== '' && $v !== null);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
    <?php if ($showPageSize): ?>
        <div class="d-flex align-items-center gap-2 text-muted small">
            <label for="pageSizeSelect" class="mb-0 text-nowrap">每頁顯示</label>
            <select id="pageSizeSelect" class="form-select form-select-sm" style="width: auto;"
                onchange="location.href = this.value;">
                <?php foreach ($pageSizeOptions as $size): ?>
                    <?php $url = $baseUrl . '?' . http_build_query(array_merge($pageSizeBase, [$pageSizeParam => $size])); ?>
                    <option value="<?= esc($url) ?>" <?= $size === $currentPageSize ? 'selected' : '' ?>>
                        <?= $size ?> 筆
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($pager['totalPages'])): ?>
                <span class="text-nowrap">第 <?= $pager['currentPage'] ?> / <?= $pager['totalPages'] ?> 頁</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="ms-auto">
<?php if ($pager['totalPages'] > 1): ?>
    <nav aria-label="分頁導航" class="mb-0">
        <ul class="pagination mb-0">
            <!-- 上一頁 -->
            <li class="page-item <?= $pager['currentPage'] <= 1 ? 'disabled' : '' ?>">
                <?php if ($pager['currentPage'] > 1): ?>
                    <a class="page-link" href="<?= buildPagingUrl($baseUrl, $pager['currentPage'] - 1, $currentParams, $pageParam) ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                <?php else: ?>
                    <span class="page-link" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </span>
                <?php endif; ?>
            </li>
            
            <!-- 頁碼顯示邏輯 -->
            <?php 
            $maxButtons = 7; // 減少顯示的按鈕數以避免混亂
            $currentPage = $pager['currentPage'];
            $totalPages = $pager['totalPages'];
            
            // 計算頁碼範圍
            $startPage = max(1, $currentPage - floor($maxButtons / 2));
            $endPage = min($totalPages, $startPage + $maxButtons - 1);
            
            // 如果結束頁碼小於最大按鈕數，調整開始頁碼
            if ($endPage - $startPage + 1 < $maxButtons && $totalPages >= $maxButtons) {
                $startPage = max(1, $endPage - $maxButtons + 1);
            }
            
            // 判斷是否需要顯示首頁和省略號
            $showFirstPage = $startPage > 1;
            $showFirstEllipsis = $startPage > 2;
            
            // 判斷是否需要顯示末頁和省略號
            $showLastPage = $endPage < $totalPages;
            $showLastEllipsis = $endPage < $totalPages - 1;
            
            // 如果顯示首頁會與主範圍重疊，調整範圍
            if ($showFirstPage && $startPage == 2) {
                $startPage = 1;
                $showFirstPage = false;
                $showFirstEllipsis = false;
            }
            
            // 如果顯示末頁會與主範圍重疊，調整範圍
            if ($showLastPage && $endPage == $totalPages - 1) {
                $endPage = $totalPages;
                $showLastPage = false;
                $showLastEllipsis = false;
            }
            ?>
            
            <!-- 首頁 -->
            <?php if ($showFirstPage): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= buildPagingUrl($baseUrl, 1, $currentParams, $pageParam) ?>">1</a>
                </li>
                <?php if ($showFirstEllipsis): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- 主要頁碼範圍 -->
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i == $currentPage): ?>
                    <li class="page-item active" aria-current="page">
                        <span class="page-link"><?= $i ?></span>
                    </li>
                <?php else: ?>
                    <li class="page-item">
                    <a class="page-link" href="<?= buildPagingUrl($baseUrl, $i, $currentParams, $pageParam) ?>"><?= $i ?></a>
                    </li>
                <?php endif; ?>
            <?php endfor; ?>
            
            <!-- 末頁 -->
            <?php if ($showLastPage): ?>
                <?php if ($showLastEllipsis): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="<?= buildPagingUrl($baseUrl, $totalPages, $currentParams, $pageParam) ?>"><?= $totalPages ?></a>
                </li>
            <?php endif; ?>

            <!-- 下一頁 -->
            <li class="page-item <?= $pager['currentPage'] >= $pager['totalPages'] ? 'disabled' : '' ?>">
                <?php if ($pager['currentPage'] < $pager['totalPages']): ?>
                    <a class="page-link" href="<?= buildPagingUrl($baseUrl, $pager['currentPage'] + 1, $currentParams, $pageParam) ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                <?php else: ?>
                    <span class="page-link" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </span>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
<?php endif; ?>
    </div>
</div>
