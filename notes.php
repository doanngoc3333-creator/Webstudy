<?php
require __DIR__.'/auth.php'; require_login();
header('Content-Type: application/json; charset=utf-8');
$db = new SQLite3(__DIR__.'/DATABASE/notes.db');
$db->exec('CREATE TABLE IF NOT EXISTS notes (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL UNIQUE, content TEXT NOT NULL DEFAULT "", updated_at TEXT NOT NULL)');
$user=(int)$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='GET') { $s=$db->prepare('SELECT content, updated_at FROM notes WHERE user_id=:u');$s->bindValue(':u',$user,SQLITE3_INTEGER);$r=$s->execute()->fetchArray(SQLITE3_ASSOC); echo json_encode($r ?: ['content'=>'','updated_at'=>null]); exit; }
if ($_SERVER['REQUEST_METHOD']==='POST') { $body=json_decode(file_get_contents('php://input'),true);$content=trim((string)($body['content']??''));if(strlen($content)>10000){http_response_code(422);echo json_encode(['error'=>'Ghi chú tối đa 10.000 ký tự']);exit;} $s=$db->prepare('INSERT INTO notes(user_id,content,updated_at) VALUES(:u,:c,datetime("now","localtime")) ON CONFLICT(user_id) DO UPDATE SET content=:c,updated_at=datetime("now","localtime")');$s->bindValue(':u',$user,SQLITE3_INTEGER);$s->bindValue(':c',$content,SQLITE3_TEXT);$s->execute();echo json_encode(['ok'=>true]);exit;}
http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
?>