<?php
$_host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : 'localhost';
define('MAIN_URL',     'http://' . $_host);
define('MAIN_API_URL', 'http://main_web');
define('SHOP_URL',     'http://' . $_host . ':8001');
define('CHALLENGE_SECRET', getenv('CHALLENGE_SECRET') ?: 'whz_local_dev_secret_change_me');

function make_challenge_token($main_uid, $challenge) {
    return hash_hmac('sha256', (string)$main_uid . ':' . $challenge, CHALLENGE_SECRET);
}

function verify_challenge_token($main_uid, $challenge, $token) {
    if (empty($token)) return false;
    return hash_equals(make_challenge_token($main_uid, $challenge), $token);
}
