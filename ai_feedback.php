<?php
// API設定を読み込む
require_once __DIR__ . '/../../config/MoodPalette/api_config.php';

// DB接続
include("funcs.php");
$pdo = db_conn();

// 今日から30日前までの日付配列を作成
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $dates[] = $d;
}

// データベースから直近7日分のデータを取得
$sql = "SELECT log_date, mood_score, memo FROM mp_db 
        WHERE log_date >= :start_date 
        ORDER BY log_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':start_date', $dates[0], PDO::PARAM_STR);
$stmt->execute();

// 日付をキーにした連想配列を作成
$byDate = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $byDate[$row['log_date']] = [
        'mood' => $row['mood_score'],
        'memo' => $row['memo'],
    ];
}

// 直近7日分を、記録がない日も含めて並べる
$lines = [];
foreach($dates as $d) {
    if(isset($byDate[$d])){
        $mood = $byDate[$d]['mood'];
        $memo = $byDate[$d]['memo'];
    } else {
        $mood = '-';
        $memo = '記録なし';
    }
    $md = date('m/d', strtotime($d));
    $lines[] = "{$md} 気分:{$mood} メモ:{$memo}";
}

$logText = implode("\n", $lines);

//ログがまったくない場合
if (trim($logText) === '') {
    $feedbackText = '直近7日分の記録がないため、フィードバックは生成できません。';
} else {
    $prompt = <<<EOT
$prompt = <<<EOT
あなたは、日本語で回答するカウンセラー兼健康アドバイザーです。
以下は、ある人の「直近7日」の気分スコア（1〜5）とメモの記録です。
気分が '-' やメモが「記録なし」となっている日は、その日に記録がつけられていないことを意味します。

{$logText}

この情報から、次の2点についてフィードバックしてください。

1. この7日間の心と体の様子の特徴（良かった点を中心に）を3〜4行程度で。
2. 今後1週間のための、具体的で無理のないセルフケア提案を3つ、各1~2行程度で。

ただし、次の点を必ず守ってください。

- 「***」「###」などの記号や見出し記号は使わない。
- 「1. この7日間の〜」のような見出しも書かない。
- 1と2の間は改行1つだけにする。
- 箇条書きは「1. 」「2. 」「3. 」のようなシンプルな番号だけを使う。
- 全体のトーンは、相手をねぎらい、励ます「ポジティブ寄り」にする。
- 問題点や心配な点に触れる場合も、責めずに「こうするともっと良くなりそう」という前向きな表現にする。
- 専門用語はできるだけ避け、中学生にも分かるやさしい日本語で書く。
- 全体で250〜350文字程度におさめる。

出力形式の例：
この一週間、〜〜（3〜4行の特徴）

1. 〜〜（セルフケア提案1）
2. 〜〜（セルフケア提案2）
3. 〜〜（セルフケア提案3）

EOT;

    // Gemini API を呼び出す
    $apiKey = GEMINI_API_KEY;
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . urlencode($apiKey);

    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt],
                ],
            ],
        ],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $feedbackText = 'AIフィードバックの取得中にエラーが発生しました: ' . curl_error($ch);
    } else {
        $data = json_decode($response, true);
        $feedbackText = $data['candidates'][0]['content']['parts'][0]['text']
            ?? 'AIからのフィードバックを読み取れませんでした。';
    }

    curl_close($ch);
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" href="imgs/favicon.png">
<title>AIフィードバック（直近7日） | MoodPalette</title>

<style>
body{
    font-family: Meiryo, sans-serif;
    margin: 16px;
    background: #f5f5f5;
}
h1{
    font-size: 20px;
    text-align: center;
    margin-bottom: 16px;
}
.feedback-box{
    max-width: 700px;
    margin: 0 auto;
    background: #ffffff;
    padding: 16px 20px;
    border-radius: 6px;
    border: 1px solid #ddd;
    line-height: 1.7;
    text-align: left;
    white-space: pre-wrap;
}
.back-link{
    text-align: center;
    margin-top: 16px;
}
.back-link a{
    text-decoration: none;
    color: #333;
}
</style>
</head>

<body>
<h1>直近7日分のAIフィードバック</h1>

<div class="feedback-box">
    <?php echo nl2br(htmlspecialchars($feedbackText, ENT_QUOTES, 'UTF-8')); ?>
</div>

<p class="back-link">
    <a href="index.php">カレンダーに戻る</a>
</p>
</body>
</html>
