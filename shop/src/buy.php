<?php
require_once 'config.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$con = mysqli_connect("db_shop", "web", "web", "whz");

$sql = "SELECT * FROM products WHERE id = '".$id."'";
$result = mysqli_query($con, $sql);
$product = mysqli_fetch_assoc($result);  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = (int) $_POST['quantity'];
    if ($quantity > 0) {
        if (!isset($_COOKIE['LoginUser']) || !is_numeric($_COOKIE['LoginUser'])) {
            die("유효하지 않은 로그인 정보입니다.");
        }

        $userId = (int) $_COOKIE['LoginUser'];

        $query = "SELECT * FROM userTBL WHERE id = '".$userId."'";
        $res = mysqli_query($con, $query);

        if (!mysqli_fetch_assoc($res)) {
            die("존재하지 않는 사용자입니다.");
        }

        $sql = "INSERT INTO orders (user_id, product_id, quantity) VALUES ($userId, {$product['id']}, $quantity)";
        $result = mysqli_query($con, $sql);

        echo "<script>alert('구매가 완료되었습니다!'); location.href='shop.php';</script>";
        exit;
    } else {
        echo "<script>alert('구매 수량은 1개 이상이어야 합니다.');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>상품 구매</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .card-img-top {
      max-height: 500px;
      object-fit: contain;
      padding: 20px;
    }
  </style>

</head>
<body class="bg-light">
  <div class="container mt-5">
    <h1 class="text-center mb-4">상품 구매</h1>
    <?php if ($product): ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <?php
            if ($product['name'] === '컴퓨터') {
              $imagePath = MAIN_URL . '/images/computer.png';
            }
            elseif ($product['name'] === '모니터') {
              $imagePath = MAIN_URL . '/images/monitor.png';
            }
            elseif ($product['name'] === '키보드') {
              $imagePath = MAIN_URL . '/images/keyboard.png';
            }
	    else {
	      $imagePath = MAIN_URL . '/images/default.png'; 
            }
          ?>
          <img src="<?= $imagePath ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']); ?>">
          <h4 class="card-title"><?= htmlspecialchars($product['name']); ?></h4>
          <p class="card-text"><?= htmlspecialchars($product['description']); ?></p>
          <p class="fw-bold text-primary">가격: ₩<?= number_format($product['price']); ?></p>
          <form method="POST" class="mt-3">
            <div class="mb-3">
              <label for="quantity" class="form-label">수량</label>
              <input type="number" id="quantity" name="quantity" class="form-control" placeholder="구매할 수량을 입력하세요" min="1" required>
            </div>
            <button type="submit" class="btn btn-success">구매하기</button>
            <a href="shop.php" class="btn btn-secondary ms-2">돌아가기</a>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert-danger">해당 상품을 찾을 수 없습니다.</div>
    <?php endif; ?>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

