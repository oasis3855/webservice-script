<?php

$str_version = '0.3';    // 画面に表示するバージョン
// ******************************************************
// Software name : Sendmail-SMTP
//
// Copyright (C) INOUE Hirokazu, All Rights Reserved
//   http://oasis.halfmoon.jp/
//
// version 0.1 (2009/04/29)
// version 0.2 (2012/03/07)
// version 0.3 (2013/08/07)
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
$info=posix_getpwuid(posix_geteuid());      // get user HOME dir
ini_set('include_path', $info['dir'].'/pear/pear/php' . PATH_SEPARATOR . ini_get('include_path'));

// PEARの Net/SMTPコンポーネントを用いる
require_once("Net/SMTP.php");

// 設定ファイルより利用するメールアカウント一覧を読み込む
require_once('./config.php');

// メールアカウント管理コンポーネントを用いる
require_once($info['dir'].'/auth/script/mail_account.php');

// 言語と文字コードの設定
mb_language('Japanese');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// このページのファイル名（リロード用）
$thispage_filename = htmlspecialchars(basename($_SERVER['PHP_SELF']));

global $strNewsSendRcpt;

?><!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja-JP" xml:lang="ja-JP">
<head>
<title>ニュース送信 (Net_SMTP)</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body style="font-size:11pt; background-color:#e6e6e6">
<?php

printf("<p>ニュース送信（smtp版）システム Version %s</p>\n", $str_version);


if(!isset($_POST['subject']) || !strlen($_POST['subject']) ||
    !isset($_POST['message']) || !strlen($_POST['message']) ||
    !isset($_POST['addr_to']) || !strlen($_POST['addr_to']) ||
    !isset($_POST['fromname']) || !strlen($_POST['fromname']))
{   // パラメータに何も指定されなかったとき

    // SMTPアカウントの選択を行うセレクトボックス
    $html_selectbox_account = "<select name=\"account_no\">";
    for($i=1; $i<=count($arrAccountsSmtp); $i++) {
        $html_selectbox_account .= "<option value=\"".$i."\">".$arrAccountsSmtp[$i-1][0]."</option>";
    }
    $html_selectbox_account .= "</select>";

    // タイトル転送方式の選択を行うセレクトボックス
    $html_selectbox_subjtransfer = "<select name=\"subj_transfer\">".
                "<option value=\"mime-base64\">mime/base64</option>".
                "<option value=\"mime-quoted-printable\">mime/quoted-printable</option>".
                "<option value=\"plain\">エンコード無し</option>".
                "</select>";

    // 文字エンコードの選択を行うセレクトボックス
    $html_selectbox_msgencode = "<select name=\"msg_encode\">".
                "<option value=\"ISO-2022-JP\">ISO-2022-JP</option>".
                "<option value=\"UTF-8\">UTF-8</option>".
                "<option value=\"SJIS\">SJIS</option>".
                "<option value=\"EUC-JP\">EUC-JP</option>".
                "</select>";

    // 本文転送方式の選択を行うセレクトボックス (RFC2045)
    $html_selectbox_msgtransfer = "<select name=\"msg_transfer\">".
                "<option value=\"7bit\">7bit</option>".
                "<option value=\"8bit\">8bit (*)</option>".
                "<option value=\"quoted-printable\">quoted-printable</option>".
                "<option value=\"base64\">base64</option>".
                "<option value=\"binary\">binary (*)</option>".
                "</select>";

    // ニュース送信時 タイトルに付加する種別文字列を選択するラジオボタン
    $html_radio_newstilte = "<input type=\"radio\" name=\"type\" value=\"news\" checked=\"checked\" />news&nbsp;".
                "<input type=\"radio\" name=\"type\" value=\"tech\" />tech&nbsp;".
                "<input type=\"radio\" name=\"type\" value=\"linux\" />linux&nbsp;".
                "<input type=\"radio\" name=\"type\" value=\"win\" />win&nbsp;".
                "<input type=\"radio\" name=\"type\" value=\"pc\" />pc&nbsp;".
                "<input type=\"radio\" name=\"type\" value=\"none\" />無選択&nbsp;";

    /* 投稿フォームを表示する */
    global $strDefaultSenderName;
    printf("<form method=\"post\" action=\"./$thispage_filename\" name=\"form1\">\n".
        "\t<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" align=\"center\" width=\"700px\">\n".
        "\t<tr><td colspan=\"2\"><strong>送信内容</strong></td></tr>\n".

        "\t<tr style=\"display:none\"><td width=\"50px\">From</td><td><input name=\"fromname\" value=\"%s\" size=\"25\"  type=\"hidden\"/> %s</td></tr>\n".

        "\t<tr style=\"display:none\"><td width=\"50px\">To</td><td><input name=\"addr_to\" size=\"80\" value=\"".$strNewsSendRcpt."\"  type=\"hidden\" /></td></tr>\n".
    
        "\t<tr><td width=\"50px\">Subject</td><td><input name=\"subject\" size=\"80\" /></td></tr>\n".
        "\t<tr><td width=\"50px\"></td><td>%s</td></tr>\n".

    
        "\t<tr><td>本文</td><td><textarea name=\"message\" cols=\"80\" rows=\"20\" style=\"font-size:10pt;\"></textarea></td></tr>\n".
    
        "\t<tr><td></td><td><input type=\"checkbox\" name=\"linewrap_flag\" value=\"true\" checked=\"checked\" />本文を78桁で改行する <input type=\"checkbox\" name=\"debug_flag\" value=\"true\" />SMTPデバッグ出力を行う</td></tr>\n".
        "\t<tr><td></td><td>Subject transfer encoding%s</td></tr>\n".
        "\t<tr><td><input type=\"submit\" value=\"  送信  \" /></td><td>Content-Type charset%s  Content-Transfer-Encoding%s</td></tr>\n".
        "\t</table>\n".
        "</form>\n", $strDefaultSenderName, $html_selectbox_account, $html_radio_newstilte,
        $html_selectbox_subjtransfer, $html_selectbox_msgencode, $html_selectbox_msgtransfer);
}
else
{

    $debug_flag = false;
    if(isset($_POST['debug_flag'])){
        $debug_flag = true;
        print("<p>SMTP デバッグメッセージ表示 : ON</p>\n");
    }

    $msg = stripslashes($_POST['message']);

    if(isset($_POST['linewrap_flag'])){
        str_line_wrap($msg, 78);
        print("<p>本文の78桁での自動改行 : ON</p>\n");
    }

    $subject = stripslashes($_POST['subject']);

    // Subject（タイトル）文字列の「特殊空白文字」を半角空白1文字に置換する
    $str_match = "/[".pack('CCC', 0xe2, 0x80, 0x80).pack('CCC', 0xe2, 0x80, 0x81).
                pack('CCC', 0xe2, 0x80, 0x82).pack('CCC', 0xe2, 0x80, 0x83).
                pack('CCC', 0xe2, 0x80, 0x84).pack('CCC', 0xe2, 0x80, 0x85).
                pack('CCC', 0xe2, 0x80, 0x86).pack('CCC', 0xe2, 0x80, 0x87).
                pack('CCC', 0xe2, 0x80, 0x88).pack('CCC', 0xe2, 0x80, 0x89)."]+/u";
    $subject = preg_replace($str_match, " ", $subject);

    // 本文文字列の「特殊空白文字」を半角空白1文字に置換する
    $msg = preg_replace($str_match, " ", $msg);

    // Subject（タイトル）文字列の先頭・末尾の半角・全角スペースを取り除く
    $subject = preg_replace("/^[\s]+/u", "", $subject);
    $subject = preg_replace("/[\s]+$/u", "", $subject);

    sendmail_sendmsg(intval($_POST['account_no']), stripslashes($_POST['subject']), $strNewsSendRcpt,
            stripslashes($_POST['fromname']), $msg, $debug_flag);

}

// リロード用リンクを表示する
printf("<p><a href=\"./%s\">初期画面に戻る</a></p>\n".
    "</body>\n".
    "</html>\n", $thispage_filename);

exit();

/*************************
 smtp接続を開始する関数

 戻り値：true(成功), false(失敗)
*************************/
function open_smtp(&$arr_mail_account, &$smtp, $account_no, $debug_flag)
{
    // account list array, from config.php
    global $arrAccountsSmtp;
    global $strNewsSendRcpt;
    // $account_noの範囲チェック
    if($account_no <= 0 || $account_no > count($arrAccountsSmtp)) {
        printf("<p>アカウント指定Noが範囲外です<br/>account=%d</p>\n", $account_no);
        return false;
    }
    // メールアカウント情報（サーバ名、ユーザ名、パスワード等）を得る
    $arr_mail_account = GetMailAccount($arrAccountsSmtp[$account_no-1][1], 'smtp');

    if(!isset($arr_mail_account['server']) || !isset($arr_mail_account['user']) || !isset($arr_mail_account['password']) || strcmp($arr_mail_account['protocol'],'smtp'))
    {
        print("<p>メールアカウント管理エラー（server/user/password値が得られないか、protocolがsmtpでない）</p>\n");
        return false;
    }

    // 新しい Net_SMTP オブジェクトを作成します
    if (! ($smtp = new Net_SMTP($arr_mail_account['server'], $arr_mail_account['port']))) {
        print("<p>ネットワーク異常（SMTPオブジェクト作成不能）</p>");
        return false;
    }

    // デバッグ出力を、stdoutに送る
    if($debug_flag) $smtp->setDebug(true);

    // SMTP サーバに接続します
    if (PEAR::isError($e = $smtp->connect())) {
        print("<p>ネットワーク異常（SMTPでコネクション不能）</p>");
        return false;
    }

    // ユーザ名、パスワードを用いてSMTPログオンします
    // （Digest-MD5, CRAMMD5, LOGIN, PLAINの順に暗号強度の高いもので接続します）
    if (PEAR::isError($e = $smtp->auth($arr_mail_account['user'], $arr_mail_account['password']))) {
        $smtp->disconnect();
        print("<p>SMTPサーバの認証に失敗</p>");
        return false;
    }

//    printf("<p>接続：%s@%s:%s</p>\n", $arr_mail_account['user'], $arr_mail_account['server'], $arr_mail_account['port']);

    return true;
}

/*************************
 メール送信を行う関数

 戻り値：なし
*************************/
function sendmail_sendmsg($account_no, $subject, $rcpt, $fromname, $msg, $debug_flag)
{
    // smtpサーバに接続する
    $smtp = '';
    $arr_mail_account = array();
    if(!open_smtp($arr_mail_account, $smtp, $account_no, $debug_flag)) return;

    // 送信者のアドレス（user@example.com）の文字列を作成する
    $from = '';
    if(strpos($arr_mail_account['user'], '@') === false) {
        $from = $arr_mail_account['user'] . '@' . $arr_mail_account['server'];
    }
    else {
        $from = $arr_mail_account['user'];
    }

    // SMTP コマンド 'MAIL FROM:' を送信します
    if (PEAR::isError($smtp->mailFrom($from))) {
        $smtp->disconnect();
        print("<p>送信者アドレスがSMTPサーバで許可されませんでした</p>");
        return;
    }

    // 受信者のアドレスを指定します
    if (PEAR::isError($res = $smtp->rcptTo($rcpt))) {
        $smtp->disconnect();
        print("<p>受信者アドレスがSMTPサーバで許可されませんでした</p>");
        return;
    }

    // タイトル(Subject)にニュース送信用の種別文字列を付け加える
    switch($_POST['type'])
    {
        case 'news': $subject = '[news] '.$subject; break;
        case 'tech': $subject = '[tech] '.$subject; break;
        case 'linux': $subject = '[linux] '.$subject; break;
        case 'win': $subject = '[win] '.$subject; break;
        case 'pc': $subject = '[pc] '.$subject; break;
    }

    // メールの本文を作成します
    $str_smtp_data = "User-Agent: PHP Pear Net_SMTP\n";
    $str_smtp_data .= "To: ".$rcpt."\n";

    // 送信者名(From),タイトル(Subject)をエンコードする
    $internal_charset_save = mb_internal_encoding();        // 現在の内部エンコードを一旦保存
    switch($_POST['subj_transfer']) {
        case 'mime-base64' :
                    mb_internal_encoding($_POST['msg_encode']);
                    // 送信者名（From）
                    $str_smtp_data .= 'From: ' . mb_encode_mimeheader(mb_convert_encoding($fromname,
                        $_POST['msg_encode'], 'UTF-8'), $_POST['msg_encode'], 'B')."<".$from.">\n";
                    // タイトル（Subject）
                    $str_smtp_data .= 'Subject: ' . mb_encode_mimeheader(mb_convert_encoding($subject,
                        $_POST['msg_encode'], 'UTF-8'), $_POST['msg_encode'], 'B') . "\n";
                    break;
        case 'mime-quoted-printable' : 
                    mb_internal_encoding($_POST['msg_encode']);
                    // 送信者名（From）
                    $str_smtp_data .= 'From: ' . mb_encode_mimeheader(mb_convert_encoding($fromname,
                        $_POST['msg_encode'], 'UTF-8'), $_POST['msg_encode'], 'Q')."<".$from.">\n";
                    // タイトル（Subject）
                    $str_smtp_data .= 'Subject: ' . mb_encode_mimeheader(mb_convert_encoding($subject,
                        $_POST['msg_encode'], 'UTF-8'), $_POST['msg_encode'], 'Q') . "\n";
                    break;
        case 'plain' :
                    $str_smtp_data .= 'From: ' . mb_convert_encoding($fromname,
                     $_POST['msg_encode'], 'UTF-8')."<".$from.">\n";
                    $str_smtp_data .= 'Subject: ' . mb_convert_encoding($subject, $_POST['msg_encode'],
                        'UTF-8') . "\n";
        default :
                    printf("<p>送信者名(From)とタイトル(Subject)転送方式の指定が範囲外 (value=%s)</p>", htmlspecialchars($_POST['subj_transfer']));
                    return;
    }
    mb_internal_encoding($internal_charset_save);
    $str_smtp_data .= "Content-Transfer-Encoding: ".$_POST['msg_transfer']."\n";

    // 本文のエンコード
    $str_temp = '';
    switch($_POST['msg_encode']) {
        case 'ISO-2022-JP' : 
            $str_smtp_data .= "Content-Type: text/plain; charset=iso-2022-jp\n"."\n\n";
            $str_temp = mb_convert_encoding($msg, 'ISO-2022-JP', 'UTF-8');
            break;
        case 'UTF-8' : 
            $str_smtp_data .= "Content-Type: text/plain; charset=utf8\n"."\n\n";
            //$str_temp = mb_convert_encoding($msg, 'UTF-8', 'UTF-8');
            $str_temp = $msg;
            break;
        case 'SJIS' : 
            $str_smtp_data .= "Content-Type: text/plain; charset=shift-jis\n"."\n\n";
            $str_temp = mb_convert_encoding($msg, 'SJIS', 'UTF-8');
            break;
        case 'EUC-JP' : 
            $str_smtp_data .= "Content-Type: text/plain; charset=euc-jp\n"."\n\n";
            $str_temp = mb_convert_encoding($msg, 'EUC-JP', 'UTF-8');
            break;
        default :
            $smtp->disconnect();
            printf("<p>本文エンコード形式の指定が範囲外 (value=%s)</p>", htmlspecialchars($_POST['msg_encode']));
            return;
    }

    $str_temp .= "\n";    // 本文の後ろに改行1個付ける

    printf("<p>%s</p>\n", htmlspecialchars($subject));

    switch($_POST['msg_transfer']) {
        case '7bit' : break;
        case '8bit' : break;
        case 'quoted-printable' : quoted_printable_encode_self($str_temp, $_POST['msg_transfer']); break;
        case 'base64' : $str_temp = base64_encode($str_temp); break;
        case 'binay' : break;
        default :
            $smtp->disconnect();
            printf("<p>本文転送形式の指定が範囲外 (value=%s)</p>", htmlspecialchars($_POST['msg_transfer']));
            return;
    }
    $str_smtp_data .= $str_temp . "\n\n";

    if (PEAR::isError($smtp->data($str_smtp_data))) {
        print("<p>本文の送信に失敗</p>");
    }
    else
    {
        printf("<p>送信完了<br />%d Bytes</p>\n", strlen($str_smtp_data));
    }

    // SMTP サーバとの接続を切断します
    $smtp->disconnect();

}

/*************************
 文字列を指定した桁数で折り返す関数

 引数 : $string=処理対象の文字列（参照形式）, $width=折り返し桁数
 戻り値：なし （処理後の文字列は、参照形式の引数$stringに再格納される）
*************************/
function str_line_wrap(&$string, $width)
{
    $break = "\n";    // 改行コードの定義

    // Step 1：original line-wrap process for ASCII string
    $para = mb_split("\n", $string);
    $string = '';
    while (count($para)) {
        $line = array_shift($para);
        // skip, if newline only
        if(mb_strlen($line, 'UTF-8') == 0) {
            $string .= $break;
            continue;
        }
        // skip line-wrap, if $line is double-width character (CJK) string
        if(mb_strlen($line, 'UTF-8') != mb_strwidth($line, 'UTF-8')) {
            $string .= $line;
            $string .= $break;
            continue;
        }
        $in_quote = false;
        // quoted line detect
        if ($line[0] == '>') $in_quote = true;

        $list = explode(' ', $line);
        $len = 0;
        while (count($list)) {
            $line = array_shift($list);
            $l = mb_strlen($line, 'UTF-8');
            $newlen = $len + $l + ($len ? 1 : 0);

            if ($newlen <= $width) {
                $string .= ($len ? ' ' : '').$line;
                $len += (1 + $l);
            } else {
                if ($l > $width) {
                    if ($cut) {
                        $start = 0;
                        while ($l) {
                            $str = mb_substr($line, $start, $width, 'UTF-8');
                            $strlen = mb_strlen($str, 'UTF-8');
                            $string .= ($len ? $break.'>' : '').$str;
                            $start += $strlen;
                            $l -= $strlen;
                            $len = $strlen;
                        }
                    } else {
                        $string .= ($len ? $break.'>' : '').$line;
                        if (count($list)) $string .= $break;
                        $len = 0;
                    }
                } else {
                    if($in_quote) $string .= $break.'>'.$line;
                    else $string .= $break.$line;
                    $len = $l;
                }
            }
        }
        if (count($para)) $string .= $break;
    }

    // Step 2：line-wrap process for non ASCII double-width character (CJK) string
    $para = explode($break, $string);
    $string = '';
    while (count($para)) {
        $line = array_shift($para);
        // skip, if newline only
        if(mb_strlen($line, 'UTF-8') == 0) {
            $string .= $break;
            continue;
        }
        // skip, if ASCII (single-width char) string
        elseif(mb_strlen($line, 'UTF-8') == mb_strwidth($line, 'UTF-8')) {
            $string .= $line;
            $string .= $break;
            continue;
        }
        $in_quote = false;
        // quoted line detect
        if ($line[0] == '>' || mb_substr($line,0,1,'UTF-8') == '＞') {
            // strip quote char '>'
            $line = mb_substr($line, 1, mb_strlen($line, 'UTF-8') - 1, 'UTF-8');
            $in_quote = true;
        }

        $line_part = "";
        $len = 0;
        for($i=0; $i<mb_strlen($line, 'UTF-8'); $i++)
        {
            $char= mb_substr($line, $i, 1, 'UTF-8');
            $line_part .= $char;
            if($char == "\n")
            {
                $len = 0;
            }
            $len += mb_strwidth($char, 'UTF-8'); //==1?1:2;  // 切り出された文字のバイト数
            if($len >= $width)
            {
                $len=0;
                if($in_quote) $string .= '>';
                $string .= $line_part.$break;
                $line_part = '';
            }
        }
        if($in_quote) $string .= '>';
        $string .= $line_part.$break;
    }

}

// エンコード関数
// $encode には変換前文字列の'SJIS', 'UTF8', 'EUC', 'ISO-2022-JP' など文字コードを指定
function quoted_printable_encode_self(&$str, $encode)
{
    // 利用した関数名
    $strFuncName = '';

    if(function_exists('quoted_printable_encode'))
    {
        $str = quoted_printable_encode($str);
    }
    else if(function_exists('imap_8bit'))
    {
        $str = imap_8bit($str);
    }
    else
    {
        $arrEncodeSupport = mb_list_encodings();
        if(array_search('Quoted-Printable', $arrEncodeSupport) != FALSE)
        {
            $str = mb_convert_encoding($str, 'Quoted-Printable', $encode);
        }
        else
        {
            $str = quoted_printable_encode_internalfunc($str);
        }
    }

    return;
}

// エンコード関数（PHPの関数を使わないバージョン）
function quoted_printable_encode_internalfunc($str)
{
    $crlf="\r\n";

    $str=trim($str);

    $lines = preg_split("/(\r\n|\n|\r)/s", $str);
    $out = '';
    $temp = '';
    foreach ($lines as $line)
    {
        for ($j = 0; $j < strlen($line); $j++)
    {
        $char = substr ( $line, $j, 1 );
        $ascii = ord ( $char );

        if ( $ascii < 32 || $ascii == 61 || $ascii > 126 )
        {
            $char = '=' . strtoupper ( dechex( $ascii ) );
        }

        if ( ( strlen ( $temp ) + strlen ( $char ) ) >= 76 )
        {
            $out .= $temp . '=' . $crlf; $temp = '';
        }
        $temp .= $char;
        }
    }
    $out .= $temp;

    return trim ( $out );
}

?>
