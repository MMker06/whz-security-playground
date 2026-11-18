<?php
require_once 'config.php';
$conn = db_connect();

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo "<script>alert('아이디와 비밀번호를 입력해주세요.'); history.back();</script>";
    exit;
}

/* Prepared Statement으로 SQL Injection 방지 */
$stmt = mysqli_prepare($conn, "SELECT id, username FROM userTBL WHERE username = ? AND password = ?");
mysqli_stmt_bind_param($stmt, 'ss', $username, $password);
mysqli_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
mysqli_close($conn);

if ($user) {
    setcookie('LoginUser', $user['id'], time() + 3600, '/', '', false, true);
    header('Location: index.php');
    exit;
} else {
    echo "<script>alert('잘못된 아이디 또는 비밀번호입니다.'); history.back();</script>";
}
