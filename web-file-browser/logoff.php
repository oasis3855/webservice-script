<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=shift_jis" />
	<meta http-equiv="Content-Language" content="ja" />
	<title> </title>
</head>
<body>
<p>���ʔF�� ���O�I�t����</p>

<?php

$info=posix_getpwuid(posix_geteuid());      // get user HOME dir

// �y�F�؋��ʊ֐��z��p����
require_once($info['dir'].'/auth/auth.php');

LogoffAuth();

?>

<a href="./index.php">�ēx���O�I������</a>

</body>
</html>
