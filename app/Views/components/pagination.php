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
?>

<?php if ($pager['totalPages'] > 1): ?>
    <nav aria-label="分頁導航">
        <ul class="pagination justify-content-center">
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

 