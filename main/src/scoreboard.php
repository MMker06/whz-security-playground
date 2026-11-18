<?php
require_once 'config.php';
$conn     = db_connect();
init_progress_table($conn);

$loggedIn = isset($_COOKIE['LoginUser']);
$myId     = $loggedIn ? (int)$_COOKIE['LoginUser'] : 0;
$username = null;
$myScore  = 0;

if ($loggedIn) {
    $stmt = mysqli_prepare($conn, "SELECT username, score FROM userTBL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $myId);
    mysqli_execute($stmt);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $username = $r['username'];
    $myScore  = $r['score'];
    mysqli_stmt_close($stmt);
}

/* 전체 랭킹 조회 */
$rankResult = mysqli_query($conn,
    "SELECT u.id, u.username, u.score,
            u.level1_clear, u.level2_clear, u.level3_clear,
            (SELECT COUNT(*) FROM user_progress WHERE user_id = u.id) AS stages_done
     FROM userTBL u
     ORDER BY u.score DESC, u.id ASC"
);
$users = [];
while ($row = mysqli_fetch_assoc($rankResult)) $users[] = $row;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>스코어보드 — Web Hacking Zone</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .logo-img    { max-width:125px; height:125px; display:block; margin:30px auto; }
    .score-badge { background:#ffd700; color:#333; font-weight:700; border-radius:20px; padding:4px 14px; }
    .rank-1      { background:linear-gradient(90deg,#fff8dc,#fff); font-weight:700; }
    .rank-2      { background:linear-gradient(90deg,#f5f5f5,#fff); }
    .rank-3      { background:linear-gradient(90deg,#fff0e6,#fff); }
    .my-row      { outline:2px solid #0d6efd; }
  </style>
</head>
<body>

<nav class="container-fluid px-4 position-relative d-flex justify-content-between align-items-center" style="height:110px;">
  <div class="fw-bold">
    <a href="index.php" class="text-decoration-none text-dark fs-3">home</a>
  </div>
  <div class="position-absolute top-50 start-50 translate-middle">
    <img src="./images/HoneyRain.png" alt="logo" class="logo-img">
  </div>
  <div class="text-end d-flex align-items-center gap-3">
    <?php if ($loggedIn): ?>
      <span class="score-badge">🏆 <?= $myScore ?>점</span>
      <span class="small fw-medium fs-5"><?= htmlspecialchars($username) ?>님</span>
      <a href="logout.php" class="text-decoration-none text-dark fw-bold fs-4">logout</a>
    <?php else: ?>
      <a href="login.html" class="text-decoration-none text-dark fw-bold fs-4">login</a>
    <?php endif; ?>
  </div>
</nav>
<hr>

<div class="container mt-4 mb-5">
  <h3 class="fw-bold mb-1">🏆 스코어보드</h3>
  <p class="text-muted mb-4">실습 문제를 풀고 점수를 획득하여 랭킹을 올리세요!</p>

  <div class="card shadow-sm">
    <div class="card-header bg-dark text-white d-flex align-items-center">
      <span class="fw-bold">전체 랭킹</span>
      <span class="ms-auto small opacity-75">총 <?= count($users) ?>명 참가 중</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th width="60" class="text-center">순위</th>
            <th>닉네임</th>
            <th class="text-center">점수</th>
            <th class="text-center">SQL Injection</th>
            <th class="text-center">Stored XSS</th>
            <th class="text-center">쿠키 변조</th>
            <th class="text-center">완료 단계</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $i => $u):
          $rank  = $i + 1;
          $isMe  = ($u['id'] == $myId);
          $rankCls = match($rank) { 1 => 'rank-1', 2 => 'rank-2', 3 => 'rank-3', default => '' };
          $medal   = match($rank) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => $rank };
        ?>
          <tr class="<?= $rankCls ?> <?= $isMe ? 'my-row' : '' ?>">
            <td class="text-center fw-bold"><?= $medal ?></td>
            <td>
              <?= htmlspecialchars($u['username']) ?>
              <?= $isMe ? ' <span class="badge bg-primary small">나</span>' : '' ?>
            </td>
            <td class="text-center fw-bold text-warning"><?= $u['score'] ?>점</td>
            <td class="text-center"><?= $u['level1_clear'] ? '✅' : '—' ?></td>
            <td class="text-center"><?= $u['level2_clear'] ? '✅' : '—' ?></td>
            <td class="text-center"><?= $u['level3_clear'] ? '✅' : '—' ?></td>
            <td class="text-center"><?= $u['stages_done'] ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">아직 참가자가 없습니다.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3 text-center">
    <a href="index.php" class="btn btn-outline-primary">← 학습 시작하기</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
