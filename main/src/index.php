<?php
require_once 'config.php';
$conn     = db_connect();
init_progress_table($conn);

$loggedIn = isset($_COOKIE['LoginUser']);
$userId   = null;
$username = null;
$progress = [];
$score    = 0;

if ($loggedIn) {
    $userId = (int)$_COOKIE['LoginUser'];
    $stmt   = mysqli_prepare($conn, "SELECT username, level1_clear, level2_clear, level3_clear, score FROM userTBL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $username = $row['username'];
        $score    = $row['score'];
    }
    mysqli_stmt_close($stmt);
    $progress = get_user_progress($conn, $userId);
}
mysqli_close($conn);

/* 카테고리별 완료 스테이지 수 계산 */
function count_done($progress, $stages) {
    $done = 0;
    foreach ($stages as $s) {
        if (!empty($progress[$s['key']])) $done++;
    }
    return $done;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>Web Hacking Zone</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .logo-img      { max-width:125px; height:125px; display:block; margin:30px auto; }
    .cat-card      { border-radius:16px; box-shadow:0 6px 16px rgba(0,0,0,.12); transition:.25s; cursor:pointer; height:100%; }
    .cat-card:hover{ transform:translateY(-4px); box-shadow:0 10px 24px rgba(0,0,0,.18); }
    .cat-icon      { font-size:3rem; }
    .diff-badge    { font-size:.75rem; }
    .progress      { height:8px; }
    .hero          { background:linear-gradient(135deg,#1a1a2e 0%,#16213e 60%,#0f3460 100%); color:#fff; padding:56px 0 40px; }
    .hero h1       { font-weight:800; font-size:2.4rem; }
    .hero p        { opacity:.8; }
    .score-badge   { background:#ffd700; color:#333; font-weight:700; border-radius:20px; padding:4px 14px; }
    .section-title { font-weight:700; font-size:1.4rem; border-left:4px solid #0d6efd; padding-left:12px; margin-bottom:24px; }
    .locked-overlay{ pointer-events:none; opacity:.45; }
  </style>
</head>
<body>

<!-- ── 네비게이션 ── -->
<nav class="container-fluid px-4 position-relative d-flex justify-content-between align-items-center" style="height:110px;">
  <div class="fw-bold">
    <a href="index.php" class="text-decoration-none text-dark fs-3">home</a>
  </div>
  <div class="position-absolute top-50 start-50 translate-middle">
    <img src="./images/HoneyRain.png" alt="logo" class="logo-img">
  </div>
  <div class="text-end d-flex align-items-center gap-3">
    <?php if ($loggedIn): ?>
      <span class="score-badge">🏆 <?= $score ?>점</span>
      <a href="scoreboard.php" class="text-decoration-none text-dark fw-semibold fs-5">랭킹</a>
      <span class="me-1 small fw-medium fs-5"><?= htmlspecialchars($username) ?>님</span>
      <a href="logout.php" class="text-decoration-none text-dark fw-bold fs-4">logout</a>
    <?php else: ?>
      <a href="scoreboard.php" class="text-decoration-none text-dark fw-semibold fs-5">랭킹</a>
      <a href="login.html"  class="text-decoration-none text-dark fw-bold fs-4">login</a>
    <?php endif; ?>
  </div>
</nav>
<hr>

<!-- ── 히어로 ── -->
<div class="hero text-center mb-5">
  <div class="container">
    <h1>Web Hacking Learning Platform</h1>
    <p class="mb-3 fs-5">실제 취약한 환경에서 웹 해킹 기법을 단계별로 배우고 실습하세요.</p>
    <?php if (!$loggedIn): ?>
      <a href="register.html" class="btn btn-primary btn-lg me-2">회원가입</a>
      <a href="login.html"    class="btn btn-outline-light btn-lg">로그인</a>
    <?php else: ?>
      <a href="scoreboard.php" class="btn btn-warning btn-lg">🏆 전체 랭킹 보기</a>
    <?php endif; ?>
  </div>
</div>

<!-- ── 카테고리 ── -->
<div class="container mb-5">
  <div class="section-title">학습 카테고리</div>

  <?php if (!$loggedIn): ?>
    <div class="alert alert-warning">⚠️ 학습을 시작하려면 <a href="login.html">로그인</a>이 필요합니다.</div>
  <?php endif; ?>

  <div class="row g-4">
  <?php foreach ($CATEGORIES as $catId => $cat):
    $stages  = $cat['stages'];
    $total   = count($stages);
    $done    = $loggedIn ? count_done($progress, $stages) : 0;
    $pct     = $total > 0 ? round($done / $total * 100) : 0;

    /* 카테고리 잠금: 이전 카테고리의 lab 스테이지가 모두 완료되어야 접근 가능 */
    $unlocked = true;
    if ($loggedIn && $catId > 1) {
        $prevCat  = $CATEGORIES[$catId - 1];
        foreach ($prevCat['stages'] as $ps) {
            if ($ps['type'] === 'lab' && empty($progress[$ps['key']])) {
                $unlocked = false;
                break;
            }
        }
    } elseif (!$loggedIn) {
        $unlocked = false;
    }

    $colorClass = 'border-' . $cat['color'];
    $btnClass   = 'btn-' . $cat['color'];
  ?>
    <div class="col-md-4">
      <div class="cat-card card <?= $unlocked ? '' : 'locked-overlay' ?> h-100">
        <div class="card-body text-center p-4">
          <div class="cat-icon mb-2"><?= $cat['icon'] ?></div>
          <h4 class="fw-bold mb-1"><?= htmlspecialchars($cat['name']) ?></h4>
          <span class="badge bg-secondary diff-badge mb-3">난이도: <?= $cat['difficulty'] ?></span>
          <p class="text-muted small mb-3"><?= htmlspecialchars($cat['description']) ?></p>

          <?php if ($loggedIn): ?>
            <div class="mb-1 d-flex justify-content-between small text-muted">
              <span>진행률</span><span><?= $done ?>/<?= $total ?></span>
            </div>
            <div class="progress mb-3">
              <div class="progress-bar bg-<?= $cat['color'] ?>" style="width:<?= $pct ?>%"></div>
            </div>
          <?php endif; ?>

          <div class="d-flex flex-column gap-1 text-start small mb-3">
          <?php foreach ($stages as $idx => $stage):
            $stageDone = $loggedIn && !empty($progress[$stage['key']]);
          ?>
            <span class="<?= $stageDone ? 'text-success fw-semibold' : 'text-muted' ?>">
              <?= $stageDone ? '✅' : '○' ?>
              <?= ($idx + 1) ?>단계: <?= htmlspecialchars($stage['title']) ?>
              <span class="text-warning">(+<?= $stage['points'] ?>점)</span>
            </span>
          <?php endforeach; ?>
          </div>
        </div>
        <div class="card-footer bg-transparent text-center pb-3">
          <?php if ($unlocked): ?>
            <a href="category.php?id=<?= $catId ?>" class="btn <?= $btnClass ?> btn-sm px-4">
              <?= $done > 0 ? '이어서 학습' : '학습 시작' ?>
            </a>
          <?php else: ?>
            <button class="btn btn-secondary btn-sm px-4" disabled>잠금</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
