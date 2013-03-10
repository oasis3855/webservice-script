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

printf("<p>メール閲覧（imap版）システム Version %s</p>\n", $str_version);

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
	if(!isset($_GET['decode'])){
		printf("<p><a href=\"%s?account=%d&amp;mbox=%d&amp;msgid=%d&amp;decode=quotedprintable\">Quoted-Printableをデコードする</a></p>\n", $thispage_filename, $account_no, $_GET['mbox'], $_GET['msgid']);
	}
	viewmail_readmsg($account_no, $_GET['mbox'], $_GET['msgid'], $thispage_filename);
}
else if(isset($_GET['mbox']))
{	// メールボックスが指定された場合
	$start_message_id = 0;
	$flag_show_all_message = false;
	if(isset($_GET['startid'])) $start_message_id = $_GET['startid'];
	if(isset($_GET['allid'])) $flag_show_all_message = true;
	viewmail_msglist($account_no, $_GET['mbox'], $start_message_id, $flag_show_all_message, $thispage_filename);
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
	if(PEAR::isError($imap->login($arr_mail_account['user'], $arr_mail_account['password'])))
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

	// メールボックス一覧の取得
	$arr_mailbox = $imap->getMailboxes();
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
	$imap->disconnect();

	return;
}

/*************************
 メールのタイトルの一覧を表示する関数
 
 引数
 $mailbox_id : メールボックスを示すID （0〜）
*************************/
function viewmail_msglist($account_no, $mailbox_id, $start_message_id, $flag_show_all_message, $thispage_filename)
{
	// imapサーバに接続する
	$imap = '';
	$arr_mail_account = array();
	if(!open_imap($arr_mail_account, $imap, $account_no)) return;

	// メールボックス一覧の取得
	$arr_mailbox = $imap->getMailboxes();
	if(PEAR::isError($arr_mailbox))
	{
		$imap->disconnect();
		print("<p>メールボックス一覧が取得できませんでした</p>\n");
		return;
	}

	if (!is_array($arr_mailbox))
	{
		$imap->disconnect();
		print("<p>メールボックス一覧が取得できませんでした</p>\n");
		return;
	}
	
	if(!strlen($arr_mailbox[$mailbox_id]))
	{
		$imap->disconnect();
		print("<p>存在しないメールボックスを指定しました</p>\n");
		return;
	}

	$str_mailbox_name = mb_convert_encoding($arr_mailbox[$mailbox_id], 'UTF-8', 'auto');
	$str_mailbox_name = htmlspecialchars($str_mailbox_name, ENT_QUOTES, 'UTF-8');
	printf("<p>保存フォルダ『%s』の記事一覧</p>\n", $str_mailbox_name);

	// 指定されたメールボックスを開く（指定する）
	if(PEAR::isError($imap->selectMailbox(mb_convert_encoding($arr_mailbox[$mailbox_id], 'UTF-8', 'auto'))))
	{
		$imap->disconnect();
		print("<p>メールボックスが開けません</p>\n");
		return;
	}

	// メール数を得る
	$n = $imap->getNumberOfMessages();
	if(PEAR::isError($n))
	{
		$imap->disconnect();
		print("<p>メールボックス内のメール数が取得できません</p>\n");
		return;
	}
	printf("<p>記事総数 %d</p>\n", $n);
	
	print("<p>");
	printf("<a href=\"%s?account=%d&amp;mbox=%d&amp;allid=1\">全て</a>&nbsp;\n", $thispage_filename, $account_no, $mailbox_id);
	for($i=$n; $i>=1; $i-=50)
	{
		printf("<a href=\"%s?account=%d&amp;mbox=%d&amp;startid=%d\">%d-%d</a>&nbsp;\n", $thispage_filename, $account_no, $mailbox_id, $i, $i, $i-50>0 ? $i-50:1 );
	}
	print("</p>\n");

	$displayed_message_cnt = 50;				// メール表示数
	if($flag_show_all_message) $displayed_message_cnt = $n;	// 全メール表示のときの処理

	if($start_message_id <=0 || $start_message_id > $n)
		$start_message_id = $n;

	printf("<table>\n");
	printf("<tr><td>ID</td><td>Date</td><td>From</td><td>Topic</td></tr>\n");
//	for($i=$start_message_id; $i<=$n && $i-$start_message_id<$displayed_message_cnt; $i++)
	for($i=$start_message_id; $i>=1 && $start_message_id-$i<=$displayed_message_cnt; $i--)
	{
		$header = $imap->getParsedHeaders($i);
		if(PEAR::isError($header))
		{	// ヘッダが読めなかった場合
			printf("<tr><td>%03d</td><td></td><td></td><td><a href=\"%s?account=%d&amp;mbox=%d&amp;msgid=%d\">記事名読み込みエラー</a></td></tr>\n", $i, $thispage_filename, $account_no, $mailbox_id, $i);
			continue;
		}

		$senddate = mb_decode_mimeheader($header['Date']);
		$senddate = htmlspecialchars($senddate);
		$arr_date_send = date_parse($senddate);

		$from = mb_decode_mimeheader($header['From']);
		$from = mb_convert_encoding($from, 'UTF-8', 'auto');
		$from = mb_strcut($from, 0, mb_strlen($from)>10? 10:mb_strlen($from)) . '...';
		$from = htmlspecialchars($from, ENT_QUOTES, 'UTF-8');

		$subj = mb_decode_mimeheader($header['Subject']);
		$subj = mb_convert_encoding($subj, 'UTF-8', 'auto');
		$subj = htmlspecialchars($subj, ENT_QUOTES, 'UTF-8');
		if(!strlen($subj)) $subj = '記事名が存在しない記事';
		
		printf("<tr><td>%03d</td><td>%04d/%02d/%02d</td><td>%s</td><td><a href=\"%s?account=%d&amp;mbox=%d&amp;msgid=%d\">%s</a></td></tr>\n", $i, $arr_date_send['year'], $arr_date_send['month'], $arr_date_send['day'], $from, $thispage_filename, $account_no, $mailbox_id, $i, $subj);
	}
	printf("</table>\n");

	// IMAPサーバから切断
	$imap->disconnect();

	return;
}


/*************************
 メール本文を表示する関数
 
 引数
 $mailbox_id : メールボックスを示すID （0〜）
 $message_id  : メールを示すID （1〜）
*************************/
function viewmail_readmsg($account_no, $mailbox_id, $message_id, $thispage_filename)
{
	// imapサーバに接続する
	$imap = '';
	$arr_mail_account = array();
	if(!open_imap($arr_mail_account, $imap, $account_no)) return;

	// メールボックス一覧の取得
	$arr_mailbox = $imap->getMailboxes();
	if(PEAR::isError($arr_mailbox))
	{
		$imap->disconnect();
		print("<p>メールボックス一覧が取得できませんでした</p>\n");
		return;
	}

	if (!is_array($arr_mailbox))
	{
		$imap->disconnect();
		print("<p>メールボックス一覧が取得できませんでした</p>\n");
		return;
	}
	
	if(!strlen($arr_mailbox[$mailbox_id]))
	{
		$imap->disconnect();
		print("<p>存在しないメールボックスを指定しました</p>\n");
		return;
	}

	// 指定されたメールボックスを開く（指定する）
	if(PEAR::isError($imap->selectMailbox(mb_convert_encoding($arr_mailbox[$mailbox_id], 'UTF-8', 'auto'))))
	{
		$imap->disconnect();
		print("<p>メールボックスが開けません</p>\n");
		return;
	}


	// メール数を得る
	$n = $imap->getNumberOfMessages();
	if(PEAR::isError($n))
	{
		$imap->disconnect();
		print("<p>メールボックス内のメール数が取得できません</p>\n");
		return;
	}

	if($message_id > $n)
	{
		printf("<p>警告：記事番号 %d が、総記事数 %d より大きい値です</p>\n", $message_id, $n);
	}

	$str_mailbox_name = mb_convert_encoding($arr_mailbox[$mailbox_id], 'UTF-8', 'auto');
	$str_mailbox_name = htmlspecialchars($str_mailbox_name, ENT_QUOTES, 'UTF-8');
	printf("<p>保存フォルダ：%s, 記事番号%d/%d</p>\n", $str_mailbox_name, $message_id, $n);


	$header = $imap->getParsedHeaders($message_id);
	if(PEAR::isError($header))
	{
		print("<p>記事名データ 読み込み不能</p>\n");
	}
	else
	{
		$senddate = mb_decode_mimeheader($header['Date']);
		$senddate = htmlspecialchars($senddate);
		$arr_date_send = date_parse($senddate);

		$from = mb_decode_mimeheader($header['From']);
		$from = mb_convert_encoding($from, 'UTF-8', 'auto');
		$from = htmlspecialchars($from, ENT_QUOTES, 'UTF-8');

		$subj = mb_decode_mimeheader($header['Subject']);
		$subj = mb_convert_encoding($subj, 'UTF-8', 'auto');
		$subj = htmlspecialchars($subj, ENT_QUOTES, 'UTF-8');
		if(!strlen($subj)) $subj = '記事名が存在しない記事';

		printf("<p>保存日 %04d/%02d/%02d  タイトル %s</p>\n", $arr_date_send['year'], $arr_date_send['month'], $arr_date_send['day'], $subj);
		printf("<p>差出人 %s</p>\n", $from);
	}

	// メール本文の読み込み
	$body = $imap->getBody($message_id);
	if(PEAR::isError($body))
	{
		print("<p>メール本文 読み込み不能</p>\n");
	}
	else
	{

		if(isset($_GET['decode']) && $_GET['decode'] === 'quotedprintable'){
			$body = mb_convert_encoding($body, 'UTF-8', 'Quoted-Printable');
		}
		$body = mb_convert_encoding($body, 'UTF-8', 'auto');
		$body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');

		print("<pre>\n");
		print($body);
		print("</pre>\n");
	}

	// IMAPサーバから切断
	$imap->disconnect();

	return;
}


?>
