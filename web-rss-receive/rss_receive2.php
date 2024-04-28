<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="ja" />
    <!-- disable viewport for mobile device -->
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=2.0,minimum-scale=1.0,user-scalable=1" />
<!--    <link rel="stylesheet" href="rss_receive.css" type="text/css" />    -->
<?php
print_header_stylesheet()
?>

    <title> </title>
</head>
<body>

<?php

//************************************************
// Software name : rss_receive.php      version 1.01 (2009/11/19)
//                                      version 1.1  (2014/09/15)
//                                      version 1.11 (2021/02/17) PHP7対応
//                                      version 1.2  (2024/04/27) ダークテーマ
// Copyright (C) 2009 INOUE Hirokazu
// All Rights Reserved
//
//ソースコード形式かバイナリ形式か、変更するかしないかを問わず、以下の条件を満
//たす場合に限り、再頒布および使用が許可されます。
//
//* ソースコードを再頒布する場合、上記の著作権表示、本条件一覧、および下記免責
//条項を含めること。
//* バイナリ形式で再頒布する場合、頒布物に付属のドキュメント等の資料に、上記の
//著作権表示、本条件一覧、および下記免責条項を含めること。
//* 書面による特別の許可なしに、本ソフトウェアから派生した製品の宣伝または販売
//促進に、<組織>の名前またはコントリビューターの名前を使用してはならない。
//
//本ソフトウェアは、著作権者およびコントリビューターによって「現状のまま」提供
//されており、明示黙示を問わず、商業的な使用可能性、および特定の目的に対する適
//合性に関する暗黙の保証も含め、またそれに限定されない、いかなる保証もありませ
//ん。著作権者もコントリビューターも、事由のいかんを問わず、損害発生の原因いか
//んを問わず、かつ責任の根拠が契約であるか厳格責任であるか（過失その他の）不法
//行為であるかを問わず、仮にそのような損害が発生する可能性を知らされていたとし
//ても、本ソフトウェアの使用によって発生した（代替品または代用サービスの調達、
//使用の喪失、データの喪失、利益の喪失、業務の中断も含め、またそれに限定されな
//い）直接損害、間接損害、偶発的な損害、特別損害、懲罰的損害、または結果損害に
//ついて、一切責任を負わないものとします。
//
//（このライセンスはBSDライセンスの日本語訳を用いています）
// http://sourceforge.jp/projects/opensource/wiki/licenses%2Fnew_BSD_license
//************************************************


//************************************************
// エラー ハンドラ関数（エラー発生時にメッセージを表示する）
//************************************************
function myErrorHandler($errno, $errstr, $errfile, $errline)
{
//  echo "**** ERROR（".$errfile."Line=".$errline."）".$errstr."****\n";
    echo "**** ERROR ****\n";
}


//************************************************
// プログラム開始
//************************************************
set_error_handler("myErrorHandler");


// 言語と文字コードの設定
mb_language('Japanese');
mb_internal_encoding('SJIS');
mb_http_output('UTF-8');

// use user's home directory PEAR
//ini_set('include_path', '/home/*****USERHOME*****/pear/pear/php' . PATH_SEPARATOR . ini_get('include_path'));
ini_set('include_path', posix_getpwuid(posix_geteuid())['dir'] . '/pear/pear/php' . PATH_SEPARATOR . ini_get('include_path'));

// PearのNet_IMAPを利用する
require_once('XML/Feed/Parser.php');

// 日時関数でエラーが出るのを抑止
date_default_timezone_set('Asia/Tokyo');

$dcurl = 'http://purl.org/dc/elements/1.1/';

// このページのファイル名（リロード用）
//$strReloadPage = 'rss_receive2.php';
$strReloadPage = basename(__FILE__);

print("<p>RSS受信（Pear XML_Feed_Parser)</p>\n");

// 受信するRSSのURL（テスト用）
//$uri = 'http://rss.rssad.jp/rss/wiredvision/feed/atom.xml';       // Atom
//$uri = 'http://feed.nikkeibp.co.jp/rss/nikkeibp/it.rdf';      // RSS1
//$uri = 'http://rss.rssad.jp/rss/itmatmarkit/rss.xml';         // RSS2.0
//$uri = 'http://rss.rssad.jp/rss/itmatmarkit/rss091.xml';      // RSS0.91

if (isset($_GET['rssuri']) && !empty($_GET['rssuri'])) {
    $uri = $_GET['rssuri'];
    // URIとしてありえない文字をurlencodeする（Linuxシェルで誤認される文字を除去）
    $uri = str_replace("|", "%7C", $uri);
    $uri = str_replace("\\", "%5C", $uri);
    $uri = str_replace(">", "%3E", $uri);
    $uri = str_replace("<", "%3C", $uri);
    $uri = str_replace("#", "%23", $uri);
    // URI先頭がhttpまたはhttpsでない場合は、httpを付加（ファイルシステムへアクセスされるのを防ぐため）
    if (substr($uri, 0, 7) != 'http://' && substr($uri, 0, 8) != 'https://') {
        $uri = 'http://' . $uri;
    }

    read_rss($uri);
} elseif (isset($_GET['editconfig']) && !empty($_GET['editconfig'])) {
    if ($_GET['editconfig'] == 'view') {
        edit_config_file($strReloadPage, 0, '', '');
    } elseif ($_GET['editconfig'] == 'save') {
        edit_config_file($strReloadPage, 1, $_POST['config'], $_POST['password']);
    } else {
        print("<p>コマンドパラメータ（editconfig）が誤っています</p>\n");
    }
} elseif (isset($_GET['showall']) && !empty($_GET['showall'])) {
    read_all_rss();
} else {
    display_rssuri_list($strReloadPage);
}

print("<p><a href=\"./$strReloadPage\">RSS一覧画面を再表示する</a>&nbsp;&nbsp;|&nbsp;&nbsp;\n" .
    "<a href=\"./$strReloadPage?showall=true\">全RSSサイト巡回一括表示</a>&nbsp;&nbsp;|&nbsp;&nbsp;\n" .
    "<a href=\"./$strReloadPage?editconfig=view\">RSSサイト設定ファイルを編集</a></p>\n" .
    "</body>\n</html>\n");

// プログラム終了
exit();

//************************************************
// head出力でスタイルシートを切り替える関数
//************************************************
function print_header_stylesheet()
{
    $nSwTopicCount = 10;    // 表示する記事数
    $nSwDescription = 1;    // 本文を表示するかどうか
    $nSwPrevDays = 1;       // 過去1日分を表示
    $nSwDarktheme = 0;      // 1:ダークテーマ、0:ライトテーマ

    // 設定ファイルより設定を読み込む
    read_config($nSwTopicCount, $nSwDescription, $nSwPrevDays, $nSwDarktheme);

    if ($nSwDarktheme == 1) {
        print("<link rel=\"stylesheet\" href=\"rss_receive_dark.css\" type=\"text/css\" />");
    } else {
        print("<link rel=\"stylesheet\" href=\"rss_receive.css\" type=\"text/css\" />");
    }
}


//************************************************
// RSSサイトの一覧を表示する関数
//************************************************
function display_rssuri_list($strReloadPage)
{
    $nLine = 0;
    $handle = @fopen("./rss_receive.ini", "r");
    if ($handle) {
        print("<ul>\n");
        while (!feof($handle)) {
            $nLine++;

            // ファイルよりURIリストを読み込む
            $strLine = htmlspecialchars(fgets($handle));
            if ($nLine <= 1) {
                continue;   // 1行目は設定リスト行（読み飛ばす）
            }
            if (strlen($strLine) < 10) {
                continue; // URIが10文字以下はありえない。
            }
            // URIとタイトルを、『,』で切り分ける
            $arrUri = explode(',', $strLine, 2);
            // 『,』以降が無い場合、タイトルはURIと同じとする
            if (count($arrUri) <= 1 || empty($arrUri[1])) {
                $strTitle = $arrUri[0];
            } else {
                $strTitle = $arrUri[1];
            }

            printf(
                "<li class=\"mainmenu\"><a href=\"%s?rssuri=%s\">%s</a></li>\n",
                $strReloadPage,
                $arrUri[0],
                $strTitle
            );
        }
        print("</ul>\n");
        fclose($handle);
    } else {
        print("<p>設定ファイルを読み込めません<p>\n");
    }
}



//************************************************
// 全てのRSSサイトを巡回して表示する関数
//************************************************
function read_all_rss()
{
    $arrLines = @file("./rss_receive.ini", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES | FILE_TEXT);

    $nLine = 0;     // 現在の処理行数
    foreach ($arrLines as $item) {
        $nLine++;
        if ($nLine <= 1) {   // 1行目は設定リスト行のため、そのまま書き込む
            continue;
        }

        // URIとタイトルを、『,』で切り分ける
        $arrUri = explode(',', $item, 2);

        // URIの末尾にある改行コードを取り除く
        $uri = trim($arrUri[0]);

        read_rss($uri);
    }
}


//************************************************
// RSSサイトを表示する関数
//************************************************
function read_rss($uri)
{
    $strReturn = '';
    $nSwTopicCount = 10;    // 表示する記事数
    $nSwDescription = 1;    // 本文を表示するかどうか
    $nSwPrevDays = 1;       // 過去1日分を表示
    $nSwDarktheme = 0;      // 1:ダークテーマ、0:ライトテーマ

    // 設定ファイルより設定を読み込む
    read_config($nSwTopicCount, $nSwDescription, $nSwPrevDays, $nSwDarktheme);

    // RSSファイルの受信
    $strRssSource  = file_get_contents($uri);
    if ($strRssSource == false) {
        print("<p>RSSファイル読み込み失敗</p>\n");
        return $strReturn;
    }

    // XML_RSSクラスの初期化と、受信
    $rss = new XML_Feed_Parser($strRssSource);

    // RSSサイトのタイトル行を表示
    printf(
        "<h2><span class=\"head\">%s</span> (%s) <a target=\"_blank\" href=\"%s\">%s</a></h2>\n",
        trim($rss->title),
        $rss->__get('version'),
        $uri,
        $uri
    );

    $i = 0;
    print("<ul>\n");
    foreach ($rss as $item) {    // データの取り出し
        $strTitle = $item->title;
        $strLink = $item->link;
        $strDescription = $item->description;
        $strContent = $item->content;

        $strDate = $item->date;         // RSS 1, 1.1
        if ($strDate == null) {
            $strDate = $item->published;     // atom
        }
        if ($strDate == null) {
            $strDate = $item->pubDate;       // RSS 2
        }
        if ($strDate == null) {
            $strDate = $item->updated;       // RSS 1, atom
        }
        $arrDate = getdate($strDate);

        if ($strDate != null && time() - $strDate > $nSwPrevDays * 24 * 3600 + 1) {
            continue;   // 指定期間より古い
        }

        // RSS項目を表示（日時と、記事へのリンク）
        printf(
            "  <li><span class=\"date\">%04d/%02d/%02d %02d:%02d</span> <a target=\"_blank\" href=\"%s\">%s</a></li>\n",
            $arrDate['year'],
            $arrDate['mon'],
            $arrDate['mday'],
            $arrDate['hours'],
            $arrDate['minutes'],
            htmlspecialchars($strLink),
            htmlspecialchars($strTitle)
        );

        // RSS要約を表示
        if ($nSwDescription == 1) {
            $strTemp = "";
            // RSSサイトにより$strDescriptionまたは$strContentに要約が格納されている差異を吸収する
            if (!empty($strDescription)) {
                $strTemp = $strDescription;
            } else {
                $strTemp = $strContent;
            }
            // 長い文章の場合、途中できる処理をしてから画面表示する
            $strTemp = strip_tags($strTemp);
            if (mb_strlen($strTemp, "utf-8") > 400) {
                $strTemp = mb_substr($strTemp, 0, 400, "utf-8");
                printf("  <li class=\"contents\">%s <span style=\"color:red;\">...</span></li>\n", htmlspecialchars($strTemp));
            } else {
                printf("  <li class=\"contents\">%s</li>\n", htmlspecialchars($strTemp));
            }
        }

        $i++;
        if ($i >= $nSwTopicCount) {
            break;   // 指定されたトピック数のみ表示
        }
    }

    print("</ul>\n");

    return $strReturn;
}

//************************************************
// 設定ファイルを編集する関数
//************************************************
function edit_config_file($strReloadPage, $nMode, $strConfig, $strPassword)
{
    // 設定ファイル保存時のパスワード（SHA-1）
    $strMyPassword = 'aa18a4a6d6360f3b9c1ba6b1520685057a8935c9';

    $strLine = '';

    if ($nMode == 0) {   // 編集画面を開く（iniファイルを読み込んで、入力画面に表示）
        $handle = @fopen("./rss_receive.ini", "r");
        if ($handle) {
            while (!feof($handle)) {
                $strLine .= htmlspecialchars(fgets($handle));
            }
            fclose($handle);
        } else {
            print("<p>設定ファイルを読み込めません<p>\n");
        }

        print("<form method=\"post\" action=\"./$strReloadPage?editconfig=save\" name=\"form\">\n");
        print("<table>\n");
        print("\t<tr><td>設定</td><td><textarea name=\"config\" cols=\"80\" rows=\"20\" style=\"font-size:10pt;\">" . $strLine . "</textarea></td></tr>\n");
        print("\t<tr><td>パスワード</td><td><input name=\"password\" size=\"30\" type=\"password\" /></td></tr>\n");

        print("\t<tr><td></td><td><input type=\"submit\" value=\"送信\" /></td></tr>\n");
        print("</table>\n");

        print("</form>\n");
    } elseif ($nMode == 1) {   // 編集内容を、設定ファイルに書き込む
        if (sha1($strPassword) != $strMyPassword) {
            print("<p>パスワードが違います</p>\n");
            return;
        }

        $handle = @fopen("./rss_receive.ini", "w");
        if ($handle) {
            fwrite($handle, $strConfig, strlen($strConfig));
            fclose($handle);
            print("<p>下記内容を保存しました</p>\n<pre>\n" . $strConfig . "\n</pre>\n");
        } else {
            print("<p>ファイルに書き込めませんでした</p>\n");
        }
    } else {
        print("<p>edit_config_file関数へのパラメータが違います</p>\n");
    }
}

//************************************************
// 設定ファイルの1行目から、基本設定項目を読み込む関数
//************************************************
function read_config(&$nSwTopicCount, &$nSwDescription, &$nSwPrevDays, &$nSwDarktheme)
{
    $handle = @fopen("./rss_receive.ini", "r");
    if ($handle == false) {
        return;
    }
    // 1行目を読み込む
    $strLine = htmlspecialchars(fgets($handle));
    fclose($handle);

    $aryConfig = explode(',', $strLine);
    foreach ($aryConfig as $item) {
        $arySetting = explode('=', $item);
        if ($arySetting[0] == 'count') {
            $nSwTopicCount = intval($arySetting[1]);
        }
        if ($arySetting[0] == 'desc') {
            $nSwDescription = intval($arySetting[1]);
        }
        if ($arySetting[0] == 'days') {
            $nSwPrevDays = intval($arySetting[1]);
        }
        if ($arySetting[0] == 'darktheme') {
            $nSwDarktheme = intval($arySetting[1]);
        }
    }
}

?>

