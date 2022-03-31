<?php

// ******************************************************
// Software name : Pass-Photo.php （証明写真作成PHP）
//
// Copyright (C) INOUE Hirokazu, All Rights Reserved
//     http://oasis.halfmoon.jp/
//
// version 1.0 (2010/08/04)
// version 1.1 (2022/03/31)
//
// GNU GPL Free Software
//
// このプログラムはフリーソフトウェアです。あなたはこれを、フリーソフトウェ
// ア財団によって発行されたGNU 一般公衆利用許諾書（バージョン3か、それ以降
// のバージョンのうちどれか）が定める条件の下で再頒布または改変することが
// できます。
//
// このプログラムは有用であることを願って頒布されますが、*全くの無保証*で
// す。*商業可能性の保証や特定目的への適合性は、言外に示されたものも含め、
// 全く存在しません。*詳しくはGNU 一般公衆利用許諾書をご覧ください。あな
// たはこのプログラムと共に、GNU 一般公衆利用許諾書のコピーを一部受け取っ
// ているはずです。もし受け取っていなければ、
// https://www.gnu.org/licenses/ をご覧ください。
//
// ******************************************************


mb_language('Japanese');
mb_internal_encoding('UTF8');
mb_http_output('UTF8');

// スクリプトをリロードするための、このスクリプトの名前
$strThisScriptName = basename(__FILE__);
// アップロード先のディレクトリ名（末尾は/で終わること）
$strUploadDir = './';
// 一時保存ファイル名の作成
srand(time());
$strBaseFilename = sprintf("temp%06d.jpg", rand(0, 999999));

$uploadfile = $strUploadDir . $strBaseFilename;

// ログファイル（IPアドレスを記録するファイル）
$logfilename = "./log-access.txt";

// 印画紙のサイズ（横サイズ, 縦サイズ）
$size_paper = array(3072, 2048);
// 写真の縦横比（横サイズ, 縦サイズ）
$size_aspect = array(24.0, 30.0);
// 1mmあたりのピクセル数
$size_scale = 22.5; // 22.5pix = 1mm

// 余白
$white_space = (int)(7 * $size_scale);    // 7mm

// サイズを変化させる場合 true
$sw_change_size = false;
// 結果画像ファイルをダウンロードする場合 true
$sw_download_file = false;

// アクセス元のIP
$ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
$host = (!empty($_SERVER['REMOTE_HOST'])) ? $_SERVER['REMOTE_HOST'] : '';

// ログファイルに記録する
$fh = fopen($logfilename, "a+");
if ($fh) {
    $strLog = sprintf("%s,%s,%s\n", date("Y/m/d,H:i:s"), $ip, $host);
    fwrite($fh, $strLog);
    fclose($fh);
}

// ファイルがアップロードされていない場合、メニュー画面を表示する
if (empty($_FILES['userfile']['name'])) {
    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Language" content="ja" />
    <title>証明書用写真 作成プログラム</title>
</head>
<body>
    <p>証明書写真 作成プログラム （確認用サンプル）</p>
    <form enctype="multipart/form-data" action="<?php echo $strThisScriptName ?>" method="post">
    <!-- アップロードするファイルのサイズ上限設定 -->
    <input type="hidden" name="MAX_FILE_SIZE" value="5000000" />
    <table border="0">
    <tr><td>印画紙方向</td>
        <td><input name="paper_orientation" type="radio"  value="landscape" checked="checked" />横（3072x2048）
        <input name="paper_orientation" type="radio"  value="portrait" />縦（2048x3072）</td></tr>
    <tr><td>写真サイズ</td>
        <td><input name="photo_size" type="radio"  value="30x24" checked="checked" />30mmx24mm(運転免許)
        <input name="photo_size" type="radio"  value="40x30" />40mmx30mm
        <input name="photo_size" type="radio"  value="45x35" />45mmx35mm(パスポート)
        <input name="photo_size" type="radio"  value="50x40" />50mmx40mm</td></tr>
    <tr><td>オプション</td>
        <td><input name="change_size" type="checkbox" value="on" />サイズを少しずつ大きくする 
        <input name="download_file" type="checkbox" value="on" />画像をダウンロードする</td></tr>
    <tr><td>ファイル</td><td><input name="userfile" type="file"  size="40" /></td></tr>
    <tr><td colspan="2"><input type="submit" value="画像ファイルを送信" /></td></tr>
    </table>
</form>
<p></p>
<p>送信可能なファイル：jpeg形式、1.0MBytes以下、縦横それぞれ100ピクセル以上1000ピクセル未満</p>
<p style="color:gray;">このサイトはPHPスクリプトの確認用サイトです。不正利用を検知するため、IPアドレスを全て記録しています<br/>
あなたのIPアドレス <?php echo $ip ?></p>
<p style="color:gray;">このPHPスクリプトの<a href="https://github.com/oasis3855/webservice-script">説明・配布ページに移動する</a>
    / テスト用顔写真（Napoleon）を<a href="./Napoleon.jpg">ダウンロード</a>する</p>
<!-- php.ini upload_max_filesize = <?php echo ini_get('upload_max_filesize'); ?> -->
</body>
</html>
    <?php
    exit();
}

// プログラム引数を解析、変数に格納する
if (!empty($_POST["paper_orientation"])) {
    switch ($_POST["paper_orientation"]) {
        case "landscape":
            $size_paper = array(3072, 2048);
            break;
        case "portrait":
            $size_paper = array(2048, 3072);
            break;
    }
}

if (!empty($_POST["photo_size"])) {
    switch ($_POST["photo_size"]) {
        case "30x24":
            $size_aspect = array(24.0, 30.0);
            break;
        case "40x30":
            $size_aspect = array(30.0, 40.0);
            break;
        case "45x35":
            $size_aspect = array(35.0, 45.0);
            break;
        case "50x40":
            $size_aspect = array(40.0, 50.0);
            break;
    }
}

if (!empty($_POST["change_size"])) {
    if ($_POST["change_size"] === "on") {
        $sw_change_size = true;
    }
}

if (!empty($_POST["download_file"])) {
    if ($_POST["download_file"] === "on") {
        $sw_download_file = true;
    }
}

// アップロードされたファイルをチェックする
if ($_FILES["userfile"]["error"] !== UPLOAD_ERR_OK) {
    disp_error_page("ファイルのアップロード中にエラーが発生しました");
    exit();
}
if ($_FILES["userfile"]["size"] === 0) {
    disp_error_page("アップロードされたファイルサイズが 0 です");
    exit();
}
if ($_FILES["userfile"]["size"] > 1024 * 1024) {
    disp_error_page("アップロード可能なファイルサイズは1.0MBytesです");
    exit();
}
if ($_FILES["userfile"]["type"] !== 'image/jpeg') {
    disp_error_page("画像ファイル（JPEG形式）以外がアップロードされました");
    exit();
}

if (move_uploaded_file($_FILES["userfile"]["tmp_name"], $uploadfile) !== true) {
    disp_error_page("アップロードされたファイルの一時保存に失敗しました");
    exit();
}

// アップロードされた画像ファイルの縦横サイズ（ピクセル）を得る
$size = getimagesize($uploadfile);

// アップロードされた画像ファイルで許容されるピクセル数を下回る、超える場合はエラー
if ($size[0] < 100 || $size[1] < 100 || $size[0] > 1000 || $size[1] > 1000) {
    disp_error_page("アップロード可能な画像は縦横それぞれ100px以上、1000px未満です");
    // アップロードされた画像ファイルを消去する
    unlink($strBaseFilename);
    exit();
}

// アップロードされた画像の指定された縦横比の場合での切り出しサイズ（開始座標、幅）を得る
if ($size[0] / $size[1] > $size_aspect[0] / $size_aspect[1]) {
    // 横が長すぎる場合
    $rect_size[0] = (int)(($size[0] - $size[1] * $size_aspect[0] / $size_aspect[1]) / 2);   // 横開始
    $rect_size[1] = 0;  // 縦開始
    $rect_size[2] = (int)($size[1] * $size_aspect[0] / $size_aspect[1]);  // 横幅
    $rect_size[3] = $size[1];   // 縦幅
} else {
    $rect_size[0] = 0;  // 横開始
    $rect_size[1] = (int)(($size[1] - $size[0] * $size_aspect[1] / $size_aspect[0]) / 2);   // 縦開始
    $rect_size[2] = $size[0];   // 横幅
    $rect_size[3] = (int)($size[0] * $size_aspect[1] / $size_aspect[0]);  // 縦幅
}


// 印画紙領域イメージ作成
$image = imagecreatetruecolor($size_paper[0], $size_paper[1]);
$colorWhite = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $colorWhite);

// 印画紙イメージに縦横方眼線をひく
$colorGray = imagecolorallocate($image, 128, 128, 128);
// 縦線をひく
for ($i = 0; $i < $size_paper[0] - 1; $i += $size_scale * 10) {
    imageline($image, $i, 0, $i, $size_paper[1] - 1, $colorGray);
    imageline($image, $i + 1, 0, $i + 1, $size_paper[1] - 1, $colorGray);
}
// 横線をひく
for ($i = 0; $i < $size_paper[1] - 1; $i += $size_scale * 10) {
    imageline($image, 0, $i, $size_paper[0] - 1, $i, $colorGray);
    imageline($image, 0, $i + 1, $size_paper[0] - 1, $i + 1, $colorGray);
}


// アップロードされた画像ファイルを、ビットマップに読み込む
$image_src = imagecreatefromjpeg($uploadfile);

// アップロードされた画像ファイルを消去する
unlink($strBaseFilename);

// 写真 1段目 を描画する
for ($i = 0; $i < 5; $i++) {
    if ($sw_change_size == true) {
        $rect_size_dst[0] = (int)($size_aspect[0] * $size_scale * (1 + 0.03 * $i)); // 横幅
        $rect_size_dst[1] = (int)($size_aspect[1] * $size_scale * (1 + 0.03 * $i)); // 縦幅
    } else {
        $rect_size_dst[0] = (int)($size_aspect[0] * $size_scale);   // 横幅
        $rect_size_dst[1] = (int)($size_aspect[1] * $size_scale);   // 縦幅
    }

    $rect_start_dst[0] = (int)($white_space + $rect_size_dst[0] * $i + $i * $size_scale * 3.0);   // 横開始
    $rect_start_dst[1] = (int)$white_space; // 縦開始

    if ($rect_start_dst[0] + $rect_size_dst[0] + $white_space > $size_paper[0]) {
        break;
    }

    imagecopyresized(
        $image,
        $image_src,
        $rect_start_dst[0],
        $rect_start_dst[1],
        $rect_size[0],
        $rect_size[1],
        $rect_size_dst[0],
        $rect_size_dst[1],
        $rect_size[2],
        $rect_size[3]
    );
}

// 写真 2段目 を描画する
for ($i = 0; $i < 5; $i++) {
    if ($sw_change_size == true) {
        $rect_size_dst[0] = (int)($size_aspect[0] * $size_scale * (1 + 0.03 * $i)); // 横幅
        $rect_size_dst[1] = (int)($size_aspect[1] * $size_scale * (1 + 0.03 * $i)); // 縦幅
    } else {
        $rect_size_dst[0] = (int)($size_aspect[0] * $size_scale);   // 横幅
        $rect_size_dst[1] = (int)($size_aspect[1] * $size_scale);   // 縦幅
    }

    $rect_start_dst[0] = (int)($white_space + $rect_size_dst[0] * $i + $i * $size_scale * 3.0);   // 横開始
    $rect_start_dst[1] = (int)($size_paper[1] / 2);   // 縦開始

    if ($white_space * 2 + $rect_size_dst[1] * 2 > $size_paper[1]) {
        break;
    }
    if ($rect_start_dst[0] + $rect_size_dst[0] + $white_space > $size_paper[0]) {
        break;
    }

    imagecopyresized(
        $image,
        $image_src,
        $rect_start_dst[0],
        $rect_start_dst[1],
        $rect_size[0],
        $rect_size[1],
        $rect_size_dst[0],
        $rect_size_dst[1],
        $rect_size[2],
        $rect_size[3]
    );
}

header("content-type: image/jpeg");
if ($sw_download_file == true) {
    header("Content-Disposition: attachment; filename=\"image.jpg\"");
}
header('Cache-Control: Private');

// JPEG画像を直接ブラウザへ出力
imagejpeg($image);

// ビットマップを破棄する
imagedestroy($image_src);
imagedestroy($image);

// このスクリプトを終了
exit();


// エラーページを表示する
function disp_error_page($strMessage)
{
    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Language" content="ja" />
    <title>証明書用写真 作成プログラム</title>
</head>
<body>
    <p>証明書写真 作成プログラム ： 処理エラー</p>
    <p><?php echo $strMessage ?></p>
</body>
</html>
    <?php
}

?>
