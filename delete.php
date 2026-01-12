<?php
// GETで日付を受け取る
$date = $_GET['date'] ?? '';
if($date === ''){
    exit('日付が指定されていません');
}

// DB接続します
include("funcs.php");
$pdo = db_conn();

// データ削除SQL作成
$stmt = $pdo->prepare("DELETE FROM mp_db WHERE log_date = :log_date");
$stmt->bindValue(':log_date', $date, PDO::PARAM_STR);
$status = $stmt->execute();

// データ削除処理後
if($status == false){
    sql_error($stmt);
} else {
    redirect("index.php");
}
?>
