<html>
<body>
<p>ファイル アップロード</p>

<?php

// パスワードのSHA1
$strPasswordSha1 = '098f6bcd4621d373cade4e832627b4f6';		// たとえば、'test' の場合の例
// スクリプトをリロードするための、このスクリプトの名前
$strThisScriptName = 'upload.php';
// アップロード先の物理ディレクトリ名（末尾は/で終わること）
$strUploadDir = '../test/upload/';
// 表示用、相対ディレクトリ名
$strRelativePath = '/test/upload/';
// 表示用、絶対URIディレクトリ名
$strAbsolutePath = 'http://www.example.com/test/upload/';

session_start();

// ファイルがアップロードされた場合
if(!empty($_FILES['userfile']['name']))
{
//	print '<p>パスワード SHA1='.sha1($_POST['pass'])."<br />\n";
//	print '<p>上書き許可='.$_POST['chk_overwrite']."</p>\n";

	print '<p>SESSION='.$_SESSION['ticket'].'<br />POST='.$_POST['ticket']."\n";
	
	// パスワードのチェック
	if(sha1($_POST['pass']) != $strPasswordSha1)
	{
		print "<p>パスワードが違います</p>\n";
		disp_footer($strThisScriptName);
		exit();
	}
	
	// セッション変数のチェック（リロードによるアップロードの阻止）
	else if($_SESSION['ticket'] != $_POST['ticket'])
	{
		echo '<p>ページのリロードは許可されません</p>';
		disp_footer($strThisScriptName);
		exit();
	}
	
	else
	{
		$strBaseFilename = basename($_FILES['userfile']['name']);
		$uploadfile = $strUploadDir . $strBaseFilename;

		// ファイルがすでに存在し、上書きモードでない場合、終了
		if(file_exists($uploadfile))
		{
			if($_POST['chk_overwrite'] != 'on')
			{
				print("<p>同名のファイルがすでに存在するため、アップロード中止</p>");
				disp_footer($strThisScriptName);
				exit();
			}

			print("<p>同名のファイルがすでに存在しますが、上書きします</p>");
		}
		
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			echo "<p>ファイルがアップロードされました</p>";
		} else {
			echo "<p>ファイルのアップロードに失敗しました</p>";
			disp_footer($strThisScriptName);
			exit();
		}

//		echo '<p>PHPのファイルアップロード変数（$_FILES）の列挙</p>';
		while(list($sKey, $sValue) = each($_FILES['userfile']))
		{
			print '$_FILES[userfile][' . $sKey . "] = " . $sValue . "<br />\n";
		}

		$arrImgsize = getimagesize($uploadfile);
		
		print '<p>画像サイズ X='.$arrImgsize[0].',Y='.$arrImgsize[1]."</p>\n";
		
		// 画像サイズが検出された場合の処理
		if($arrImgsize[0] > 0 && $arrImgsize[1] > 0)
		{
			if($arrImgsize[0] > $arrImgsize[1])
			{
				$nWidth = $_POST['width'];
				$nHeight = round($arrImgsize[1] / $arrImgsize[0] * $nWidth);
			}
			else
			{
				$nHeight = $_POST['width'];
				$nWidth = round($arrImgsize[0] / $arrImgsize[1] * $nHeight);
			}

			print '<p><textarea name="full" rows="4" cols="70">&lt;a target="_blank" href="'.$strRelativePath.$strBaseFilename.'"&gt;&lt;img src="'.$strRelativePath.$strBaseFilename.'" border="0" width="'.$nWidth.'" height="'.$nHeight.'" alt="'.$strBaseFilename.'" /&gt;&lt;/a&gt;'."\n"."</textarea></p>\n";

			print '<p><textarea name="full" rows="4" cols="70">&lt;a target="_blank" href="'.$strAbsolutePath.$strBaseFilename.'"&gt;&lt;img src="'.$strAbsolutePath.$strBaseFilename.'" border="0" width="'.$nWidth.'" height="'.$nHeight.'" alt="'.$strBaseFilename.'" /&gt;&lt;/a&gt;'."\n"."</textarea></p>\n";


			print '<p><a target="_blank" href="'.$strUploadDir.$strBaseFilename.'"><img src="'.$strUploadDir.$strBaseFilename.'" border="0" width="'.$nWidth.'" height="'.$nHeight.'" alt="'.$strBaseFilename.'" /></a></p>'."\n";
			
		}
		
		// 画像サイズが検出されない場合の処理
		else
		{
			print '<p><textarea name="full" rows="4" cols="70">&lt;a target="_blank" href="'.$strRelativePath.$strBaseFilename.'"&gt;'.$strBaseFilename.'をダウンロードする&lt;/a&gt;'."\n"."</textarea></p>\n";

			print '<p><textarea name="full" rows="4" cols="70">&lt;a target="_blank" href="'.$strAbsolutePath.$strBaseFilename.'"&gt;'.$strBaseFilename.'をダウンロードする&lt;/a&gt;'."\n"."</textarea></p>\n";
		}

		
	}

	disp_footer($strThisScriptName);
	exit();

}

// ファイルがアップロードされていない場合
else
{
	// セッション変数を定義する
	$_SESSION['ticket'] = md5(uniqid().mt_rand());

	print("<form enctype=\"multipart/form-data\" action=\"".$strThisScriptName."\" method=\"POST\">\n");
	print("<!-- アップロードするファイルのサイズ上限設定 -->\n");
	print("<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"1000000\" />\n");
	print("<!-- リロード防止用 セッション変数の設定 -->\n");
	print("<input type=\"hidden\" name=\"ticket\" value=\"".$_SESSION['ticket']."\" />");
	print("<table boarder=\"0\">\n");
	print("<tr><td>ファイル</td><td><input name=\"userfile\" type=\"file\"  size=\"40\" /></td></tr>\n");
	print("<tr><td>上書き許可</td><td><input name=\"chk_overwrite\" type=\"checkbox\" value=\"on\" />許可する</td></tr>\n");
	print("<tr><td>長辺のピクセル数</td><td><input name=\"width\" type=\"text\" value=\"320\" maxlength=\"4\" size=\"25\" /></td></tr>\n");
	print("<tr><td>アップロード用パスワード</td><td><input name=\"pass\" type=\"password\"  size=\"25\" /></td></tr>\n");
	print("<tr><td colspan=\"2\"><input type=\"submit\" value=\"ファイルを送信\" /></td></tr>\n");
	print("</table>\n");
	print("</form>\n");

	print '<p>SESSION='.$_SESSION['ticket'].'<br />POST='.$_POST['ticket']."</p>\n";
}
?>

</body>
</html>

<?php

exit();

// これ以下は、ユーザ定義関数

function disp_footer($strThisScriptName)
{
	$_SESSION = array(); // セッション変数を全てクリア
	session_destroy(); // セッションファイルを削除
	echo '<p><a href="'.$strThisScriptName.'">送信画面の表示</a></p>';
}
?>
