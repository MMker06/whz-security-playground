<?php
session_start();
$con = mysqli_connect('db_board', 'web', 'web', 'whz') or die("DB 연결 실패");

/* 컬럼이 없을 경우 자동 추가 (초기 마이그레이션) */
mysqli_query($con, "ALTER TABLE boardTBL ADD COLUMN IF NOT EXISTS main_uid INT DEFAULT NULL");
mysqli_query($con, "ALTER TABLE boardTBL ADD COLUMN IF NOT EXISTS solve_token VARCHAR(64) DEFAULT NULL");

$username   = $_POST['username']  ?? '';
$title      = $_POST['title']     ?? '';
$content    = $_POST['content']   ?? '';
$date       = date('Y-m-d H:i:s');

/* 세션/POST에서 챌린지 파라미터 가져오기 */
$main_uid   = (int)(   $_POST['main_uid']  ?? $_SESSION['main_uid']  ?? 0);
$token      = trim(    $_POST['token']     ?? $_SESSION['token']     ?? '');

if (!$username || !$title || !$content) {
    echo "<script>alert('제목과 내용을 입력해주세요.'); history.back();</script>";
    exit;
}

$ins = mysqli_prepare($con, "INSERT INTO boardTBL (username, title, content, date, views, main_uid, solve_token) VALUES (?, ?, ?, ?, 0, ?, ?)");
mysqli_stmt_bind_param($ins, 'ssssis', $username, $title, $content, $date, $main_uid, $token);

if (mysqli_execute($ins)) {
    $new_board_id = mysqli_insert_id($con);
    mysqli_stmt_close($ins);
    mysqli_close($con);
    echo "<script>
        alert('등록 성공! 게시글을 확인하고 \"관리자에게 신고\" 버튼을 눌러 XSS를 실행하세요.');
        window.location.href = 'view.php?board_id={$new_board_id}';
    </script>";
} else {
    mysqli_stmt_close($ins);
    mysqli_close($con);
    echo "<script>alert('등록 실패'); history.back();</script>";
}
