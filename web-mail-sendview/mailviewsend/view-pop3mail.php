<?php

$str_version = '0.2';	// 画面に表示するバージョン
// ******************************************************
// Software name : View-Pop3Mail
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

// PEARの POP3コンポーネントを用いる
require_once("Net/POP3.php");

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
<title>PHP Mail (Net_POP3)</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body style="font-size:11pt; background-color:#e6e6e6">
<?php

printf("<p>メール閲覧（pop3版）システム Version %s</p>\n", $str_version);

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
if($account_no <= 0 || $account_no > count($arrAccountsPop3)) {
    printf("<p>アカウント指定Noが範囲外です<br/>account=%d</p>\n", $account_no);
    return false;
}

/** URLに与えられた引数により、表示モードを切り替える **/
if(isset($_GET['msgid']))
{	// メール番号が指定された場合
	if(!isset($_GET['decode'])){
		printf("<p><a href=\"%s?account=%d&amp;msgid=%d&amp;decode=quotedprintable\">Quoted-Printableをデコードする</a></p>\n", $thispage_filename, $account_no, $_GET['msgid']);
	}
	viewmail_readmsg($account_no, $_GET['msgid'], $thispage_filename);
}
else
{	// そのほかの場合は、タイトル一覧を表示
	$start_message_id = 0;
	$flag_show_all_message = false;
	if(isset($_GET['startid'])) $start_message_id = $_GET['startid'];
	if(isset($_GET['allid'])) $flag_show_all_message = true;
	viewmail_maillist($account_no, $start_message_id, $flag_show_all_message, $thispage_filename);
}

// リロード用リンクを表示する
printf("<p><a href=\"./%s?account=%d\">初期画面に戻る</a><br/>\n".
	"<a href=\"./index.php\">メール機能選択メニューを表示する</a></p>\n".
	"<p><a href=\"logoff.php\">ログオフ</a></p>\n".
	"</body>\n".
	"</html>\n", $thispage_filename, $account_no);

exit();

/*************************
 pop3接続を開始する関数

 戻り値：true(成功), false(失敗)
*************************/
function open_pop3(&$arr_mail_account, &$pop3, $account_no)
{
    // account list array, from config.php
    global $arrAccountsPop3;
    // $account_noの範囲チェック
    if($account_no <= 0 || $account_no > count($arrAccountsPop3)) {
        printf("<p>アカウント指定Noが範囲外です<br/>account=%d</p>\n", $account_no);
        return false;
    }
    // メールアカウント情報（サーバ名、ユーザ名、パスワード等）を得る
    $arr_mail_account = GetMailAccount($arrAccountsPop3[$account_no-1][1], 'pop3');

	if(!isset($arr_mail_account['server']) || !isset($arr_mail_account['user']) || !isset($arr_mail_account['password']) || strcmp($arr_mail_account['protocol'],'pop3'))
	{
		print("<p>メールアカウント管理エラー（server/user/password値が得られないか、protocolがpop3でない）</p>\n");
		return false;
	}

	// POP3サーバに接続する
	if(! $pop3->connect ($arr_mail_account['server'], $arr_mail_account['port'] ))
	{
		print("<p>POP3サーバへの接続に失敗</p>\n");
		return;
	}
	if(! $pop3->login( $arr_mail_account['user'], $arr_mail_account['password'], true ))
	{
		$pop3->disconnect();
		print("<p>POP3サーバへの接続に失敗</p>\n");
		return;
	}
	printf("<p>POP3サーバに接続（%s）</p>\n", htmlspecialchars($arr_mail_account['user']));

	return true;
}

/*************************
 メールアカウント一覧を表示する関数
*************************/
function viewmail_accountlist($thispage_filename)
{
    // account list array, from config.php
    global $arrAccountsPop3;

    for($i=0; $i<count($arrAccountsPop3); $i++) {
        printf("<p><a href=\"%s?account=%d\">%s</a></p>\n", $thispage_filename, $i+1, $arrAccountsPop3[$i][0]);
    }
    return;
}

/*************************
 メールのタイトルの一覧を表示する関数
 
 引数
 $nMboxId : メールボックスを示すID （0〜）
*************************/
function viewmail_maillist($account_no, $start_message_id, $flag_show_all_message, $thispage_filename)
{
	// pop3サーバに接続する
	$pop3 = new Net_POP3();
	$arr_mail_account = array();
	if(!open_pop3($arr_mail_account, $pop3, $account_no)) return;

	// メール数を得る
	$n = $pop3->numMsg();
	if($n == false)
	{
		$pop3->disconnect();
		print("<p>メールボックス内のメール数が取得できません</p>\n");
		return;
	}
	printf("<p>記事総数 %d</p>\n", $n);
	
	print("<p>");
	printf("<a href=\"%s?account=%d&amp;allid=1\">全て</a>&nbsp;\n", $thispage_filename, $account_no);
	for($i=$n; $i>=1; $i-=50)
	{
		printf("<a href=\"%s?account=%d&amp;startid=%d\">%d-%d</a>&nbsp;\n", $thispage_filename, $account_no, $i, $i, $i-50>0 ? $i-50:1 );
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
		$header = $pop3->getParsedHeaders($i);
		if(PEAR::isError($header))
		{	// ヘッダが読めなかった場合
			printf("<tr><td>%03d</td><td></td><td></td><td><a href=\"%s?account=%d&amp;msgid=%d\">記事名読み込みエラー</a></td></tr>\n", $i, $thispage_filename, $account_no, $i);
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
		
		printf("<tr><td>%03d</td><td>%04d/%02d/%02d</td><td>%s</td><td><a href=\"%s?account=%d&amp;msgid=%d\">%s</a></td></tr>\n", $i, $arr_date_send['year'], $arr_date_send['month'], $arr_date_send['day'], $from, $thispage_filename, $account_no, $i, $subj);
	}
	printf("</table>\n");

	// pop3サーバから切断
	$pop3->disconnect();

	return;
}


/*************************
 メール本文を表示する関数
 
 引数
 $nMboxId : メールボックスを示すID （0〜）
 $message_id  : メールを示すID （1〜）
*************************/
function viewmail_readmsg($account_no, $message_id, $thispage_filename)
{
	// pop3サーバに接続する
	$pop3 = new Net_POP3();
	$arr_mail_account = array();
	if(!open_pop3($arr_mail_account, $pop3, $account_no)) return;

	// メール数を得る
	$n = $pop3->numMsg();
	if($n == false)
	{
		$pop3->disconnect();
		print("<p>メールボックス内のメール数が取得できません</p>\n");
		return;
	}

	if($message_id > $n)
	{
		printf("<p>警告：記事番号 %d が、総記事数 %d より大きい値です</p>\n", $message_id, $n);
	}

	printf("<p>記事番号%d/%d</p>\n", $message_id, $n);

	$header = $pop3->getParsedHeaders($message_id);
	if($header == false)
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
	$body = $pop3->getBody($message_id);
	if($body == false)
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

	// POP3サーバから切断
	$pop3->disconnect();

	return;
}


?>

</body>
</html>

