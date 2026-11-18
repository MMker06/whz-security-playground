<?php
/**
 * 자동 채점 API
 * POST: main_uid, challenge(sqli|xss|cookie), token
 * shop/board에서 성공 감지 시 호출 → 점수 지급 + level_clear 업데이트
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}

$main_uid  = (int)($_POST['main_uid']  ?? 0);
$challenge = trim($_POST['challenge']  ?? '');
$token     = trim($_POST['token']      ?? '');

if (!$main_uid || !$challenge || !$token) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Missing params']);
    exit;
}

if (!verify_challenge_token($main_uid, $challenge, $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Invalid token']);
    exit;
}

$conn = db_connect();
init_progress_table($conn);

$challengeMap = [
    'sqli'   => ['stage_key' => 'lab_1', 'level_col' => 'level1_clear', 'points' => 100],
    'xss'    => ['stage_key' => 'lab_2', 'level_col' => 'level2_clear', 'points' => 100],
    'cookie' => ['stage_key' => 'lab_3', 'level_col' => 'level3_clear', 'points' => 100],
];

if (!isset($challengeMap[$challenge])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Unknown challenge']);
    exit;
}

$map      = $challengeMap[$challenge];
$stageKey = $map['stage_key'];
$levelCol = $map['level_col'];
$points   = $map['points'];

/* 이미 해결했는지 확인 */
$checkStmt = mysqli_prepare($conn, "SELECT user_id FROM user_progress WHERE user_id = ? AND stage_key = ?");
mysqli_stmt_bind_param($checkStmt, 'is', $main_uid, $stageKey);
mysqli_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);
$alreadySolved = (mysqli_stmt_num_rows($checkStmt) > 0);
mysqli_stmt_close($checkStmt);

if ($alreadySolved) {
    mysqli_close($conn);
    echo json_encode(['ok' => true, 'msg' => 'Already solved', 'already' => true]);
    exit;
}

/* 진행 상황 기록 + 점수 지급 */
mark_progress($conn, $main_uid, $stageKey);

$upd = mysqli_prepare($conn, "UPDATE userTBL SET `$levelCol` = 1, score = score + ? WHERE id = ?");
mysqli_stmt_bind_param($upd, 'ii', $points, $main_uid);
mysqli_execute($upd);
mysqli_stmt_close($upd);

/* 이론 단계 점수도 아직 안 받았으면 함께 지급 (이론은 필수 선행이지만 혹시 누락 방지) */

mysqli_close($conn);

echo json_encode([
    'ok'     => true,
    'msg'    => "Challenge '$challenge' solved!",
    'points' => $points,
]);
