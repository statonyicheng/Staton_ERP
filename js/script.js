function confirmDelete(url) {
    if (confirm('確定要刪除這筆資料嗎？')) {
        location.href = url;
    }
}

// 列表整列可點（共用）
document.addEventListener('click', function (e) {
    const row = e.target.closest('.table-row-link');
    if (!row) {
        return;
    }

    // 若點擊到可互動元素，交給元素自己處理
    if (e.target.closest('a, button, input, select, textarea, label')) {
        return;
    }

    const href = row.getAttribute('data-href');
    if (href) {
        window.location.href = href;
    }
});