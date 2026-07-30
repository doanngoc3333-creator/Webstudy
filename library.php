<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// DATABASE nằm CÙNG CẤP với library.php (không phải thư mục cha)
$dbPath = __DIR__ . '/DATABASE/library.db';


if (!file_exists($dbPath)) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode([
        "error" => "Database not found"
    ]);
    exit;
}


$db = new SQLite3($dbPath);


if (isset($_GET['id'])) {

    $id = $_GET['id'];

    if (!ctype_digit($id)) {
        http_response_code(400);
        header("Content-Type: application/json");
        echo json_encode([
            "error" => "Invalid ID"
        ]);
        exit;
    }


    $stmt = $db->prepare(
        "SELECT Name, FILE_PATH, FILE_TYPE 
         FROM Documents 
         WHERE Number = :id"
    );


    $stmt->bindValue(
        ":id",
        intval($id),
        SQLITE3_INTEGER
    );


    $result = $stmt->execute();

    $file = $result->fetchArray(SQLITE3_ASSOC);


    if (!$file) {

        http_response_code(404);
        header("Content-Type: application/json");
        echo json_encode([
            "error"=>"Document not found"
        ]);

        exit;
    }

    header("Content-Type: application/json");
    echo json_encode($file);

    exit;
}




$result = $db->query(
    "SELECT Number, Name, Description, FILE_PATH
     FROM Documents
     ORDER BY Name"
);


$data=[];


while($row=$result->fetchArray(SQLITE3_ASSOC)){

    $data[]=$row;

}



header("Content-Type: application/json");

echo json_encode($data);


$db->close();

?>