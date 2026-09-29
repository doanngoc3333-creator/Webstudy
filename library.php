<?php
require __DIR__ . '/auth.php'; require_login();
header('Cache-Control: no-store');
$db = new SQLite3(__DIR__ . '/DATABASE/library.db');
$db->exec('PRAGMA foreign_keys = ON');
// Migrate the original global library so every existing document belongs to its creator/admin.
$columns = [];
$result = $db->query('PRAGMA table_info(Documents)');
while ($c = $result->fetchArray(SQLITE3_ASSOC)) $columns[] = $c['name'];
if (!in_array('OWNER_ID', $columns, true)) $db->exec('ALTER TABLE Documents ADD COLUMN OWNER_ID INTEGER NOT NULL DEFAULT 1');
if (!in_array('CREATED_AT', $columns, true)) $db->exec('ALTER TABLE Documents ADD COLUMN CREATED_AT TEXT');
$db->exec("UPDATE Documents SET CREATED_AT = datetime('now','localtime') WHERE CREATED_AT IS NULL OR CREATED_AT = ''");
$userId = (int)$_SESSION['user_id'];
function json_response($data, int $status = 200): void { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function safe_name(string $name): string { $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($name)); return trim($name, '._') ?: 'document'; }
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        if (!ctype_digit((string)$_GET['id'])) json_response(['error'=>'ID không hợp lệ'], 400);
        $s = $db->prepare('SELECT Number, Name, Description, FILE_PATH, FILE_TYPE, CREATED_AT FROM Documents WHERE Number=:id AND OWNER_ID=:u');
        $s->bindValue(':id', (int)$_GET['id'], SQLITE3_INTEGER); $s->bindValue(':u', $userId, SQLITE3_INTEGER);
        $row = $s->execute()->fetchArray(SQLITE3_ASSOC);
        if (!$row) json_response(['error'=>'Không tìm thấy tài liệu'], 404);
        json_response($row);
    }
    $s = $db->prepare('SELECT Number, Name, Description, FILE_PATH, FILE_TYPE, CREATED_AT FROM Documents WHERE OWNER_ID=:u ORDER BY CREATED_AT DESC, Name COLLATE NOCASE');
    $s->bindValue(':u', $userId, SQLITE3_INTEGER); $r = $s->execute(); $items=[];
    while ($row=$r->fetchArray(SQLITE3_ASSOC)) $items[]=$row;
    json_response($items);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) json_response(['error'=>'Vui lòng chọn một file hợp lệ'], 400);
    $file = $_FILES['document'];
    if ($file['size'] > 50 * 1024 * 1024) json_response(['error'=>'Mỗi file tối đa 50 MB'], 413);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf'=>'application/pdf','doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document','txt'=>'text/plain','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg'];
    if (!isset($allowed[$ext])) json_response(['error'=>'Chỉ hỗ trợ PDF, Word, TXT và hình ảnh'], 415);
    $mime = $allowed[$ext];
    $dir = __DIR__ . '/DATABASE/storage/users/' . $userId;
    if (!is_dir($dir) && !mkdir($dir, 0750, true)) json_response(['error'=>'Không thể tạo thư mục lưu trữ'], 500);
    $stored = bin2hex(random_bytes(12)) . '_' . safe_name($file['name']);
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) json_response(['error'=>'Không thể lưu file'], 500);
    $relative = 'DATABASE/storage/users/' . $userId . '/' . $stored;
    $title = trim($_POST['name'] ?? pathinfo($file['name'], PATHINFO_FILENAME));
    if ($title === '') $title = $file['name'];
    $s=$db->prepare("INSERT INTO Documents (Name, Description, FILE_PATH, FILE_TYPE, OWNER_ID, CREATED_AT) VALUES (:n,:d,:p,:t,:u,datetime('now','localtime'))");
    $s->bindValue(':n',$title,SQLITE3_TEXT); $s->bindValue(':d','Đã tải lên từ máy của bạn',SQLITE3_TEXT); $s->bindValue(':p',$relative,SQLITE3_TEXT); $s->bindValue(':t',$mime,SQLITE3_TEXT); $s->bindValue(':u',$userId,SQLITE3_INTEGER);
    if (!$s->execute()) { @unlink($dir . '/' . $stored); json_response(['error'=>'Không thể lưu thông tin tài liệu'],500); }
    json_response(['ok'=>true]);
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input); $id=$input['id']??'';
    if (!ctype_digit((string)$id)) json_response(['error'=>'ID không hợp lệ'],400);
    $s=$db->prepare('SELECT FILE_PATH FROM Documents WHERE Number=:id AND OWNER_ID=:u'); $s->bindValue(':id',(int)$id,SQLITE3_INTEGER);$s->bindValue(':u',$userId,SQLITE3_INTEGER);$row=$s->execute()->fetchArray(SQLITE3_ASSOC);
    if (!$row) json_response(['error'=>'Không tìm thấy tài liệu'],404);
    $s=$db->prepare('DELETE FROM Documents WHERE Number=:id AND OWNER_ID=:u');$s->bindValue(':id',(int)$id,SQLITE3_INTEGER);$s->bindValue(':u',$userId,SQLITE3_INTEGER);$s->execute();
    $path=__DIR__.'/'.$row['FILE_PATH']; if (strpos(realpath($path) ?: '', realpath(__DIR__.'/DATABASE/storage/users/'.$userId).DIRECTORY_SEPARATOR)===0) @unlink($path);
    json_response(['ok'=>true]);
}
json_response(['error'=>'Method not allowed'],405);
