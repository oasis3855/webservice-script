<?php
$info=posix_getpwuid(posix_geteuid());		# ユーザのホームディレクトリを得る
// use user's home directory PEAR
ini_set('include_path', $info['dir'].'/pear/pear/php' . PATH_SEPARATOR . ini_get('include_path'));
// PearのNet_IMAPを利用する
require_once('Net/IMAP.php');
require_once('Net/IMAPProtocol.php');		// 日本語対応パッチ済み

// 【認証共通関数】を用いる
require_once($info['dir'].'/auth/auth.php');
// メールアカウント管理コンポーネントを用いる
require_once($info['dir'].'/auth/script/mail_account.php');

// 言語と文字コードの設定
mb_language('Japanese');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

if(isset($_GET['mode']) && $_GET['mode'] === 'logout'){
	// ログオフ処理（exitする）。sessionを発行するので、PHPの出力より前にこの処理を置く
	func_logoff_auth(basename($_SERVER['PHP_SELF']), 0);
}
else{
	// ログイン処理
	func_check_auth(basename($_SERVER['PHP_SELF']), basename($_SERVER['PHP_SELF']), 0, 'UTF-8');
}

?><!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja-JP" xml:lang="ja-JP">
<head>
<title>メール機能選択メニュー</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body style="font-size:11pt; background-color:#e6e6e6">
<p>メール機能選択メニュー</p>

<p><a href="view-imapmail.php">メール閲覧（imap版）システムを使う</a>&nbsp;&nbsp;&nbsp;<a href="view-imapmail-debug.php">imap debug messageを表示する(loginのみ)</a></p>
<p><a href="view-pop3mail.php">メール閲覧（pop3版）システムを使う</a></p>
<p><a href="sendmail-mime.php">送信(mime）</a>&nbsp;/&nbsp;<a href="sendmail-smtp.php">送信(smtp）</a></p>
<p><a href="<?php echo basename($_SERVER['PHP_SELF']); ?>?mode=logout">ログオフ</a></p>
</body>
</html>
