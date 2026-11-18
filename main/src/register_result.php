<?php
require_once 'config.php';
$conn = db_connect();

$realname = trim($_POST['realname'] ?? '');
$email    = trim($_POST['email']    ?? '');
$address  = trim($_POST['address']  ?? '');
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$realname || !$email || !$username || !$password) {
    echo "<script>alert('모든 필수 항목을 입력해주세요.'); history.back();</script>";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('올바른 이메일 형식을 입력해주세요.'); history.back();</script>";
    exit;
}

/* 중복 확인 — Prepared Statement */
$chk = mysqli_prepare($conn, "SELECT id FROM userTBL WHERE username = ? OR email = ?");
mysqli_stmt_bind_param($chk, 'ss', $username, $email);
mysqli_execute($chk);
mysqli_stmt_store_result($chk);
$dup = mysqli_stmt_num_rows($chk) > 0;
mysqli_stmt_close($chk);

if ($dup) {
    echo "<script>alert('이미 사용중인 아이디 또는 이메일입니다.'); history.back();</script>";
    mysqli_close($conn);
    exit;
}

$ins = mysqli_prepare($conn, "INSERT INTO userTBL (realname, email, address, username, password) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($ins, 'sssss', $realname, $email, $address, $username, $password);

if (mysqli_execute($ins)) {
    echo "<script>alert('회원가입 성공!'); location.href = 'login.html';</script>";
} else {
    echo "<script>alert('회원가입 실패. 다시 시도해주세요.'); history.back();</script>";
}

mysqli_stmt_close($ins);
mysqli_close($conn);
