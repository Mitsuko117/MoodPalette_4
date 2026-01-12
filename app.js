$(function () {
// 日付マスをクリックしたとき
$('.day').on('click', function () {
  const date = $(this).data('date'); // YYYY-MM-DD
  if (!date) {
    return; // 空マス（月初の前の空白）は無視
  }

//この日付の気持ち、メモを取得
const memo = $(this).data('memo');
const mood = $(this).data('mood');

// モーダルに日付表示
$('#modaldatetext').text(date);

// hidden に日付をセット（write.php に送る用）
$('#form-date').val(date);

// 以前の選択をリセット
$('#form-mood').val('');
 $('.mood-tab').removeClass('selected');

 // ★ 保存済みのmoodがあれば、そのアイコンを選択状態にする
if (mood && String(mood).match(/^[1-5]$/)) {
  $('#form-mood').val(mood); // hiddenにも反映
  $('.mood-tab').each(function () {
    const score = $(this).data('score');
    if (String(score) === String(mood)) {
      $(this).addClass('selected');
    }
  });
}

//メモ欄には保存済みのメモを表示
$('#moodnote').val(memo);

// モーダル表示
$('#modalbackdrop').css('display', 'flex');
});

// 気分アイコンをクリックしたとき
$('.mood-tab').on('click', function () {
  const score = $(this).data('score');
  $('#form-mood').val(score);
  $('.mood-tab').removeClass('selected');
  $(this).addClass('selected');
});

// 保存ボタン：write.php にPOST（気分チェックなし版）
$('#savemoodbtn').on('click', function () {
  $('#moodform').submit();
});

//削除ボタン
$('#deletemoodbtn').on('click',function(){
  const date = $('#form-date').val(); //モーダルで開いている日付
  if(!date){
    alert('削除する日付が取得できませんでした。');
    return;
  }

  const ok = confirm(date + 'の記録を削除しますか？');
  if(!ok){
    return;
  }

//delete.phpにGETで日付を渡す
window.location.href = 'delete.php?date=' + encodeURIComponent(date);
});

// 閉じるボタン
$('#closemoodbtn').on('click', function () {
  $('#modalbackdrop').css('display', 'none');
});

// 背景（黒い部分）クリックで閉じる
$('#modalbackdrop').on('click', function (event) {
  if (event.target.id === 'modalbackdrop') {
    $('#modalbackdrop').css('display', 'none');
  }
});
});
