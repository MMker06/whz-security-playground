<?php
session_start();
require_once 'config.php';

$conn     = mysqli_connect('db_shop', 'web', 'web', 'whz');
$loggedIn = isset($_COOKIE['LoginUser']);
$username = null;
$isAdmin  = false;

/* URL로 전달된 챌린지 파라미터 세션에 저장 */
if (isset($_GET['main_uid']) && isset($_GET['token'])) {
    $_SESSION['main_uid'] = (int)$_GET['main_uid'];
    $_SESSION['token']    = $_GET['token'];
}

if ($loggedIn) {
    $userId = (int)$_COOKIE['LoginUser'];
    $stmt   = mysqli_prepare($conn, "SELECT username FROM userTBL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($row) {
        $username = $row['username'];
        $isAdmin  = ($username === 'admin');
    }
}

/* admin으로 로그인된 경우 자동 채점 시도 */
$solveResult = null;
if ($isAdmin && !empty($_SESSION['main_uid']) && !empty($_SESSION['token'])) {
    $main_uid  = (int)$_SESSION['main_uid'];
    $token     = $_SESSION['token'];
    $challenge = 'sqli';

    /* 토큰 검증 */
    $expected = make_challenge_token($main_uid, $challenge);
    if (hash_equals($expected, $token)) {
        /* 메인 서버 solve API 호출 */
        $ch = curl_init(MAIN_API_URL . '/api/solve.php');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'main_uid'  => $main_uid,
                'challenge' => $challenge,
                'token'     => $token,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $solveResult = json_decode($resp, true);

        if ($solveResult && $solveResult['ok']) {
            unset($_SESSION['main_uid'], $_SESSION['token']);
        }
    }
}

$result = mysqli_query($conn, "SELECT * FROM products");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>온라인 쇼핑몰</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .card-img-top { max-height:200px; object-fit:contain; padding:20px; }
    .logo-img     { max-width:125px; height:125px; display:block; margin:30px auto; }
    .solved-banner{ background:linear-gradient(90deg,#198754,#20c997); color:#fff;
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
      <span class="me-2 small fw-medium fs-5"><?= htmlspecialchars($username) ?>님</span>
      <a href="logout.php" class="text-decoration-none text-dark fw-bold fs-4">logout</a>
    <?php else: ?>
      <a href="login.html" class="text-decoration-none text-dark fw-bold fs-4">login</a>
    <?php endif; ?>
  </div>
</nav>
<hr>

<div class="container mt-4">

  <!-- ── 관리자 로그인 성공 → 자동 채점 배너 ── -->
  <?php if ($isAdmin): ?>
    <?php if ($solveResult && $solveResult['ok'] && empty($solveResult['already'])): ?>
      <div class="solved-banner mb-4">
        <h4>🎉 SQL Injection 실습 성공!</h4>
        <p class="mb-1">관리자 계정으로 로그인 우회에 성공했습니다. <strong>+100점</strong>이 자동으로 지급되었습니다.</p>
        <a href="<?= MAIN_URL ?>/index.php" class="btn btn-light mt-2">메인으로 돌아가기</a>
      </div>
    <?php elseif ($solveResult && !empty($solveResult['already'])): ?>
      <div class="alert alert-info mb-4">
        ✅ 이미 해결한 문제입니다. <a href="<?= MAIN_URL ?>/index.php">메인으로 돌아가기</a>
      </div>
    <?php else: ?>
      <div class="alert alert-success mb-4">
        ✅ 관리자 계정으로 로그인 성공! SQL Injection 공격이 작동했습니다.
        <?php if (!empty($_SESSION['main_uid'])): ?>
          <br><small class="text-muted">자동 채점 중... 메인 서버 연결 확인 필요</small>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- 상품 목록 -->
  <div class="row row-cols-1 row-cols-md-3 g-4">
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <?php
              $img = match($row['name']) {
                '컴퓨터' => MAIN_URL . '/images/computer.png',
                '모니터' => MAIN_URL . '/images/monitor.png',
                '키보드' => MAIN_URL . '/images/keyboard.png',
                default  => MAIN_URL . '/images/default.png',
              };
            ?>
            <img src="<?= $img ?>" class="card-img-top" alt="<?= htmlspecialchars($row['name']) ?>">
            <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
            <p class="card-text"><?= htmlspecialchars($row['description']) ?></p>
            <p class="card-text fw-bold text-primary">가격: ₩<?= number_format($row['price']) ?></p>
          </div>
          <div class="card-footer text-center">
            <?php if ($loggedIn): ?>
              <a href="buy.php?id=<?= $row['id'] ?>" class="btn btn-success">구매하기</a>
            <?php else: ?>
              <button class="btn btn-secondary" disabled>로그인 후 구매 가능</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
