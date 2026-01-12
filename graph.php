<?php
// graph.php : 指定月の気分ログを折れ線グラフで表示

// DB接続
include("funcs.php");
$pdo = db_conn();

// URLパラメータから年月を取得（なければ今月）
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

// その月の日数を取得
$daysInMonth = (int)date('t', strtotime("{$year}-{$month}-01"));

// その月の全日付配列を作成
$dates = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $dates[] = sprintf('%04d-%02d-%02d', $year, $month, $day);
}

// データベースからその月のデータを取得
$sql = "SELECT log_date, mood_score FROM mp_db 
        WHERE YEAR(log_date) = :year AND MONTH(log_date) = :month 
        ORDER BY log_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':year', $year, PDO::PARAM_INT);
$stmt->bindValue(':month', $month, PDO::PARAM_INT);
$status = $stmt->execute();

// 日付をキーにした連想配列を作成
$dataByDate = [];
if($status == false){
    sql_error($stmt);
} else {
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($result as $row){
        $dataByDate[$row['log_date']] = (int)$row['mood_score'];
    }
}

// その月の全日分のデータを作成
$logs = [];
$pointColors = [];  

foreach($dates as $date){
    $displayDate = date('m/d', strtotime($date));
    
    if(isset($dataByDate[$date])) {
        $logs[] = [
            'date' => $displayDate,
            'mood' => $dataByDate[$date],
        ];
        $pointColors[] = 'rgb(75, 192, 192)';  // 記録あり：緑
    } else {
        $logs[] = [
            'date' => $displayDate,
            'mood' => 0,
        ];
        $pointColors[] = 'rgb(200, 200, 200)';  // 記録なし：グレー
    }
}

// Chart.js に渡すために PHP配列をJSONに変換
$jsLogs = json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsColors = json_encode($pointColors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


<?php
// graph.php : 指定月の気分ログを折れ線グラフで表示

// DB接続
include("funcs.php");
$pdo = db_conn();

// URLパラメータから年月を取得（なければ今月）
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

// その月の日数を取得
$daysInMonth = (int)date('t', strtotime("{$year}-{$month}-01"));

// その月の全日付配列を作成
$dates = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $dates[] = sprintf('%04d-%02d-%02d', $year, $month, $day);
}

// データベースからその月のデータを取得
$sql = "SELECT log_date, mood_score FROM mp_db 
        WHERE YEAR(log_date) = :year AND MONTH(log_date) = :month 
        ORDER BY log_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':year', $year, PDO::PARAM_INT);
$stmt->bindValue(':month', $month, PDO::PARAM_INT);
$status = $stmt->execute();

// 日付をキーにした連想配列を作成
$dataByDate = [];
if($status == false){
    sql_error($stmt);
} else {
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($result as $row){
        $dataByDate[$row['log_date']] = (int)$row['mood_score'];
    }
}

// その月の全日分のデータを作成
$logs = [];
$pointColors = [];  

foreach($dates as $date){
    $displayDate = date('m/d', strtotime($date));
    
    if(isset($dataByDate[$date])) {
        $logs[] = [
            'date' => $displayDate,
            'mood' => $dataByDate[$date],
        ];
        $pointColors[] = 'rgb(75, 192, 192)';  // 記録あり：緑
    } else {
        $logs[] = [
            'date' => $displayDate,
            'mood' => 0,
        ];
        $pointColors[] = 'rgb(200, 200, 200)';  // 記録なし：グレー
    }
}

// Chart.js に渡すために PHP配列をJSONに変換
$jsLogs = json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsColors = json_encode($pointColors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>気分ロググラフ（直近30日）</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Chart.js 読み込み[chart:191] -->
    <style>
        body{
            font-family: Meiryo, sans-serif;
            margin: 0;
            padding: 16px;
            background-color: #fff;
        }
        h1{
            font-size: 20px;
            text-align: center;
            margin-bottom: 16px;
        }
        .chart-container{
            max-width: 700px;
            margin: 0 auto;
        }
        .back-link{
            text-align: center;
            margin-top: 16px;
        }
        .back-link a{
            text-decoration: none;
            color: #0073aa;
        }
    </style>
</head>
<body>
    <h1>気分ロググラフ（直近30日）</h1>

    <div class="chart-container">
        <canvas id="moodChart"></canvas>
    </div>

    <div class="back-link">
        <a href="index.php">カレンダーに戻る</a>
    </div>

    <script>
        // PHPから渡された直近30日分のログ
        const logs = <?php echo $jsLogs ?: '[]'; ?>;

        // 日付と気分スコアの配列を作成
        const labels = logs.map(item => item.date);
        const data = logs.map(item => item.mood);

        const ctx = document.getElementById('moodChart').getContext('2d');

        const moodChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,  // x軸: 日付
                datasets: [{
                    label: '気分スコア',
                    data: data,    // y軸: mood(1〜5)
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: true,
                    }
                },
                scales: {
                    y: {
                        suggestedMin: 1,
                        suggestedMax: 5,
                        ticks: {
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: '気分スコア'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: '日付'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
