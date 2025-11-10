<?php

$USER_HOME_DIR = posix_getpwuid(posix_geteuid())['dir'];
$strBaseDir = $USER_HOME_DIR.'/www';

// HTTP絶対パス（最後のスラッシュは含まない）
$strAbsolutePath = 'https://www.example.com';

// 画像幅指定
$nFixWidth = 320;


?>
