<?php
function require_login(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user_id'])) { header('Location: websignin.php'); exit; }
}
function current_user(): string { return htmlspecialchars($_SESSION['username'] ?? 'Bạn', ENT_QUOTES, 'UTF-8'); }
function page_start(string $title, string $active = ''): void {
    $user = current_user();
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.htmlspecialchars($title).' · Webstudy</title><link rel="stylesheet" href="assets/style.css"></head><body><div class="shell"><header class="topbar"><a class="brand" href="dashboard.php">Web<span>study</span></a><nav class="nav"><a class="'.($active==='home'?'active':''). '" href="dashboard.php">⌂ Trang chủ</a><a class="'.($active==='library'?'active':'').'" href="library.html">▣ Thư viện</a><a class="'.($active==='note'?'active':'').'" href="note.php">✎ Ghi chú</a><a class="'.($active==='settings'?'active':'').'" href="settings.php">⚙ Cài đặt</a><span class="avatar">'.strtoupper(substr($user,0,1)).'</span></nav></header>';
}
function page_end(): void { echo '</div></body></html>'; }
?>