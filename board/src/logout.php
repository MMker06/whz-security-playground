<?php
setcookie("LoginUser", "", time() - 1, "/");
header("Location: index.php");
exit;
