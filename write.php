<?php
//1. POSTデータ取得
$date = $_POST['date'] ?? '';
$mood = $_POST['mood'] ?? '';
$memo = $_POST['memo'] ?? '';

if($date === '' || $mood === ''){
    exit('日付または気分が入力されていません');
}

//2. DB接続
include("funcs.php");
$pdo = db_conn();

//3. その日付のデータが既に存在するかチェック
$check_sql = "SELECT id FROM mp_db WHERE log_date = :log_date";
$check_stmt = $pdo->prepare($check_sql);
$check_stmt->bindValue(':log_date', $date, PDO::PARAM_STR);
$check_stmt->execute();
$existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

//4. 存在すればUPDATE、なければINSERT
if($existing){
    // 更新
    $sql = "UPDATE mp_db SET mood_score=:mood_score, memo=:memo WHERE log_date=:log_date";
} else {
    // 新規登録
    $sql = "INSERT INTO mp_db(log_date, mood_score, memo) VALUES(:log_date, :mood_score, :memo)";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':log_date', $date, PDO::PARAM_STR);
$stmt->bindValue(':mood_score', $mood, PDO::PARAM_INT);
$stmt->bindValue(':memo', $memo, PDO::PARAM_STR);
$status = $stmt->execute();

//5. 処理後
if($status==false){
    sql_error($stmt);
}else{
    redirect("index.php");
}
?>
