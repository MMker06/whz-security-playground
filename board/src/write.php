<?php
session_start();
require_once 'config.php';
$con = mysqli_connect('db_board', 'web', 'web', 'whz') or die("DB 연결 실패");

$loggedIn = isset($_COOKIE['LoginUser']);
$username = '';

/* URL에서 챌린지 파라미터 세션에 저장 */
if (isset($_GET['main_uid']) && isset($_GET['token'])) {
    $_SESSION['main_uid'] = (int)$_GET['main_uid'];
    $_SESSION['token']    = $_GET['token'];
}

$main_uid = (int)($_SESSION['main_uid'] ?? 0);
$token    = $_SESSION['token'] ?? '';

if ($loggedIn) {
    $userId = (int)$_COOKIE['LoginUser'];
    $stmt   = mysqli_prepare($con, "SELECT username FROM userTBL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    $username = $row ? htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') : '';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>게시글 작성</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .logo-img { max-width:125px; height:125px; display:block; margin:0 auto; }
    .hint-box  { background:#fff3cd; border-left:4px solid #ffc107; border-radius:8px; padding:14px 18px; }
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

<div class="container mt-4" style="max-width:900px;">
  <?php if ($main_uid): ?>
    <div class="hint-box mb-4">
      <strong>🔬 XSS 실습 안내</strong><br>
      게시글 내용에 <code>&lt;script&gt;</code> 태그를 포함해 작성하세요.<br>
      작성 후 게시글 뷰 페이지의 <strong>"관리자에게 신고"</strong> 버튼을 눌러 관리자 봇이 방문하게 하면 자동으로 성공 처리됩니다.<br>
      <small class="text-muted">예시: <code>&lt;script&gt;alert('XSS')&lt;/script&gt;</code></small>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-header text-center"><h4>게시글 작성</h4></div>
    <div class="card-body">
      <form method="POST" action="write_result.php">
        <input type="hidden" name="username" value="<?= $username ?>">
        <input type="hidden" name="main_uid" value="<?= $main_uid ?>">
        <input type="hidden" name="token"    value="<?= htmlspecialchars($token) ?>">

        <div class="mb-3 row align-items-center">
          <label class="col-sm-2 col-form-label">작성자</label>
          <div class="col-sm-10">
            <p class="form-control-plaintext mb-0 fw-semibold"><?= $username ?></p>
          </div>
        </div>
        <div class="mb-3">
          <label for="title" class="form-label">제목</label>
          <input type="text" class="form-control" id="title" name="title" placeholder="제목을 입력하세요" required>
        </div>
        <div class="mb-3">
          <label for="content" class="form-label">내용</label>
          <textarea class="form-control" id="content" name="content" rows="8"
                    placeholder="내용을 입력하세요 (XSS 실습: &lt;script&gt;태그 삽입 가능)" required></textarea>
        </div>
        <div class="text-center">
          <button type="submit" class="btn btn-primary px-4">게시글 등록</button>
          <a href="index.php" class="btn btn-outline-secondary ms-2">취소</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
