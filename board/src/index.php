<?php
session_start();
require_once 'config.php';
$con = mysqli_connect('db_board', 'web', 'web', 'whz') or die("DB 연결 실패");

/* URL에서 챌린지 파라미터 세션에 저장 */
if (isset($_GET['main_uid']) && isset($_GET['token'])) {
    $_SESSION['main_uid'] = (int)$_GET['main_uid'];
    $_SESSION['token']    = $_GET['token'];
}

$loggedIn = isset($_COOKIE['LoginUser']);
$username = null;
$cookieId = 0;

if ($loggedIn) {
    $cookieId = (int)$_COOKIE['LoginUser'];
    $stmt = mysqli_prepare($con, "SELECT username FROM userTBL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $cookieId);
    mysqli_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    $username = $row['username'] ?? null;
}

/* 쿠키 변조 감지: 정상 계정 id = 1(admin), 2(victim) */
$cookieTampered = ($loggedIn && ($cookieId < 1 || $cookieId > 2 || $username === null));

$solveResult = null;
if ($cookieTampered) {
    $main_uid = (int)($_SESSION['main_uid'] ?? 0);
    $token    = $_SESSION['token'] ?? '';

    if ($main_uid && $token) {
        $expected = make_challenge_token($main_uid, 'cookie');
        if (hash_equals($expected, $token)) {
            $ch = curl_init(MAIN_API_URL . '/api/solve.php');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'main_uid'  => $main_uid,
                    'challenge' => 'cookie',
                    'token'     => $token,
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);
            $resp        = curl_exec($ch);
            curl_close($ch);
            $solveResult = json_decode($resp, true);
            if ($solveResult && $solveResult['ok']) {
                unset($_SESSION['main_uid'], $_SESSION['token']);
            }
        }
    }
}

/* 게시글 목록 */
$listResult = mysqli_query($con, "SELECT * FROM boardTBL ORDER BY board_id DESC");
$total      = mysqli_num_rows($listResult);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>게시판</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .logo-img     { max-width:125px; height:125px; display:block; margin:0 auto; }
    .solved-banner{ background:linear-gradient(90deg,#198754,#20c997); color:#fff;
                    border-radius:12px; padding:20px 28px; text-align:center; }
    .tamper-hint  { background:#fff3cd; border-left:4px solid #ffc107; border-radius:8px; padding:14px 18px; }
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
      <span class="me-2 small fw-medium fs-5">
        <?= $username ? htmlspecialchars($username) : '알 수 없는 사용자 (변조됨)' ?>님
      </span>
      <a href="logout.php" class="text-decoration-none text-dark fw-bold fs-4">logout</a>
    <?php else: ?>
      <a href="login.html" class="text-decoration-none text-dark fw-bold fs-4">login</a>
    <?php endif; ?>
  </div>
</nav>
<hr>

<div class="container mt-4">

  <!-- 쿠키 변조 성공 배너 -->
  <?php if ($cookieTampered): ?>
    <?php if ($solveResult && !empty($solveResult['ok']) && empty($solveResult['already'])): ?>
      <div class="solved-banner mb-4">
        <h4>🎉 쿠키 변조 실습 성공!</h4>
        <p class="mb-1">LoginUser 쿠키를 변조하여 인증을 우회했습니다. <strong>+100점</strong> 자동 지급!</p>
        <a href="<?= MAIN_URL ?>/index.php" class="btn btn-light mt-2">메인으로 돌아가기</a>
      </div>
    <?php elseif ($solveResult && !empty($solveResult['already'])): ?>
      <div class="alert alert-info mb-4">
        ✅ 이미 해결한 문제입니다. <a href="<?= MAIN_URL ?>/index.php">메인으로 돌아가기</a>
      </div>
    <?php else: ?>
      <div class="alert alert-warning mb-4">
        ⚠️ 쿠키 변조 감지! (LoginUser = <?= $cookieId ?>) — 존재하지 않는 사용자 ID입니다.
        <?php if (empty($_SESSION['main_uid'])): ?>
          <br><small>챌린지 토큰이 없습니다. 메인 페이지에서 실습을 시작하세요.</small>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- 쿠키 변조 실습 힌트 -->
  <?php if (!empty($_SESSION['main_uid']) && !$cookieTampered): ?>
    <div class="tamper-hint mb-4">
      <strong>🍪 쿠키 변조 실습 안내</strong><br>
      게시판에 로그인한 후, 브라우저 개발자 도구(F12)에서 <code>LoginUser</code> 쿠키 값을 변조하세요.<br>
      현재 등록된 계정 ID: <code>1(admin)</code>, <code>2(victim)</code><br>
      이 외의 값(예: <strong>999</strong>)으로 변경 후 새로고침하면 자동 성공 처리됩니다.<br>
      <small class="text-muted">콘솔: <code>document.cookie = "LoginUser=999; path=/"</code></small>
    </div>
  <?php endif; ?>

  <h2 class="text-center mb-4">게시판</h2>

  <table class="table table-bordered table-hover text-center">
    <thead class="table-dark">
      <tr>
        <th width="50">번호</th>
        <th>제목</th>
        <th width="150">작성자</th>
        <th width="200">날짜</th>
        <th width="60">조회수</th>
      </tr>
    </thead>
    <tbody>
      <?php $num = $total; while ($rows = mysqli_fetch_assoc($listResult)): ?>
        <tr>
          <td><?= $num ?></td>
          <td class="text-start">
            <a href="view.php?board_id=<?= $rows['board_id'] ?>" class="text-decoration-none">
              <?= htmlspecialchars($rows['title']) ?>
            </a>
          </td>
          <td><?= htmlspecialchars($rows['username']) ?></td>
          <td><?= htmlspecialchars($rows['date']) ?></td>
          <td><?= (int)$rows['views'] ?></td>
        </tr>
        <?php $num--; endwhile; ?>
    </tbody>
  </table>

  <div class="d-flex justify-content-between mt-3">
    <?php if ($loggedIn): ?>
      <a href="write.php" class="btn btn-primary">글쓰기</a>
    <?php else: ?>
      <a href="login.html" class="btn btn-outline-primary">로그인 후 글쓰기</a>
    <?php endif; ?>
    <a href="<?= MAIN_URL ?>/index.php" class="btn btn-outline-secondary">← 메인으로</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
