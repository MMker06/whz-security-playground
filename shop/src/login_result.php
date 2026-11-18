<?php
session_start();
$con = mysqli_connect('db_shop', 'web', 'web', 'whz') or die("접속 실패");

/* 취약한 쿼리 — SQL Injection 실습용으로 의도적으로 유지 */
$sql    = "SELECT * FROM userTBL WHERE username='" . $_POST['username'] . "' AND password='" . $_POST['password'] . "'";
$result = mysqli_query($con, $sql);

if ($user = mysqli_fetch_assoc($result)) {
    setcookie('LoginUser', $user['id'], time() + 3600, '/');
    mysqli_close($con);
    header('Location: index.php');
    exit;
} else {
    mysqli_close($con);
    echo "<script>alert('잘못된 아이디 또는 비밀번호입니다.'); history.back();</script>";
}
