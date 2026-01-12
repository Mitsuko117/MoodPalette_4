<?php
// データベースから読み込んで一覧表示
include("funcs.php");
$pdo = db_conn();  

$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');   
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');   

// 前月・翌月の年・月を計算
$prevYear = $year;
$prevMonth = $month -1;
if($prevMonth <1){
    $prevMonth=12;
    $prevYear--;
}

$nextYear = $year;
$nextMonth = $month +1;
if($nextMonth >12){
    $nextMonth = 1;
    $nextYear++;
}

// データベースからその月のデータを取得
$sql = "SELECT log_date, mood_score, memo FROM mp_db 
        WHERE YEAR(log_date) = :year AND MONTH(log_date) = :month 
        ORDER BY log_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':year', $year, PDO::PARAM_INT);  
$stmt->bindValue(':month', $month, PDO::PARAM_INT); 
$stmt->execute();

// データベースから取得した結果を連想配列に格納
$dataByDate = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dataByDate[$row['log_date']] = [
        'mood' => $row['mood_score'],
        'memo' => $row['memo']
    ];
}

// その月の日数を取得
$daysInMonth = (int)date('t', strtotime("{$year}-{$month}-01"));

// グラフ用：その月の全日分のデータを作成
$chartDates = [];
$chartScores = [];
$pointColors = [];  // ★追加：ポイントの色配列

for ($day = 1; $day <= $daysInMonth; $day++) {
    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $displayDate = sprintf('%02d/%02d', $month, $day);
    
    $chartDates[] = $displayDate;
    
    if(isset($dataByDate[$dateStr])) {
        $chartScores[] = (int)$dataByDate[$dateStr]['mood'];
        $pointColors[] = 'rgb(75, 192, 192)';  // 記録あり：緑
    } else {
        $chartScores[] = 0;
        $pointColors[] = 'rgb(200, 200, 200)';  // 記録なし：グレー
    }
}

// テーブル表示用：記録がある日だけ
$filtered = [];
foreach ($dataByDate as $date => $data) {
    $filtered[] = [$date, $data['mood'], $data['memo']];
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="imgs/favicon.png">
    <title>MoodPalette</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
    font-size:18px;
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
    font-size: 18px;
    text-align: center;
    margin-top: 16px;
}

a {
    text-decoration: none;
}

.month-nav a {
  color: inherit;
  text-decoration: none;
}

.recent-logs-table {
  margin: 0 auto;
}

.recent-logs-table th.mood,
.recent-logs-table td.mood {
    text-align: center;
}

/* 折れ線グラフ */
.chart-container {
    max-width: 700px;
    margin: 20px auto;
    padding: 0 20px;
}

.chart-wrapper {
    position: relative;
    height: 300px;
}

</style>
</head>
<body>
    <h1><img src="./imgs/H1_2.png" alt=""></h1>

    <div class="month-nav"  style="text-align: center; margin-bottom:8px; font-weight:bold;">
        <a href="?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>">◀</a>
        <span><?php echo $year . '.' . sprintf('%02d', $month); ?></span>
        <a href="?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>">▶</a>
    </div>

    <?php if(count($filtered) > 0): ?>
    <!-- グラフ表示エリア -->
    <div class="chart-container">
        <div class="chart-wrapper">
            <canvas id="moodChart"></canvas>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('moodChart');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartDates); ?>,
                datasets: [{
                    label: '気分スコア',
                    data: <?php echo json_encode($chartScores); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: <?php echo json_encode($pointColors); ?>,
                    spanGaps: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                if (value === 0) {
                                    return '記録なし';
                                }
                                return '気分スコア: ' + value;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                if (value === 0) return '未記録';
                                return value;
                            }
                        },
                        title: { display: true, text: '気分スコア（1〜5）' }
                    },
                    x: {
                        type: 'category',
                        title: { display: true, text: '日付' },
                        ticks: {
                            autoSkip: true,
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>

    <?php if(count($filtered) === 0): ?>
        <p>この月の記録はまだありません</p>
    <?php else: ?>
    <table class="recent-logs-table" border="1" cellpadding="4" cellspacing="0">
    <tr>
        <th>DATE</th>
        <th class="mood">MOOD</th>
        <th>MEMO</th>
    </tr>
    <?php foreach ($filtered as $row): ?>
        <tr>
        <td><?php echo htmlspecialchars($row[0] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>

        <td class="mood">
        <?php
        $mood = $row[1] ?? '';
        if ($mood !== '' && ctype_digit((string)$mood)) {
        $moodInt = (int)$mood;
        if ($moodInt >= 1 && $moodInt <= 5) {
        $iconPath = './imgs/' . $moodInt . '.png';
            echo '<img src="' . htmlspecialchars($iconPath, ENT_QUOTES, 'UTF-8') . '" alt="" width="24" height="24">';
        }
        }
        ?>
        </td>

        <td><?php echo nl2br(htmlspecialchars($row[2] ?? '', ENT_QUOTES, 'UTF-8')); ?></td>
        </tr>
    <?php endforeach; ?>
    </table>

    <?php endif; ?>
    <p><a href="index.php">入力画面に戻る</a></p>

</body>
</html>
