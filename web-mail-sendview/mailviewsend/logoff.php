<?php
$info=posix_getpwuid(posix_geteuid());		# ユーザのホームディレクトリを得る

// 【認証共通関数】を用いる
require_once($info['dir'].'/auth/auth.php');

// 言語と文字コードの設定
mb_language('Japanese');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

func_logoff_auth(basename($_SERVER['PHP_SELF']), 0);

?>
