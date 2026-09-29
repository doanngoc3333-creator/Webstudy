<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        $error = 'Tên đăng nhập cần dài 3–30 ký tự, chỉ gồm chữ, số hoặc dấu gạch dưới.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu cần có ít nhất 6 ký tự.';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        $db = new SQLite3(__DIR__ . '/DATABASE/login.db');
        $check = $db->prepare('SELECT ID FROM users WHERE USERNAME = :u LIMIT 1');
        $check->bindValue(':u', $username, SQLITE3_TEXT);
        if ($check->execute()->fetchArray(SQLITE3_ASSOC)) {
            $error = 'Tên đăng nhập này đã được sử dụng.';
        } else {
            $stmt = $db->prepare('INSERT INTO users (USERNAME, PASSWORD) VALUES (:u, :p)');
            $stmt->bindValue(':u', $username, SQLITE3_TEXT);
            $stmt->bindValue(':p', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
            if ($stmt->execute()) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $db->lastInsertRowID();
                $_SESSION['username'] = $username;
                header('Location: dashboard.php'); exit;
            }
            $error = 'Không thể tạo tài khoản. Vui lòng thử lại.';
        }
    }
}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tạo tài khoản · Webstudy</title><link rel="stylesheet" href="assets/style.css"></head><body class="auth-page"><main class="auth-card"><a class="brand" href="websignin.php">Web<span>study</span></a><h1>Tạo tài khoản</h1><p>Tạo tài khoản mới để bắt đầu lưu tài liệu và ghi chú.</p><?php if ($error): ?><div class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?><form method="post"><div class="form-group"><label for="username">Tên đăng nhập</label><input id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" required autofocus></div><div class="form-group"><label for="password">Mật khẩu</label><input id="password" type="password" name="password" autocomplete="new-password" minlength="6" required></div><div class="form-group"><label for="confirm_password">Nhập lại mật khẩu</label><input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" minlength="6" required></div><button class="button" type="submit">Tạo tài khoản</button></form><p style="text-align:center;margin:20px 0 0">Đã có tài khoản? <a href="websignin.php" style="color:var(--primary);font-weight:700">Đăng nhập</a></p></main></body></html>