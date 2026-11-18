<?php
$_host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : 'localhost';
define('MAIN_URL',  'http://' . $_host);
define('SHOP_URL',  'http://' . $_host . ':8001');
define('BOARD_URL', 'http://' . $_host . ':8002');
define('CHALLENGE_SECRET', getenv('CHALLENGE_SECRET') ?: 'whz_local_dev_secret_change_me');

function db_connect() {
    $conn = mysqli_connect('db_main', 'web', 'web', 'whz');
    if (!$conn) die("DB 연결 실패");
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

function make_challenge_token($main_uid, $challenge) {
    return hash_hmac('sha256', (string)$main_uid . ':' . $challenge, CHALLENGE_SECRET);
}

function verify_challenge_token($main_uid, $challenge, $token) {
    if (empty($token)) return false;
    return hash_equals(make_challenge_token($main_uid, $challenge), $token);
}

function init_progress_table($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_progress (
        user_id INT NOT NULL,
        stage_key VARCHAR(50) NOT NULL,
        completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(user_id, stage_key)
    )");
}

function get_user_progress($conn, $user_id) {
    $stmt = mysqli_prepare($conn, "SELECT stage_key FROM user_progress WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $progress = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $progress[$row['stage_key']] = true;
    }
    mysqli_stmt_close($stmt);
    return $progress;
}

function mark_progress($conn, $user_id, $stage_key) {
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO user_progress (user_id, stage_key) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, 'is', $user_id, $stage_key);
    mysqli_execute($stmt);
    mysqli_stmt_close($stmt);
}

/* ─── 카테고리·스테이지 정의 ─── */
$CATEGORIES = [
    1 => [
        'name'        => 'SQL Injection',
        'icon'        => '💉',
        'color'       => 'danger',
        'difficulty'  => 'Medium',
        'description' => '데이터베이스 쿼리에 악의적인 SQL 코드를 삽입하여 인증을 우회하거나 데이터를 탈취하는 공격 기법입니다.',
        'stages'      => [
            ['key' => 'theory_1', 'type' => 'theory', 'title' => 'SQL Injection 이론 학습',          'points' => 10],
            ['key' => 'lab_1',    'type' => 'lab',    'title' => '실습: 로그인 우회 (Login Bypass)',  'points' => 100, 'challenge' => 'sqli'],
        ],
    ],
    2 => [
        'name'        => 'Stored XSS',
        'icon'        => '⚡',
        'color'       => 'warning',
        'difficulty'  => 'Medium',
        'description' => '악성 스크립트를 서버에 저장시켜 다른 사용자가 페이지를 방문할 때 스크립트가 실행되도록 하는 공격 기법입니다.',
        'stages'      => [
            ['key' => 'theory_2', 'type' => 'theory', 'title' => 'Stored XSS 이론 학습',             'points' => 10],
            ['key' => 'lab_2',    'type' => 'lab',    'title' => '실습: 게시판 XSS 공격',             'points' => 100, 'challenge' => 'xss'],
        ],
    ],
    3 => [
        'name'        => '쿠키 변조',
        'icon'        => '🍪',
        'color'       => 'info',
        'difficulty'  => 'Easy',
        'description' => '브라우저의 쿠키 값을 조작하여 다른 사용자(관리자) 권한으로 세션을 탈취하는 공격 기법입니다.',
        'stages'      => [
            ['key' => 'theory_3', 'type' => 'theory', 'title' => '쿠키 변조 이론 학습',              'points' => 10],
            ['key' => 'lab_3',    'type' => 'lab',    'title' => '실습: 쿠키 변조로 권한 상승',        'points' => 100, 'challenge' => 'cookie'],
        ],
    ],
];
