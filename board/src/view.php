<?php
session_start();
require_once 'config.php';
$con = mysqli_connect('db_board', 'web', 'web', 'whz');

$board_id = (int)($_GET['board_id'] ?? 0);

/* 조회수 증가 */
$upd = mysqli_prepare($con, "UPDATE boardTBL SET views=views+1 WHERE board_id=?");
mysqli_stmt_bind_param($upd, 'i', $board_id);
mysqli_execute($upd);
mysqli_stmt_close($upd);

/* 게시글 조회 */
$sel = mysqli_prepare($con, "SELECT title, content, date, views, username, main_uid, solve_token FROM boardTBL WHERE board_id=?");
mysqli_stmt_bind_param($sel, 'i', $board_id);
mysqli_execute($sel);
$rows = mysqli_fetch_assoc(mysqli_stmt_get_result($sel));
mysqli_stmt_close($sel);

/* 현재 세션의 챌린지 정보 */
$session_main_uid = (int)($_SESSION['main_uid'] ?? 0);
$session_token    = $_SESSION['token']    ?? '';

/* 게시글에 저장된 챌린지 정보 우선 사용 */
$main_uid  = (int)($rows['main_uid']    ?? $session_main_uid);
$token     = $rows['solve_token']       ?? $session_token;

/* 관리자 봇 신고 처리 */
$botResult = null;
if (isset($_GET['report']) && $_GET['report'] == '1' && $rows) {
    /* 게시글에 <script> 태그가 있는지 확인 */
    if ($main_uid && $token && preg_match('/<script[\s>]/i', $rows['content'])) {
        $expected = make_challenge_token($main_uid, 'xss');
        if (hash_equals($expected, $token)) {
            $ch = curl_init(MAIN_API_URL . '/api/solve.php');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'main_uid'  => $main_uid,
                    'challenge' => 'xss',
                    'token'     => $token,
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);
            $resp      = curl_exec($ch);
            curl_close($ch);
            $botResult = json_decode($resp, true);
        }
    }
}

/* 사용자 정보 */
$loggedIn = isset($_COOKIE['LoginUser']);
$curUsername = null;
if ($loggedIn) {
    $userId = (int)$_COOKIE['LoginUser'];
    $usr = mysqli_prepare($con, "SELECT username FROM userTBL WHERE id = ?");
    mysqli_stmt_bind_param($usr, 'i', $userId);
    mysqli_execute($usr);
    $ur = mysqli_fetch_assoc(mysqli_stmt_get_result($usr));
    mysqli_stmt_close($usr);
    $curUsername = $ur['username'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>게시글 보기</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .logo-img { max-width:125px; height:125px; display:block; margin:30px auto; }
    .solved-banner { background:linear-gradient(90deg,#198754,#20c997); color:#fff;
                     border-radius:12px; padding:20px 28px; text-align:center; }
  </style>
</head>
<body>

<nav class="container-fluid px-4 position-relative d-flex justify-content-between align-items-center" style="height:110px;">
  <div class="fw-bold">
    <a href="<?= MAIN_URL ?>/index.php" class="text-decoration-none text-dark fs-3">home</a>
  </div>
  <div class="position-absolute top-50 start-50 translate-middle">
    <img src="<?= MAIN_URL ?>/images/HoneyRain.png" alt="logo" class="logo-img">
  </div>
  <div class="text-end">
    <?php if ($loggedIn): ?>
      <span class="me-2 small fw-medium fs-5"><?= htmlspecialchars($curUsername) ?>님</span>
      <a href="logout.php" class="text-decoration-none text-dark fw-bold fs-4">logout</a>
    <?php else: ?>
      <a href="login.html" class="text-decoration-none text-dark fw-bold fs-4">login</a>
    <?php endif; ?>
  </div>
</nav>
<hr>

<div class="container mt-4">

  <?php if ($botResult && !empty($botResult['ok']) && empty($botResult['already'])): ?>
    <div class="solved-banner mb-4">
      <h4>🎉 Stored XSS 실습 성공!</h4>
      <p class="mb-1">관리자 봇이 XSS 스크립트가 포함된 게시글을 방문했습니다. <strong>+100점</strong> 자동 지급!</p>
      <a href="<?= MAIN_URL ?>/index.php" class="btn btn-light mt-2">메인으로 돌아가기</a>
    </div>
  <?php elseif ($botResult && !empty($botResult['already'])): ?>
    <div class="alert alert-info mb-4">
      ✅ 이미 해결한 문제입니다. <a href="<?= MAIN_URL ?>/index.php">메인으로 돌아가기</a>
    </div>
  <?php endif; ?>

  <?php if ($rows): ?>
    <div class="card shadow-sm mb-4">
      <div class="card-header">
        <h4 class="mb-0"><?= htmlspecialchars($rows['title']) ?></h4>
      </div>
      <div class="card-body">
        <div class="d-flex text-muted small mb-3 gap-4">
          <span>작성자: <strong><?= htmlspecialchars($rows['username']) ?></strong></span>
          <span>날짜: <?= htmlspecialchars($rows['date']) ?></span>
          <span>조회수: <?= (int)$rows['views'] ?></span>
        </div>
        <hr>
        <!-- 의도적으로 XSS 취약하게 유지 — 실습 목적 -->
        <div class="content-area"><?= $rows['content'] ?></div>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <a href="index.php" class="btn btn-outline-secondary btn-sm">← 목록으로</a>
        <?php if ($main_uid && $token): ?>
          <a href="view.php?board_id=<?= $board_id ?>&report=1"
             class="btn btn-warning btn-sm"
             onclick="return confirm('관리자에게 이 게시글을 신고합니다. 관리자 봇이 게시글을 방문합니다.')">
            🤖 관리자에게 신고 (XSS 트리거)
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-danger">게시글을 찾을 수 없습니다.</div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
