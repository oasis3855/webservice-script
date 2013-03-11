<?php

$str_version = '1.11';    // 画面に表示するバージョン
// ******************************************************
// Software name : Web File Browser （Web ファイルブラウザ）
//
// Copyright (C) INOUE Hirokazu, All Rights Reserved
//     http://oasis.halfmoon.jp/
//
// version 1.0 (2010/Feb/21)
// version 1.1 (2010/Feb/23)
// version 1.11 (2013/Mar/11)
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
global $strBaseDir;
global $strAbsolutePath;
global $nFixWidth;

// 相対 ディレクトリ
$strRelDir = "";
// ターゲット ディレクトリ
$strTargetDir = "";

// 相対 ファイル
$strRelFile = "";
// ターゲット ファイル
$strTargetFile = "";

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


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<title>Webファイルブラウザ</title>
<style type="text/css">
<!--
a.file {
	color: green;
}
-->
</style>
</head>
<body>
<?php

printf("<p>Webファイルブラウザ Version %s</p>\n", $str_version);

// 相対ディレクトリの決定（何も指定がないときは、ルート / ）
$strRelDir = "/";
if($_POST['target_dir'])
{	// テキストボックスより直接ディレクトリを指定したとき
	if($_POST['target_dir'][0] != '/')
	{
		$strRelDir = '/' . $_POST['target_dir'];
	}
	else
	{
		$strRelDir = $_POST['target_dir'];
	}
}
elseif($_GET['target_dir'])
{	// リンクよりディレクトリを指定したとき
	if($_GET['target_dir'][0] != '/')
	{
		$strRelDir = '/' . $_GET['target_dir'];
	}
	else
	{
		$strRelDir = $_GET['target_dir'];
	}
}
// 末尾に '/' がある場合は取り除く
$strRelDir = rtrim($strRelDir, '/');
// クオート
$strRelDir = escape_linux_filename($strRelDir);

if($_GET['target_file'])
{	// リンクよりファイルを指定したとき
	if($_GET['target_file'][0] != '/')
	{
		$strRelFile = '/' . $_GET['target_file'];
	}
	else
	{
		$strRelFile = $_GET['target_file'];
	}
}
// 末尾に '/' がある場合は取り除く
$strRelFile = rtrim($strRelFile, '/');
// クオート
$strRelFile = escape_linux_filename($strRelFile);

// 絶対ディレクトリ、ファイルの決定
$strTargetDir = $strBaseDir . $strRelDir;
$strTargetFile = $strBaseDir . $strRelFile;

// 画像の場合の、長辺のピクセル数をPOST変数より得る
if($_POST['fix_width'])
{
	$nFixWidth = intval($_POST['fix_width']);
	if($nFixWidth <= 0) $nFixWidth = 320;
}

?>

<form method="post" action="<?php echo basename($strThisScriptName) ?>">
<p>表示したいディレクトリ ： <input type="text" name="target_dir" value="<?php echo htmlspecialchars($strRelDir) ?>" />
<input type="submit" value="ファイル一覧を表示する" /></p>
<p>画像の場合　長辺のピクセル数：<input type="text" size="5" name="fix_width" value="<?php echo $nFixWidth?>" /></p>

</form>


<?php




if($strRelFile)
{
	print("<p>現在表示中のファイル ： ".htmlspecialchars($strRelFile)."</p>\n");
}
else
{
	print("<p>現在表示中のディレクトリ ： ".htmlspecialchars($strRelDir)."/</p>\n");
}

if(!$strRelFile)
{
	$arrDirs = scandir($strTargetDir);
	print("<table>");
	foreach($arrDirs as $strTmp)
	{
		if(is_dir($strTargetDir."/".$strTmp))
		{
			print("<tr><td><a class=\"dir\" href=\"".basename($strThisScriptName)."?target_dir=".htmlspecialchars($strRelDir)."/".htmlspecialchars($strTmp)."\">".htmlspecialchars($strTmp)."</a></td>");
			print("<td>".attr_to_string(fileperms($strTargetDir."/".$strTmp))."</td>");
			print("<td>&lt;ディレクトリ&gt;</td>");
			print("<td>");
			if(time() - filemtime($strTargetDir."/".$strTmp) < 60*60*24*7) print("<span style=\"color:red;\">");
			elseif(time() - filemtime($strTargetDir."/".$strTmp) > 60*60*24*365) print("<span style=\"color:darkgray;\">");
			else print("<span>");
			print(date("Y/m/d H:i:s ",filemtime($strTargetDir."/".$strTmp)));
			print("</span></td>");
			print("</tr>\n");
		}
		else
		{
			print("<tr><td><a class=\"file\" href=\"".basename($strThisScriptName)."?target_file=".htmlspecialchars($strRelDir)."/".htmlspecialchars($strTmp)."\">".htmlspecialchars($strTmp)."</a></td>");
			print("<td>".attr_to_string(fileperms($strTargetDir."/".$strTmp))."</td>");
			print("<td>".number_format(filesize($strTargetDir."/".$strTmp))."</td>");
			print("<td>");
			if(time() - filemtime($strTargetDir."/".$strTmp) < 60*60*24*7) print("<span style=\"color:red;\">");
			elseif(time() - filemtime($strTargetDir."/".$strTmp) > 60*60*24*365) print("<span style=\"color:darkgray;\">");
			else print("<span>");
			print(date("Y/m/d H:i:s ",filemtime($strTargetDir."/".$strTmp)));
			print("</span></td>");
			print("</tr>\n");
			
		}
	}
	print("</table>");
}
else
{
	$strMimeType = exec('file -ib '.$strTargetFile);
	// $strMimeType = mime_content_type($strTargetFile);	// この関数が存在しない

	print("<p>ファイルのMIMEタイプ：".htmlspecialchars($strMimeType)."</p>\n");

	$arrPathElem = explode("/", $strRelFile);
	$strFileBody = $arrPathElem[count($arrPathElem)-1];

	$arrMimeElem = explode("/", $strMimeType);
	if($arrMimeElem[0] == "image")
	{
		$arrImgsize = getimagesize($strTargetFile);
		if($arrImgsize[0] > 0 && $arrImgsize[1] > 0)
		{
			print '<p>画像サイズ X='.$arrImgsize[0].',Y='.$arrImgsize[1]."</p>\n";

			// 表示幅の計算
			if($arrImgsize[0] > $arrImgsize[1])
			{
				$nWidth = $nFixWidth;
				$nHeight = round($arrImgsize[1] / $arrImgsize[0] * $nWidth);
			}
			else
			{
				$nHeight = $nFixWidth;
				$nWidth = round($arrImgsize[0] / $arrImgsize[1] * $nHeight);
			}

			print '<p><textarea name="full" rows="4" cols="70">&lt;a target="_blank" href="'.htmlspecialchars($strRelFile).'"&gt;&lt;img src="'.htmlspecialchars($strRelFile).'" border="0" width="'.$nWidth.'" height="'.$nHeight.'" alt="'.htmlspecialchars($strFileBody).'" /&gt;&lt;/a&gt;'."\n"."</textarea></p>\n";

			print '<p><textarea name="full" rows="4" cols="70">&lt;a target="_blank" href="'.htmlspecialchars($strRelFile).'"&gt;&lt;img src="'.htmlspecialchars($strRelFile).'" border="0" width="'.$arrImgsize[0].'" height="'.$arrImgsize[1].'" alt="'.htmlspecialchars($strFileBody).'" /&gt;&lt;/a&gt;'."\n"."</textarea></p>\n";

			print '<p><textarea name="full" rows="4" cols="70">&lt;a target="_blank" href="'.$strAbsolutePath.htmlspecialchars($strRelFile).'"&gt;&lt;img src="'.$strAbsolutePath.htmlspecialchars($strRelFile).'" border="0" width="'.$nWidth.'" height="'.$nHeight.'" alt="'.htmlspecialchars($strFileBody).'" /&gt;&lt;/a&gt;'."\n"."</textarea></p>\n";

			print '<p><textarea name="full" rows="4" cols="70">&lt;a target="_blank" href="'.$strAbsolutePath.htmlspecialchars($strRelFile).'"&gt;&lt;img src="'.$strAbsolutePath.htmlspecialchars($strRelFile).'" border="0" width="'.$arrImgsize[0].'" height="'.$arrImgsize[1].'" alt="'.htmlspecialchars($strFileBody).'" /&gt;&lt;/a&gt;'."\n"."</textarea></p>\n";


			print '<p><a target="_blank" href="'.$strAbsolutePath.htmlspecialchars($strRelFile).'"><img src="'.$strAbsolutePath.htmlspecialchars($strRelFile).'" border="0" width="'.$nWidth.'" height="'.$nHeight.'" alt="'.htmlspecialchars($strRelFile).'" /></a></p>'."\n";

		}
	}
	elseif($arrMimeElem[0] == "text")
	{
		print '<p><textarea name="full" rows="4" cols="70">&lt;a href="'.htmlspecialchars($strRelFile).'"&gt;'.htmlspecialchars($strFileBody).'を表示する&lt;/a&gt;'."\n"."</textarea></p>\n";

		print '<p><textarea name="full" rows="4" cols="70">&lt;a href="'.$strAbsolutePath.htmlspecialchars($strRelFile).'"&gt;'.htmlspecialchars($strFileBody).'を表示する&lt;/a&gt;'."\n"."</textarea></p>\n";

		print '<p><a href="'.$strAbsolutePath.htmlspecialchars($strRelFile).'">'.htmlspecialchars($strFileBody)."を表示する</a>\n";

	}
	else
	{
		print '<p><textarea name="full" rows="4" cols="70">&lt;a href="'.htmlspecialchars($strRelFile).'"&gt;'.htmlspecialchars($strFileBody).'をダウンロードする&lt;/a&gt;'."\n"."</textarea></p>\n";

		print '<p><textarea name="full" rows="4" cols="70">&lt;a href="'.$strAbsolutePath.htmlspecialchars($strRelFile).'"&gt;'.htmlspecialchars($strFileBody).'をダウンロードする&lt;/a&gt;'."\n"."</textarea></p>\n";

		print '<p><a href="'.$strAbsolutePath.htmlspecialchars($strRelFile).'">'.htmlspecialchars($strFileBody)."をダウンロードする</a>\n";
	}

}

print("<p><a href=\"".$strThisScriptName."?mode=logout\">ログオフする</a></p>\n".
        "</body>\n".
        "</html>\n");

// Linuxでファイル名として認められていない文字を消去、クオートする
function escape_linux_filename($str)
{
	// 処理後の文字列
	$strReturn = "";

	$strReturn = str_replace(":", "", $str);
	$strReturn = str_replace(",", "", $strReturn);
	$strReturn = str_replace("*", "", $strReturn);
	$strReturn = str_replace("?", "", $strReturn);
	$strReturn = str_replace("\"", "", $strReturn);
	$strReturn = str_replace("`", "", $strReturn);
	$strReturn = str_replace("<", "", $strReturn);
	$strReturn = str_replace(">", "", $strReturn);
	$strReturn = str_replace("|", "", $strReturn);
	$strReturn = str_replace("$", "", $strReturn);
	$strReturn = addcslashes($strReturn, " \\");

	return($strReturn);
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

?>
