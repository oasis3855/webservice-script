<?php
// ******************************************************
// Software name : Web File Browser �iWeb �t�@�C���u���E�U�j
//
// Copyright (C) INOUE Hirokazu, All Rights Reserved
//     http://oasis.halfmoon.jp/
//
// version 1.0 (2010/Feb/21)
//
// GNU GPL Free Software
//
// ���̃v���O�����̓t���[�\�t�g�E�F�A�ł��B���Ȃ��͂�����A�t���[�\�t�g�E�F�A��
// �c�ɂ���Ĕ��s���ꂽ GNU ��ʌ��O���p�����_��(�o�[�W����2���A��]�ɂ���Ă�
// ����ȍ~�̃o�[�W�����̂����ǂꂩ)�̒�߂�����̉��ōĔЕz�܂��͉��ς��邱�Ƃ�
// �ł��܂��B
// 
// ���̃v���O�����͗L�p�ł��邱�Ƃ�����ĔЕz����܂����A*�S���̖��ۏ�* �ł��B
// ���Ɖ\���̕ۏ؂����̖ړI�ւ̓K�����́A���O�Ɏ����ꂽ���̂��܂ߑS�����݂�
// �܂���B�ڂ�����GNU ��ʌ��O���p�����_�񏑂��������������B
// 
// ���Ȃ��͂��̃v���O�����Ƌ��ɁAGNU ��ʌ��O���p�����_�񏑂̕��������ꕔ�󂯎�
// �����͂��ł��B�����󂯎���Ă��Ȃ���΁A�t���[�\�t�g�E�F�A���c�܂Ő������Ă�
// ������(����� the Free Software Foundation, Inc., 59 Temple Place, Suite 330
// , Boston, MA 02111-1307 USA)�B
//
// http://www.opensource.jp/gpl/gpl.ja.html
// ******************************************************

// �X�N���v�g�������[�h���邽�߂́A���̃X�N���v�g�̖��O
$strThisScriptName = htmlspecialchars(basename($_SERVER['PHP_SELF']));
// �x�[�X �f�B���N�g���i�T�[�o�̃��[�g����̃f�B���N�g���K�w�����ׂď����j
$info=posix_getpwuid(posix_geteuid());      // get user HOME dir
$strBaseDir = $info['dir'].'/www';
// ���� �f�B���N�g��
$strRelDir = "";
// �^�[�Q�b�g �f�B���N�g��
$strTargetDir = "";

// ���� �t�@�C��
$strRelFile = "";
// �^�[�Q�b�g �t�@�C��
$strTargetFile = "";

// HTTP��΃p�X�i�Ō�̃X���b�V���͊܂܂Ȃ��j
$strAbsolutePath = 'http://www.example.com';

// �摜���w��
$nFixWidth = 320;

// php.ini��date.timezone="Asia/Tokyo"�Ɛݒ肵�Ă��悢
//date_default_timezone_set("Asia/Tokyo");

// �y�F�؋��ʊ֐��z��p����
require_once($info['dir'].'/auth/auth.php');

if(isset($_GET['mode']) && $_GET['mode'] === 'logout'){
    // ���O�I�t�����iexit����j�Bsession�𔭍s����̂ŁAPHP�̏o�͂��O�ɂ��̏�����u��
    func_logoff_auth(basename($_SERVER['PHP_SELF']), 0);
}
else{
    // ���O�C������
    func_check_auth(basename($_SERVER['PHP_SELF']), basename($_SERVER['PHP_SELF']), 0);
}

// ���΃f�B���N�g���̌���i�����w�肪�Ȃ��Ƃ��́A���[�g / �j
$strRelDir = "/";
if($_POST['target_dir'])
{	// �e�L�X�g�{�b�N�X��蒼�ڃf�B���N�g�����w�肵���Ƃ�
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
{	// �����N���f�B���N�g�����w�肵���Ƃ�
	if($_GET['target_dir'][0] != '/')
	{
		$strRelDir = '/' . $_GET['target_dir'];
	}
	else
	{
		$strRelDir = $_GET['target_dir'];
	}
}
// ������ '/' ������ꍇ�͎�菜��
$strRelDir = rtrim($strRelDir, '/');
// �N�I�[�g
$strRelDir = escape_linux_filename($strRelDir);

if($_GET['target_file'])
{	// �����N���t�@�C�����w�肵���Ƃ�
	if($_GET['target_file'][0] != '/')
	{
		$strRelFile = '/' . $_GET['target_file'];
	}
	else
	{
		$strRelFile = $_GET['target_file'];
	}
}
// ������ '/' ������ꍇ�͎�菜��
$strRelFile = rtrim($strRelFile, '/');
// �N�I�[�g
$strRelFile = escape_linux_filename($strRelFile);

// ��΃f�B���N�g���A�t�@�C���̌���
$strTargetDir = $strBaseDir . $strRelDir;
$strTargetFile = $strBaseDir . $strRelFile;

// �摜�̏ꍇ�́A���ӂ̃s�N�Z������POST�ϐ���蓾��
if($_POST['fix_width'])
{
	$nFixWidth = intval($_POST['fix_width']);
	if($nFixWidth <= 0) $nFixWidth = 320;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS" />
<title>Web�t�@�C���u���E�U</title>
<style type="text/css">
<!--
a.file {
	color: green;
}
-->
</style>
</head>
<body>
<p>Web�t�@�C���u���E�U (version 1.0)</p>

<form method="post" action="<?php echo $strThisScriptName ?>">
<p>�\���������f�B���N�g�� �F <input type="text" name="target_dir" value="<?php echo htmlspecialchars($strRelDir) ?>" />
<input type="submit" value="�t�@�C���ꗗ��\������" /></p>
<p>�摜�̏ꍇ�@���ӂ̃s�N�Z�����F<input type="text" size="5" name="fix_width" value="<?php echo $nFixWidth?>" /></p>

</form>


<?php




if($strRelFile)
{
	print("<p>���ݕ\�����̃t�@�C�� �F ".htmlspecialchars($strRelFile)."</p>\n");
}
else
{
	print("<p>���ݕ\�����̃f�B���N�g�� �F ".htmlspecialchars($strRelDir)."/</p>\n");
}

if(!$strRelFile)
{
	$arrDirs = scandir($strTargetDir);
	print("<table>");
	foreach($arrDirs as $strTmp)
	{
		if(is_dir($strTargetDir."/".$strTmp))
		{
			print("<tr><td><a class=\"dir\" href=\"".$strThisScriptName."?target_dir=".htmlspecialchars($strRelDir)."/".htmlspecialchars($strTmp)."\">".htmlspecialchars($strTmp)."</a></td>");
			print("<td>".attr_to_string(fileperms($strTargetDir."/".$strTmp))."</td>");
			print("<td>&lt;�f�B���N�g��&gt;</td>");
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
			print("<tr><td><a class=\"file\" href=\"".$strThisScriptName."?target_file=".htmlspecialchars($strRelDir)."/".htmlspecialchars($strTmp)."\">".htmlspecialchars($strTmp)."</a></td>");
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
	// $strMimeType = mime_content_type($strTargetFile);	// ���̊֐������݂��Ȃ�

	print("<p>�t�@�C����MIME�^�C�v�F".htmlspecialchars($strMimeType)."</p>\n");

	$arrPathElem = explode("/", $strRelFile);
	$strFileBody = $arrPathElem[count($arrPathElem)-1];

	$arrMimeElem = explode("/", $strMimeType);
	if($arrMimeElem[0] == "image")
	{
		$arrImgsize = getimagesize($strTargetFile);
		if($arrImgsize[0] > 0 && $arrImgsize[1] > 0)
		{
			print '<p>�摜�T�C�Y X='.$arrImgsize[0].',Y='.$arrImgsize[1]."</p>\n";

			// �\�����̌v�Z
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


			print '<p><a target="_blank" href="'.htmlspecialchars($strRelFile).'"><img src="'.htmlspecialchars($strRelFile).'" border="0" width="'.$nWidth.'" height="'.$nHeight.'" alt="'.htmlspecialchars($strRelFile).'" /></a></p>'."\n";

		}
	}
	elseif($arrMimeElem[0] == "text")
	{
		print '<p><textarea name="full" rows="4" cols="70">&lt;a href="'.htmlspecialchars($strRelFile).'"&gt;'.htmlspecialchars($strFileBody).'��\������&lt;/a&gt;'."\n"."</textarea></p>\n";

		print '<p><textarea name="full" rows="4" cols="70">&lt;a href="'.$strAbsolutePath.htmlspecialchars($strRelFile).'"&gt;'.htmlspecialchars($strFileBody).'��\������&lt;/a&gt;'."\n"."</textarea></p>\n";

		print '<p><a href="'.htmlspecialchars($strRelFile).'">'.htmlspecialchars($strFileBody)."��\������</a>\n";

	}
	else
	{
		print '<p><textarea name="full" rows="4" cols="70">&lt;a href="'.htmlspecialchars($strRelFile).'"&gt;'.htmlspecialchars($strFileBody).'���_�E�����[�h����&lt;/a&gt;'."\n"."</textarea></p>\n";

		print '<p><textarea name="full" rows="4" cols="70">&lt;a href="'.$strAbsolutePath.htmlspecialchars($strRelFile).'"&gt;'.htmlspecialchars($strFileBody).'���_�E�����[�h����&lt;/a&gt;'."\n"."</textarea></p>\n";

		print '<p><a href="'.htmlspecialchars($strRelFile).'">'.htmlspecialchars($strFileBody)."���_�E�����[�h����</a>\n";
	}

}

?>
</body>
</html>
<?php

// Linux�Ńt�@�C�����Ƃ��ĔF�߂��Ă��Ȃ������������A�N�I�[�g����
function escape_linux_filename($str)
{
	// ������̕�����
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

// �t�@�C�������iint�j���A������i-rwxrwxrwx�j�`���ɕϊ�����
//
// �i���̊֐��́Ahttp://jp.php.net/manual/ja/function.fileperms.php ���R�s�[�����j
// �iCopyright (C) 2001-2009 The PHP Group�j

function attr_to_string($perms)
{
	// ������̕�����
	$strReturn = "";

	if (($perms & 0xC000) == 0xC000) {
	    // �\�P�b�g
	    $strReturn = 's';
	} elseif (($perms & 0xA000) == 0xA000) {
	    // �V���{���b�N�����N
	    $strReturn = 'l';
	} elseif (($perms & 0x8000) == 0x8000) {
	    // �ʏ�̃t�@�C��
	    $strReturn = '-';
	} elseif (($perms & 0x6000) == 0x6000) {
	    // �u���b�N�X�y�V�����t�@�C��
	    $strReturn = 'b';
	} elseif (($perms & 0x4000) == 0x4000) {
	    // �f�B���N�g��
	    $strReturn = 'd';
	} elseif (($perms & 0x2000) == 0x2000) {
	    // �L�����N�^�X�y�V�����t�@�C��
	    $strReturn = 'c';
	} elseif (($perms & 0x1000) == 0x1000) {
	    // FIFO �p�C�v
	    $strReturn = 'p';
	} else {
	    // �s��
	    $strReturn = 'u';
	}

	// ���L��
	$strReturn .= (($perms & 0x0100) ? 'r' : '-');
	$strReturn .= (($perms & 0x0080) ? 'w' : '-');
	$strReturn .= (($perms & 0x0040) ?
	            (($perms & 0x0800) ? 's' : 'x' ) :
	            (($perms & 0x0800) ? 'S' : '-'));

	// �O���[�v
	$strReturn .= (($perms & 0x0020) ? 'r' : '-');
	$strReturn .= (($perms & 0x0010) ? 'w' : '-');
	$strReturn .= (($perms & 0x0008) ?
	            (($perms & 0x0400) ? 's' : 'x' ) :
	            (($perms & 0x0400) ? 'S' : '-'));

	// �S��
	$strReturn .= (($perms & 0x0004) ? 'r' : '-');
	$strReturn .= (($perms & 0x0002) ? 'w' : '-');
	$strReturn .= (($perms & 0x0001) ?
	            (($perms & 0x0200) ? 't' : 'x' ) :
	            (($perms & 0x0200) ? 'T' : '-'));

	return($strReturn);

}

?>
