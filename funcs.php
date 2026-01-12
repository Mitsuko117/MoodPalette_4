<?php

// config ディレクトリの設定ファイルを読み込む
require_once __DIR__ . '/../../config/MoodPalette/db_config.php';

//XSS対応（ echoする場所で使用！それ以外はNG ）
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES);
}


//DB接続関数：db_conn()
function db_conn(){
    try {
        return new PDO(
            'mysql:dbname=' . DB_NAME . ';charset=utf8;host=' . DB_HOST,
            DB_USER,      
            DB_PASS
        );
    } catch (PDOException $e) {
        exit('DB Connection Error:' . $e->getMessage());
    }
}

//SQLエラー関数：sql_error($stmt)
function sql_error($stmt){
    $error = $stmt->errorInfo();
    exit("SQLError:".$error[2]);
}

//リダイレクト関数: redirect($file_name)
function redirect($file_name){
    header("Location: ".$file_name);
    exit();
}

?>





