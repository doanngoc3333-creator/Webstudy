<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: websignin.php'); exit; }
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$db = new SQLite3(__DIR__ . '/DATABASE/login.db');
$stmt = $db->prepare('SELECT ID, USERNAME, PASSWORD FROM users WHERE USERNAME = :u LIMIT 1');
$stmt->bindValue(':u', $username, SQLITE3_TEXT);
$row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
// Supports the existing database while also accepting password_hash values for future migrations.
$valid = $row && (password_verify($password, $row['PASSWORD']) || hash_equals((string)$row['PASSWORD'], $password));
if ($valid) { session_regenerate_id(true); $_SESSION['user_id'] = $row['ID']; $_SESSION['username'] = $row['USERNAME']; header('Location: dashboard.php'); exit; }
header('Location: websignin.php?error=1'); exit;
?>