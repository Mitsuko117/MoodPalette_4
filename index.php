<?php
// データベースから読み込むように変更
include("funcs.php");
$pdo = db_conn();  

//URLパラメータから表示する年・月を決める（なければ今日）
$year  = isset($_GET['year']) ?(int)$_GET['year']:(int)date('Y');
$month = isset($_GET['month']) ?(int)$_GET['month']: date('n'); 
//isset この変数、ちゃんと入ってる？NULLじゃない？と尋ねる関数

// 前月・翌月の年・月を計算
$prevYear = $year;
$prevMonth = $month -1;
$nextYear = $year;
$nextMonth = $month +1;

if($prevMonth <1){
    $prevMonth=12;
    $prevYear--;
}
if($nextMonth >12){
    $nextMonth = 1;
    $nextYear++;
}

// その月の1日が何曜日か（0:日〜6:土）
$firstDayOfMonth = date('w', strtotime("$year-$month-01"));

// 月曜始まり用に変換（0:月〜6:日）
$startIndex = ($firstDayOfMonth + 6) % 7;

// その月の日数
$daysInMonth = date('t', strtotime("$year-$month-01"));

// データベースから全データを取得してカレンダー用配列を作る
$calendarData = [];

$sql = "SELECT log_date, mood_score, memo FROM mp_db ORDER BY log_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $date = $row['log_date'];
    $calendarData[$date] = [
        'mood' => $row['mood_score'],
        'memo' => $row['memo']
    ];
}
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <!-- <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="imgs/favicon.png"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoodPalette</title>

<style> 
/* デザインの設定 */

body{
    font-size:20px;
    font-family: Meiryo;
    margin: 0;
    padding: 0;
    background-color: #fff;
}

h1{
    font-size: 20px;
    text-align: center;
    margin:16px 20px;
}

h1 img{
    width: 400px;
    height:auto;
}

p {
    font-size:18px;
    text-align: center;
    margin: 16px;
}

a {
    text-decoration: none;
    padding: 42px;
}

.calendar{
/* カレンダーの「マス」を並べる箱 */
    display: grid;
    grid-template-columns: repeat(7,1fr); /*7列（曜日分）にする*/
    gap: 5px; /*マス同士の隙間*/
    max-width: 550px; /*幅の上限*/
    margin: 0 auto 20px auto;  /* 上はh1で余白をとるので、下だけ20px */
    padding:0 8px;  /* 画面端にくっつきすぎないよう左右に少し余白 */
}

.month-nav a {
  color: inherit;          /* 親と同じ色（＝周りの文字と同じ色） */
  text-decoration: none;   /* 下線を消す */
}

.day img{
    width: 20px;
    height: 20px;
    object-fit: contain;
    display:block;
}

.day{
/* 1日分のマスの見た目 */
    border:1px solid #ccc;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    min-height:60px;
    font-size:12px;
    box-sizing:border-box;/* 枠線・余白を含めてサイズ計算 */
    width: 100%;
    aspect-ratio:1/1;
    padding:4px;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:flex-start;
}

.day.has-data{
    background-color: #dff7d0;
}

.day-number{
    font-weight:bold;
    margin-bottom:4px;
}

.memo {
    font-size: 18px;   
    line-height: 1.5;    
}

/* 曜日ヘッダ用 */
.weekday-header{
    text-align:center;
    font-size:11px;
    font-weight:bold;
    padding-top:4px;
}

/* ポップアップの背景 */
.modal-backdrop{
    position:fixed;
    inset: 0; /* 画面全体を覆う */
    background: rgba(0,0,0,0.4); /* 半透明の黒 */
    display: none;  /* 最初は非表示、JSから display:flex にする */
    align-items: center;  /* 中身を上下中央に */
    justify-content: center; /* 左右中央に */
    z-index:1000; /* ほかの要素より前に出す */
}

/* ポップアップ本体 */
.modal{
    background: white;
    padding: 16px 16px 20px;
    border-radius:4px;
    width: 260px; 
    height:300px;
    box-sizing: border-box;  /*https://zero-plus.io/media/box-sizing/*/
}

.modal h3{
    margin-top:0;
    font-size: 14px;
}

.modal label{
    font-size: 12px;
}

.modal button{
    font-size: 12px;
    margin-top: 8px; 
}

.mood-tabs{
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
    justify-content: center;
}

.mood-tab{
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: #fff;
    cursor: pointer;
    font-size: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.mood-tab:hover{
    transform:scale(1.1);
    transition: transform 0.1s;
}

.mood-tab-img{
    width: 24px;
    height: 24px;
    object-fit: contain;
    display:block;
}
.mood-tab.selected{
    transform: scale(1.5);
}

.mood-tab.selected{
    border: 1.5px solid #c1ff72;
    background: #fff;
}

.recent-logs-wrapper{
    text-align: center;
    margin-top: 16px;
}

.recent-logs-table{
    margin: 0 auto;
}

/* レスポンシブ対応 */
@media(max-width:480px){
h1{
    font-size: 18px;
    margin: 12px 0;
}

.calendar{
    max-width: 100%;
    padding: 0 4px;
}

.day{
    min-height: 40px;
    padding: 6px;
    font-size: 10px;
}

.modal{
    width: 90%;
    padding: 12px;
}

.modal h3{
    font-size: 13px;
}

.modal label,
.modal button{
    font-size: 11px;
}
}
</style>
</head>

<body>
<h1><img src="./imgs/H1.png" alt=""></h1>

<div class="month-nav"  style="text-align: center; margin-bottom:8px; font-weight:bold;">
<a href="?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>">◀</a>
    <span><?php echo $year . '.' . sprintf('%02d', $month); ?></span>
    <a href="?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>">▶</a>
</div>

<!-- 曜日ヘッダー -->
<div class="calendar">
    <div class="weekday-header">月</div>
    <div class="weekday-header">火</div>
    <div class="weekday-header">水</div>
    <div class="weekday-header">木</div>
    <div class="weekday-header">金</div>
    <div class="weekday-header">土</div>
    <div class="weekday-header">日</div>

<?php
// 1日より前の空マス（月曜始まり用）
for ($i = 0; $i < $startIndex; $i++) {
    echo '<div class="day"></div>';
}

// 日付マス（1～月末）
for ($day = 1; $day <= $daysInMonth; $day++) {
    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day); // YYYY-MM-DD

    // この日付に記録があれば取得
    $mood = $calendarData[$dateStr]['mood'] ?? '';
    $memo = $calendarData[$dateStr]['memo'] ?? '';

    // メモは data-memo に入れておく（htmlspecialcharsでエスケープ）
    $memoAttr = htmlspecialchars($memo, ENT_QUOTES, 'UTF-8');

    // 日付マスの外枠（data-date と data-memo を1回だけ出す）
    echo '<div class="day" data-date="' . $dateStr . '" data-mood="' . htmlspecialchars($mood, ENT_QUOTES, 'UTF-8') . '" data-memo="' . $memoAttr . '">';

    // 日付の数字
    echo '<div class="day-number">' . $day . '</div>';

    // この日付に記録があれば、mood画像と📝を表示
    if ($mood !== '' && ctype_digit((string)$mood)) {
        $moodInt = (int)$mood;
        if ($moodInt >= 1 && $moodInt <= 5) {
            $iconPath = './imgs/' . $moodInt . '.png';
            echo '<div><img src="' . htmlspecialchars($iconPath, ENT_QUOTES, 'UTF-8') . '" alt="" width="24" height="24"></div>';
        }
    }

    if (trim($memo) !== '') {
        echo '<div class="memo">📝</div>';
    }

    echo '</div>'; // .day を閉じる
}
?>
</div>

<!-- 下のボタン -->
<p>
    <a href="read.php?year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="btn-link">この月の記録を見る</a>
    <a href="ai_feedback.php" class="btn-link ai-btn">AIフィードバック</a>
</p>


<!-- モーダル -->
<div id="modalbackdrop" class="modal-backdrop">
    <div class="modal">
        <h3 id="modaldatetext"></h3>
    
<!-- 気分タブ -->
<div class="mood-tabs">
    <button type="button" class="mood-tab" data-score="1"><img src="./imgs/1.png" alt="" class="mood-tab-img"></button>
    <button type="button" class="mood-tab" data-score="2"><img src="./imgs/2.png" alt="" class="mood-tab-img"></button>
    <button type="button" class="mood-tab" data-score="3"><img src="./imgs/3.png" alt="" class="mood-tab-img"></button>
    <button type="button" class="mood-tab" data-score="4"><img src="./imgs/4.png" alt="" class="mood-tab-img"></button>
    <button type="button" class="mood-tab" data-score="5"><img src="./imgs/5.png" alt="" class="mood-tab-img"></button>
</div>

<!-- 後でwrite.phpに送るためのフォーム -->
<form id="moodform" action="write.php" method="post">
<!-- 日付（クリックした日付が入る） -->
<input type="hidden" name="date" id="form-date">
<!-- 選ばれたスコア -->
<input type="hidden" name="mood" id="form-mood">
<!-- メモ -->
<label>
memo <br>
<textarea name="memo" id="moodnote" rows="5" style="width:100%;"></textarea>
</label>
</form>

<br>
<button id="savemoodbtn">保存</button>
<button id="deletemoodbtn">削除</button>
<button id="closemoodbtn">閉じる</button>
</div>
</div>
</div>

<!-- jQuery が必要なら読み込み（なければ通常のJSでも可） -->
    <script src="jquery-2.1.3.min.js"></script>
    <script src="app.js?v=2"></script>
<!-- app.jsのみでは昔のものを読み込んだため、v2として読み込ませている。 -->
</body>
</html>