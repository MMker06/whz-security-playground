<?php
	$conn = mysqli_connect('db_main', 'web', 'web', 'whz');

	$loggedIn = isset($_COOKIE['LoginUser']);
	$actualFlag = "FLAG(cookie_poisoning_success)";

	if ($loggedIn) {
		$userId = (int) $_COOKIE['LoginUser'];
		$query = "SELECT username FROM userTBL WHERE id = '".$userId."'";
		$resultUser = mysqli_query($conn, $query);
		if ($row = mysqli_fetch_assoc($resultUser)) {
			$username = $row['username'];
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && $loggedIn) {
	$inputFlag = $_POST['flag_input'];

		if ($inputFlag === $actualFlag) {
			echo "  <script>
				alert('정답입니다! 다음 레벨로 이동하세요.');
				window.location.href = 'index.php';
				</script>";
		}
		else {
			echo "  <script>
				alert('오답입니다. 다시 시도해 보세요.');
				history.back();
				</script>";
		}
	}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>LV.3 FLAG 인증</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- 상단 내비게이션 유지 -->
<nav class="container-fluid px-4 position-relative d-flex justify-content-between align-items-center" style="height: 110px;">
  <!-- 왼쪽 -->
  <div class="fw-bold">
    <a href="index.php" class="text-decoration-none text-dark fs-3">home</a>
  </div>

  <!-- 가운데 로고 -->
  <div class="position-absolute top-50 start-50 translate-middle">
    <img src="../../images/HoneyRain.png" alt="logo" style="max-width: 125px; height: 125px;">
  </div>

  <!-- 오른쪽 -->
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

<div class="container mt-5">
  <div class="card">
    <div class="card-header bg-dark text-white text-center fw-bold fs-4">
      LV.3 FLAG 인증
    </div>
    <div class="card-body text-center">
      <form method="post" class="w-50 mx-auto">
        <label for="flag_input" class="form-label fs-5"></label>
        <input type="text" name="flag_input" id="flag_input" class="form-control" required>
        <button type="submit" class="btn btn-primary mt-3">제출</button>
      </form>

      <?php if ($flagMessage): ?>
        <div class="mt-3 fw-bold text-<?= str_starts_with($flagMessage) ? "success" : "danger" ?>">
          <?= $flagMessage ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>

