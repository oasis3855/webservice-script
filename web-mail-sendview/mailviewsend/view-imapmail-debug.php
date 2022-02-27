<?php

$str_version = '0.2';	// 画面に表示するバージョン
// ******************************************************
// Software name : View-ImapMail
//
// Copyright (C) INOUE Hirokazu, All Rights Reserved
//     http://oasis.halfmoon.jp/
//
// version 0.1 (2009/04/29)
// version 0.2 (2012/03/05)
//
// GNU GPL Free Software
//
// このプログラムはフリーソフトウェアです。あなたはこれを、フリーソフトウェア財
// 団によって発行された GNU 一般公衆利用許諾契約書(バージョン2か、希望によっては
// それ以降のバージョンのうちどれか)の定める条件の下で再頒布または改変することが
// できます。
// 
// このプログラムは有用であることを願って頒布されますが、*全くの無保証* です。
// 商業可能性の保証や特定の目的への適合性は、言外に示されたものも含め全く存在し
// ません。詳しくはGNU 一般公衆利用許諾契約書をご覧ください。
// 
// あなたはこのプログラムと共に、GNU 一般公衆利用許諾契約書の複製物を一部受け取
// ったはずです。もし受け取っていなければ、フリーソフトウェア財団まで請求してく
// ださい(宛先は the Free Software Foundation, Inc., 59 Temple Place, Suite 330
// , Boston, MA 02111-1307 USA)。
//
// http://www.opensource.jp/gpl/gpl.ja.html
// ******************************************************

// use user's home directory PEAR
$info=posix_getpwuid(posix_geteuid());		// get user HOME dir
ini_set('include_path', $info['dir'].'/pear/pear/php' . PATH_SEPARATOR . ini_get('include_path'));

// PearのNet_IMAPを利用する
require_once('Net/IMAP.php');
require_once('Net/IMAPProtocol.php');		// 日本語対応パッチ済み

// 設定ファイルより利用するメールアカウント一覧を読み込む
require_once('./config.php');

// 【認証共通関数】を用いる
require_once($info['dir'].'/auth/auth.php');

// メールアカウント管理コンポーネントを用いる
require_once($info['dir'].'/auth/script/mail_account.php');

// 言語と文字コードの設定
mb_language('Japanese');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// このページのファイル名（リロード用）
$thispage_filename = htmlspecialchars(basename($_SERVER['PHP_SELF']));

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
<title>PHP Mail (Net_IMAP)</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body style="font-size:11pt; background-color:#e6e6e6">
<?php

printf("<p>メール閲覧（imap版）システム Version %s (Loginのみ debugメッセージ表示版)</p>\n", $str_version);

// メールアカウントの指定
$account_no = 0;
if(!isset($_GET['account'])) {
    viewmail_accountlist($thispage_filename);
    // リロード用リンクを表示する
    printf("<p><a href=\"./index.php\">メール機能選択メニューを表示する</a></p>\n".
        "<p><a href=\"logoff.php\">ログオフ</a></p>\n".
        "</body>\n".
        "</html>\n");
    exit();
}
else {
    $account_no = intval($_GET['account']);
}
if($account_no <= 0 || $account_no > count($arrAccountsImap)) {
    printf("<p>アカウント指定Noが範囲外です<br/>account=%d</p>\n", $account_no);
    return false;
}

/** URLに与えられた引数により、表示モードを切り替える **/
if(isset($_GET['mbox']) && isset($_GET['msgid']))
{	// メールボックスとメール番号が指定された場合
    // ***何もしない***
}
else if(isset($_GET['mbox']))
{	// メールボックスが指定された場合
    // ***何もしない***
}
else
{	// 何も指定されない場合
	viewmail_mboxlist($account_no, $thispage_filename);
}

// リロード用リンクを表示する
printf("<p><a href=\"./%s?account=%d\">初期画面に戻る</a><br/>\n".
	"<a href=\"./index.php\">メール機能選択メニューを表示する</a></p>\n".
	"<p><a href=\"logoff.php\">ログオフ</a></p>\n".
	"</body>\n".
	"</html>\n", $thispage_filename, $account_no);

exit();

/*************************
 imap接続を開始する関数

 戻り値：true(成功), false(失敗)
*************************/
function open_imap(&$arr_mail_account, &$imap, $account_no)
{
    // account list array, from config.php
    global $arrAccountsImap;
    // $account_noの範囲チェック
    if($account_no <= 0 || $account_no > count($arrAccountsImap)) {
        printf("<p>アカウント指定Noが範囲外です<br/>account=%d</p>\n", $account_no);
        return false;
    }
    // メールアカウント情報（サーバ名、ユーザ名、パスワード等）を得る
    $arr_mail_account = GetMailAccount($arrAccountsImap[$account_no-1][1], 'imap');

	if(!isset($arr_mail_account['server']) || !isset($arr_mail_account['user']) || !isset($arr_mail_account['password']) || strcmp($arr_mail_account['protocol'],'imap'))
	{
		print("<p>メールアカウント管理エラー（server/user/password値が得られないか、protocolがimapでない）</p>\n");
		return false;
	}

	// IMAPサーバに接続する
	if(!strcmp($arr_mail_account['port'], '993')) {
		// IMAP SSL
		$imap = new Net_IMAP('ssl://'.$arr_mail_account['server'], 993);
	}
	elseif(!strcmp($arr_mail_account['port'], '143')) {
		// IMAP with StartTLS
		$imap = new Net_IMAP($arr_mail_account['server'], 143, true);
	}
	else {
		printf("<p>このプログラムで利用可能なMAPサーバのポートは993(SSL)もしくは143のみです</p>\n<p>port=%s</p>\n", $arr_mail_account['port']);
		return false;
	}
    
    // デバッグメッセージをONにする
    $imap->setDebug(true);
    
    print("\n<pre>\n\n");   // for debug msg
	$imap_result = $imap->login($arr_mail_account['user'], $arr_mail_account['password']);
    print("\n\n</pre>\n");   // for debug msg
	if(PEAR::isError($imap_result))
	{
		print("<p>IMAPサーバへの接続に失敗</p>\n");
		return false;
	}
	printf("<p>IMAPサーバに接続 （%s）</p>\n", htmlspecialchars($arr_mail_account['user']));


	return true;
}

/*************************
 メールアカウント一覧を表示する関数
*************************/
function viewmail_accountlist($thispage_filename)
{
    // account list array, from config.php
    global $arrAccountsImap;

    for($i=0; $i<count($arrAccountsImap); $i++) {
        printf("<p><a href=\"%s?account=%d\">%s</a></p>\n", $thispage_filename, $i+1, $arrAccountsImap[$i][0]);
    }
    return;
}


/*************************
 メールボックス一覧を表示する関数
*************************/
function viewmail_mboxlist($account_no, $thispage_filename)
{
	// imapサーバに接続する
	$imap = '';
	$arr_mail_account = array();
	if(!open_imap($arr_mail_account, $imap, $account_no)) return;

    print("\n<pre>\n\n");   // for debug msg
	// メールボックス一覧の取得
	$arr_mailbox = $imap->getMailboxes();
    print("\n\n</pre>\n");   // for debug msg
	if(PEAR::isError($arr_mailbox))
	{
		$imap->disconnect();
		print("<p>メールボックス一覧が取得できませんでした</p>\n");
		return;
	}

	// メールボックス一覧を表示
	if (is_array($arr_mailbox)) {
	    foreach ($arr_mailbox as $mailbox_id => $str_mailbox_name) {
			$str_mailbox_name = mb_convert_encoding($str_mailbox_name, 'UTF-8', 'auto');
			$str_mailbox_name = htmlspecialchars($str_mailbox_name, ENT_QUOTES, 'UTF-8');

			printf("%02d : <a href=\"%s?account=%d&amp;mbox=%d\">%s</a><br/>\n", $mailbox_id, $thispage_filename, $account_no, $mailbox_id, $str_mailbox_name);
	    }
	}


	// IMAPサーバから切断
    print("\n<pre>\n\n");   // for debug msg
	$imap->disconnect();
    print("\n\n</pre>\n");   // for debug msg

	return;
}



?>
