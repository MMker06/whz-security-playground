<?php
$con = mysqli_connect('db_board', 'web', 'web', 'whz') or die("DB 접속 실패");
$sql = "SELECT * FROM userTBL WHERE username='" . $_POST['username'] . "' AND password='" . $_POST['password'] . "'";
$result = mysqli_query($con, $sql);

if ($user = mysqli_fetch_assoc($result)) {
    setcookie("LoginUser", $user['id'], time() + 3600, "/");
    header("Location: index.php");
    exit;
} else {
    echo "<script>alert('잘못된 아이디 또는 비밀번호입니다.'); history.back();</script>";
}
mysqli_close($con);
