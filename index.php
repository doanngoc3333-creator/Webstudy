<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $db = new SQLite3(__DIR__ . '/DATABASE/login.db');

    $stmt = $db->prepare('SELECT * FROM users WHERE USERNAME = :u AND PASSWORD = :p');
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $stmt->bindValue(':p', $password, SQLITE3_TEXT);

    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row) {
        $_SESSION['user_id'] = $row['ID'];
        $_SESSION['username'] = $row['USERNAME'];
        header('Location: lobby.html');
        exit;
    } else {
        header('Location: websignin.html?error=1');
        exit;
    }
}
?>