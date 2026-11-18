<?php
/**
 * 이론 학습 완료 처리 (category.php의 POST form에서 호출)
 * stage_key, user_id 기반으로 progress 기록 + 점수 지급
 */
require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$conn     = db_connect();
init_progress_table($conn);

$loggedIn = isset($_COOKIE['LoginUser']);
if (!$loggedIn) { header('Location: ../login.html'); exit; }

$userId   = (int)$_COOKIE['LoginUser'];
$stageKey = trim($_POST['stage_key'] ?? '');
$catId    = (int)($_POST['cat_id'] ?? 0);
$stageNum = (int)($_POST['stage_num'] ?? 1);

/* 유효한 theory key인지 확인 */
global $CATEGORIES;
$validKeys = ['theory_1', 'theory_2', 'theory_3'];
$pointsMap = ['theory_1' => 10, 'theory_2' => 10, 'theory_3' => 10];

if (!in_array($stageKey, $validKeys) || !isset($pointsMap[$stageKey])) {
    header('Location: ../index.php');
    exit;
}

/* 이미 완료했는지 확인 후 점수 지급 */
$check = mysqli_prepare($conn, "SELECT user_id FROM user_progress WHERE user_id = ? AND stage_key = ?");
mysqli_stmt_bind_param($check, 'is', $userId, $stageKey);
mysqli_execute($check);
mysqli_stmt_store_result($check);
$alreadyDone = (mysqli_stmt_num_rows($check) > 0);
mysqli_stmt_close($check);

if (!$alreadyDone) {
    mark_progress($conn, $userId, $stageKey);
    $pts  = $pointsMap[$stageKey];
    $upd  = mysqli_prepare($conn, "UPDATE userTBL SET score = score + ? WHERE id = ?");
    mysqli_stmt_bind_param($upd, 'ii', $pts, $userId);
    mysqli_execute($upd);
    mysqli_stmt_close($upd);
}

mysqli_close($conn);
header('Location: ../category.php?id=' . $catId . '&stage=' . ($stageNum + 1));
exit;
