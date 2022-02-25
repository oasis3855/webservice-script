<?php

$str_version = '1.2';    // 画面に表示するバージョン
// ******************************************************
// Software name : Web File Browser （Web ファイルブラウザ）
//
// Copyright (C) INOUE Hirokazu, All Rights Reserved
//     http://oasis.halfmoon.jp/
//
// version 1.0 (2010/Feb/21)
// version 1.1 (2010/Feb/23)
// version 1.11 (2013/Mar/11)
// version 1.2 (2013/Dec/05)  画面構成変更、ディレクトリ階層認識、fancyboxタグ対応
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

// スクリプトをリロードするための、このスクリプトの名前
$strThisScriptName = htmlspecialchars(basename($_SERVER['PHP_SELF']));
// ベース ディレクトリ（サーバのルートからのディレクトリ階層をすべて書く）
$info=posix_getpwuid(posix_geteuid());      // get user HOME dir

// 設定ファイルより、ユーザ環境ごとのディレクトリ等の設定値を読み込む
require_once('./config.php');

// php.iniでdate.timezone="Asia/Tokyo"と設定してもよい
//date_default_timezone_set("Asia/Tokyo");

// 【認証共通関数】を用いる
require_once($info['dir'].'/auth/auth.php');

if(isset($_GET['mode']) && $_GET['mode'] === 'logout'){
	// ログオフ処理（exitする）。sessionを発行するので、PHPの出力より前にこの処理を置く
	func_logoff_auth(basename($_SERVER['PHP_SELF']), 0);
}
else{
	// ログイン処理
	func_check_auth(basename($_SERVER['PHP_SELF']), basename($_SERVER['PHP_SELF']), 0, 'UTF-8');
}

main_func($strThisScriptName);


// メイン処理関数
function main_func($strThisScriptName)
{
	// HTMLヘッダと画面タイトル部分を出力
	print_header();

	// サイドバー部分を出力
	print_sidebar($strThisScriptName);

	// メインコンテンツ領域開始
	print("<div id=\"main_content_right\">\n");

	if($_GET['target_file'] || $_POST['target_file'])
	{
		if($_POST['target_file']){ print_file_preview($strThisScriptName, $_POST['target_file']); }
		elseif($_GET['target_file']){ print_file_preview($strThisScriptName, $_GET['target_file']); }
		else{ print_filelist($strThisScriptName, '/'); }		// エラーの場合ファイル一覧表示
	}
	else
	{
		$strRelDir = "/";		// 引数が無い時は「ルート」ディレクトリを表示する
		if($_POST['target_dir']){ $strRelDir = $_POST['target_dir']; }
		elseif($_GET['target_dir']){ $strRelDir = $_GET['target_dir']; }

		// 指定ディレクトリ内のファイル一覧を画面表示する
		print_filelist($strThisScriptName, $strRelDir);
	}

	// メインコンテンツ領域終了
	print("</div> <!-- \"main_content_right\" -->\n");

	print_footer();

}

// Linuxでファイル名として認められていない文字が有る場合、NULL文字列を返す。
// それ以外の文字は、必要な場合にエスケープ処理をした文字列を返す。
function escape_linux_filename($str)
{
	// 不正な文字が含まれないか検査する
	if(preg_match('/[\:\;\,\*\?\\\"\'\`\<\>\|\$]/', $str)){
		return "";		// 禁止文字が含まれた場合、NULL文字列を返す
	}

	// 対象ディレクトリに「..」や「.」などドッドのみで構成されるディレクトリがないか検査する
	$arrDir = explode("/", $str);
	for($i=0; $i<count($arrDir); $i++)
	{
		if(preg_match('/^[\.]+$/', $arrDir[$i])) return "";
	}

	// 指定された文字をエスケープ（「\」を付加する）した文字列を返す
	return addcslashes($str, ' ');
}

// ファイル属性（int）を、文字列（-rwxrwxrwx）形式に変換する
//
// （この関数は、http://jp.php.net/manual/ja/function.fileperms.php をコピーした）
// （Copyright (C) 2001-2009 The PHP Group）

function attr_to_string($perms)
{
	// 処理後の文字列
	$strReturn = "";

	if (($perms & 0xC000) == 0xC000) {
	    // ソケット
	    $strReturn = 's';
	} elseif (($perms & 0xA000) == 0xA000) {
	    // シンボリックリンク
	    $strReturn = 'l';
	} elseif (($perms & 0x8000) == 0x8000) {
	    // 通常のファイル
	    $strReturn = '-';
	} elseif (($perms & 0x6000) == 0x6000) {
	    // ブロックスペシャルファイル
	    $strReturn = 'b';
	} elseif (($perms & 0x4000) == 0x4000) {
	    // ディレクトリ
	    $strReturn = 'd';
	} elseif (($perms & 0x2000) == 0x2000) {
	    // キャラクタスペシャルファイル
	    $strReturn = 'c';
	} elseif (($perms & 0x1000) == 0x1000) {
	    // FIFO パイプ
	    $strReturn = 'p';
	} else {
	    // 不明
	    $strReturn = 'u';
	}

	// 所有者
	$strReturn .= (($perms & 0x0100) ? 'r' : '-');
	$strReturn .= (($perms & 0x0080) ? 'w' : '-');
	$strReturn .= (($perms & 0x0040) ?
	            (($perms & 0x0800) ? 's' : 'x' ) :
	            (($perms & 0x0800) ? 'S' : '-'));

	// グループ
	$strReturn .= (($perms & 0x0020) ? 'r' : '-');
	$strReturn .= (($perms & 0x0010) ? 'w' : '-');
	$strReturn .= (($perms & 0x0008) ?
	            (($perms & 0x0400) ? 's' : 'x' ) :
	            (($perms & 0x0400) ? 'S' : '-'));

	// 全体
	$strReturn .= (($perms & 0x0004) ? 'r' : '-');
	$strReturn .= (($perms & 0x0002) ? 'w' : '-');
	$strReturn .= (($perms & 0x0001) ?
	            (($perms & 0x0200) ? 't' : 'x' ) :
	            (($perms & 0x0200) ? 'T' : '-'));

	return($strReturn);

}

// HTMLヘッダ出力と、画面デザイン上のヘッダ部分を表示
function print_header()
{
	print("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n".
		"<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"ja\" lang=\"ja\" dir=\"ltr\">\n".
		"<head>\n".
		"<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf8\" />\n".
		"<title>Web File Browser</title>\n".
		"<link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\" />".
		"</head>\n".
		"<body>\n");

	print("<div style=\"height:100px; width:100%; padding:0px; margin:0px;\">\n".
		"<p><span style=\"margin:0px 20px; font-size:30px; font-weight:lighter;\">Web File Browser</span><span style=\"margin:0px 0px; font-size:25px; font-weight:lighter; color:lightgray;\"> for webpage maintenance</span></p>\n".
		"</div>\n"); 

}

// 左ペインの画面出力
function print_sidebar($strThisScriptName)
{
	$strDateTime = date("Y/m/d H:i:s");
	print("<div id=\"main_content_left\">\n".
		"<h2>System</h2>\n".
		"<p>".$strDateTime."</p>\n".
		"<h2>Menu</h2>\n".
		"<ul>\n".
		"<li><a href=\"".$strThisScriptName."\">Home</a></li>\n".
		"<li><a href=\"".$strThisScriptName."?mode=logout\">Logoff</a></li>\n".
		"</ul>\n".
		"<h2>Direct Dir</h2>\n".
		"<form method=\"post\" action=\"".basename($strThisScriptName)."\">\n".
		"<p><input type=\"text\" name=\"target_dir\" value=\"/\" />\n".
		"<input type=\"submit\" value=\"Go\" /></p>\n".
		"</form>\n".
		"</div>	<!-- id=\"main_content_left\" -->\n");
}

// フッター（バージョン情報など）の画面出力と、HTML閉じるタグの出力
function print_footer()
{
	global $str_version;	// このスクリプトの先頭で定義しているバージョン番号

	print("<p>&nbsp;</p>\n".
		"<div class=\"clear\"></div>\n".
		"<div id=\"footer\">\n".
		"<p><a href=\"http://oasis.halfmoon.jp/\">Web File Browser</a> version ".$str_version." &nbsp;&nbsp; GNU GPL free software</p>\n".
		"</div>	<!-- id=\"footer\" -->\n".
		"</body>\n".
		"</html>\n");
}

// 指定されたディレクトリのファイルリストを表示
function print_filelist($strThisScriptName, $strRelDir)
{
	// config.php で設定されるグローバル変数
	global $strBaseDir;
	global $strAbsolutePath;
	global $nFixWidth;

	// ディレクトリ名が示されな場合は「ルート」ディレクトリが表示対象
	if(strlen($strRelDir)<=0){ $strRelDir = "/"; }

	// 指定された相対ディレクトリ名の先頭は「/」で始まるようにする
	if($strRelDir[0] != '/'){ $strRelDir = "/" . $strRelDir; }

	// 末尾に '/' を付加する
	if($strRelDir[strlen($strRelDir)-1] != '/'){ $strRelDir = $strRelDir . '/'; }
	// 禁止文字のチェックと、エスケープ処理（禁止文字が含まれる場合はNULL文字列が返る）
	$strRelDir = escape_linux_filename($strRelDir);

	// 禁止文字が検知された場合、「ルート」ディレクトリを処理対象とする
	if(strlen($strRelDir) <= 0){ $strRelDir = "/"; }

	// 絶対ディレクトリ、ファイルの決定
	$strTargetDir = $strBaseDir . $strRelDir;

	print("<p>現在表示中のディレクトリ ： ".htmlspecialchars($strRelDir)."</p>\n");

	// ディレクトリのパンくずリストを表示する
	$arrDir = explode("/", $strRelDir);
	// 配列が「/」の1個だけの時を除き、末尾の「/」を削除する
	if(count($arrDir)>1){ array_pop($arrDir); }
	print("<p>");
	for($i=0; $i<count($arrDir); $i++)
	{
		$strStepDir = '/';
		for($j=1; $j<=$i; $j++){ $strStepDir = $strStepDir . $arrDir[$j] . '/'; }
		print("<a class=\"dir\" href=\"".basename($strThisScriptName)."?target_dir=".htmlspecialchars($strStepDir)."\">".htmlspecialchars($arrDir[$i]!="" ? $arrDir[$i] : "[root]")."</a> > \n");
	}
	print("</p>\n");

	if(!is_dir($strTargetDir)){
		print("<p>ディレクトリでないものを指定した</p>\n");
		return;
	}

	$arrDirs = scandir($strTargetDir);
	print("<table>");
	foreach($arrDirs as $strTmp)
	{
		if(is_dir($strTargetDir."/".$strTmp))
		{
			print("<tr><td><a class=\"dir\" href=\"".basename($strThisScriptName)."?target_dir=".htmlspecialchars($strRelDir).htmlspecialchars($strTmp)."\">".htmlspecialchars($strTmp)."</a></td>");
			print("<td>".attr_to_string(fileperms($strTargetDir.$strTmp))."</td>");
			print("<td>&lt;ディレクトリ&gt;</td>");
			print("<td>");
			if(time() - filemtime($strTargetDir.$strTmp) < 60*60*24*7) print("<span style=\"color:red;\">");
			elseif(time() - filemtime($strTargetDir.$strTmp) > 60*60*24*365) print("<span style=\"color:darkgray;\">");
			else print("<span>");
			print(date("Y/m/d H:i:s ",filemtime($strTargetDir.$strTmp)));
			print("</span></td>");
			print("</tr>\n");
		}
		else
		{
			print("<tr><td><a class=\"file\" href=\"".basename($strThisScriptName)."?target_file=".htmlspecialchars($strRelDir).htmlspecialchars($strTmp)."\">".htmlspecialchars($strTmp)."</a></td>");
			print("<td>".attr_to_string(fileperms($strTargetDir.$strTmp))."</td>");
			print("<td>".number_format(filesize($strTargetDir.$strTmp))."</td>");
			print("<td>");
			if(time() - filemtime($strTargetDir.$strTmp) < 60*60*24*7) print("<span style=\"color:red;\">");
			elseif(time() - filemtime($strTargetDir.$strTmp) > 60*60*24*365) print("<span style=\"color:darkgray;\">");
			else print("<span>");
			print(date("Y/m/d H:i:s ",filemtime($strTargetDir.$strTmp)));
			print("</span></td>");
			print("</tr>\n");
			
		}
	}
	print("</table>");

}

// 指定されたファイルの、ダウンロード・リンク構文例を表示
function print_file_preview($strThisScriptName, $strRelFile)
{
	// config.php で設定されるグローバル変数
	global $strBaseDir;
	global $strAbsolutePath;
	global $nFixWidth;

	$strRelDir = escape_linux_filename($strRelFile);

	// 絶対ディレクトリ、ファイルの決定
	$strTargetFile = $strBaseDir . $strRelFile;

	// 禁止文字が検知された場合、「ルート」ディレクトリを処理対象とする
	if(strlen($strRelDir) <= 0 || !is_file($strTargetFile) || !is_readable($strTargetFile))
	{
		print("<p>ファイルでないものを指定した</p>\n");
		return;
	}

	// ディレクトリのパンくずリストを表示する
	$arrDir = explode("/", $strRelFile);
	// 配列から最後の1つ（ファイル名）を削除する
	$strFileBody = array_pop($arrDir);
	print("<p>");
	for($i=0; $i<count($arrDir); $i++)
	{
		$strStepDir = '/';
		for($j=1; $j<=$i; $j++){ $strStepDir = $strStepDir . $arrDir[$j] . '/'; }
		print("<a class=\"dir\" href=\"".basename($strThisScriptName)."?target_dir=".htmlspecialchars($strStepDir)."\">".htmlspecialchars($arrDir[$i]!="" ? $arrDir[$i] : "[root]")."</a> > \n");
	}
	print(htmlspecialchars($strFileBody)."\n");
	print("</p>\n");

	$strMimeType = exec('file -ib '.$strTargetFile);
	// $strMimeType = mime_content_type($strTargetFile);	// この関数が存在しない

	print("<p>ファイルのMIMEタイプ：".htmlspecialchars($strMimeType)."</p>\n");

	$arrMimeElem = explode("/", $strMimeType);
	if($arrMimeElem[0] == "image")
	{

		// Web引数を読み取る
		$nTargetSize = $nFixWidth;	// 目標サイズ（縦または横）の初期設定値
		if($_POST['size']){ $nTargetSize = intval($_POST['size']); }
		$strImgTitle = '';
		if($_POST['title']){ $strImgTitle = htmlspecialchars($_POST['title']); }
		$strImgAlt = '';
		if($_POST['alt']){ $strImgAlt = htmlspecialchars($_POST['alt']); }

		// 画像のサイズを読み込む
		$arrImgsize = getimagesize($strTargetFile);
		$x0 = $arrImgsize[0];	// 画像の元サイズ（横）
		$y0 = $arrImgsize[1];	// 画像の元サイズ（縦）
		$x = 0;		// サイズ変更後（横）
		$y = 0;		// サイズ変更後（縦）

		// 画像サイズ、それぞれの値のチェック
		if($x0 <=0 || $y0 <= 0 || $nTargetSize <= 1 || $nTargetSize > 9999)
		{
			print("<p>画像サイズまたは指定サイズが範囲外</p>\n");
			return;
		}

		// 画像表示サイズを計算する
		if($_POST['size_base'] === 'long' || !defined($_POST['size_base']))
		{
			if($x0 > $y0){ $x = $nTargetSize; $y = (int)($x*$y0/$x0); }
			else{ $y = $nTargetSize; $x = (int)($y*$x0/$y0); }
		}
		elseif($_POST['size_base'] === 'v')
		{
			$y = $nTargetSize; $x = (int)($y*$x0/$y0);
		}
		elseif($_POST['size_base'] === 'h')
		{
			$x = $nTargetSize; $y = (int)($x*$y0/$x0);
		}
		else
		{
			$x = $x0; $y = $y0;		// 等倍（サイズ変更なし）
		}

		// 説明・値設定ボックス
		print("<form method=\"post\" action=\"".$strThisScriptName."\" class=\"inbox\">\n".
			"<table border=\"0\">\n".
			"<tr><td colspan=\"2\">webでの表現方法の設定</td></tr>\n".
			"<tr><td>元画像の情報</td><td>横(x)=".$x0.", 縦(y)=".$y0.", サイズ=".filesize($strTargetFile)." bytes\n".
			"<!-- Control value : directory index, filename -->\n".
			"<input type=\"hidden\" name=\"dir\" value=\"1\" size=\"30\" />\n".
			"<input type=\"hidden\" name=\"target_file\" value=\"".htmlspecialchars($strRelFile)."\" size=\"30\" /></td></tr>\n".
			"<tr><td>画像表示サイズ</td><td><input type=\"text\" name=\"size\" value=\"".$nTargetSize."\" size=\"15\" /> (px)\n". 
			"&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"size_base\" value=\"long\" checked=\"checked\" />長辺\n".
			"&nbsp;&nbsp;<input type=\"radio\" name=\"size_base\" value=\"v\" />縦\n".
			"&nbsp;&nbsp;<input type=\"radio\" name=\"size_base\" value=\"h\" />横\n".
			"&nbsp;&nbsp;<input type=\"radio\" name=\"size_base\" value=\"off\" />OFF（実寸）</td></tr>\n".
			"<tr><td>alt属性値</td><td><input type=\"text\" name=\"alt\" value=\"".$strImgAlt."\" size=\"30\" />&nbsp;<input type=\"checkbox\" name=\"alt_fname\" value=\"on\" checked=\"checked\" />空欄の時はファイル名を利用</td></tr>\n".
			"<tr><td>title属性値</td><td><input type=\"text\" name=\"title\" value=\"".$strImgTitle."\" size=\"30\" /> (空欄の時は属性削除) </td></tr>\n".
			"<tr><td>その他</td><td><input type=\"checkbox\" name=\"target\" value=\"blank\" checked=\"checked\" />ファイル（画像）を新しいウインドウで開く&nbsp;&nbsp;<input type=\"checkbox\" name=\"fancybox\" value=\"on\" checked=\"checked\" />fancybox対応（&lt;a rel=... title=...&gt;）</td></tr>\n".
			"</table>\n".
			"<input type=\"submit\" value=\"表示条件を変更して再表示する\" />\n".
			"</form>\n");

		// Alt属性にファイル名を使う場合の処理
		if(!strlen($strImgAlt) && $_POST['alt_fname']==='on'){ $strImgAlt = $strFileBody; }

		# 貼り付け用コード例を画面表示する
		print("<pre class=\"fold\">\n".
			"&lt;a href=\"".htmlspecialchars($strRelFile)."\"".
			($_POST['fancybox'] === 'on' ? ' rel="lightbox_group"' : '').
			($_POST['fancybox'] === 'on' && strlen($strImgTitle) ? ' title="'.htmlspecialchars($strImgTitle).'"' : '').
			($_POST['target'] === 'blank' ? ' target="_blank"' : '')."&gt;".
			"&lt;img src=\"".htmlspecialchars($strRelFile)."\" width=\"".$x."\" height=\"".$y."\" ".
			"alt=\"".htmlspecialchars($strImgAlt)."\" ".(strlen($strImgTitle) ? 'title="'.htmlspecialchars($strImgTitle).'"' : '')." /&gt;".
			"&lt;/a&gt;\n".
			"</pre>\n");

		# 貼り付け用コード例を画面表示する
		print("<pre class=\"fold\">\n".
			"&lt;a href=\"".htmlspecialchars($strAbsolutePath.$strRelFile)."\"".
			($_POST['fancybox'] === 'on' ? ' rel="lightbox_group"' : '').
			($_POST['fancybox'] === 'on' && strlen($strImgTitle) ? ' title="'.htmlspecialchars($strImgTitle).'"' : '').
			($_POST['target'] === 'blank' ? ' target="_blank"' : '')."&gt;".
			"&lt;img src=\"".htmlspecialchars($strAbsolutePath.$strRelFile)."\" width=\"".$x."\" height=\"".$y."\" ".
			"alt=\"".htmlspecialchars($strImgAlt)."\" ".(strlen($strImgTitle) ? 'title="'.htmlspecialchars($strImgTitle).'"' : '')." /&gt;".
			"&lt;/a&gt;\n".
			"</pre>\n");


		# 貼り付け用コードの描画例を表示する
		print("<p>Web上での表示例</p>\n".
			"<p><a href=\"".htmlspecialchars($strAbsolutePath.$strRelFile)."\"".
			($_POST['target'] === 'blank' ? ' target="_blank"' : '').">".
			"<img src=\"".htmlspecialchars($strAbsolutePath.$strRelFile)."\" width=\"".$x."\" height=\"".$y."\" ".
			"alt=\"".$strImgAlt."\" ".(strlen($strImgTitle) ? 'title="'.$strImgTitle.'"' : '')." />".
			"</a></p>\n");

	}
	else
	{
		// 貼り付け用コード例を画面表示する
		print("<pre class=\"fold\">\n".
				"&lt;a href=\"".htmlspecialchars($strRelFile)."\"&gt;".
				htmlspecialchars($strFileBody)."をダウンロードする&lt;/a&gt;\n".
				"</pre>\n");

		// 貼り付け用コード例を画面表示する
		print("<pre class=\"fold\">\n".
				"&lt;a href=\"".htmlspecialchars($strAbsolutePath.$strRelFile)."\"&gt;".
				htmlspecialchars($strFileBody)."をダウンロードする&lt;/a&gt;\n".
				"</pre>\n");
	   
		// 貼り付け用コードの描画例を表示する
		print("<p>Web上での表示例</p>\n".
				"<p><a href=\"".htmlspecialchars($strAbsolutePath.$strRelFile)."\">".
				htmlspecialchars($strFileBody)."をダウンロードする</a></p>\n");
	}


}

?>
