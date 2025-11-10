<?php

// ******************************************************
// Software name : Pass-Photo.php （証明写真作成PHP）
//
// Copyright (C) INOUE Hirokazu, All Rights Reserved
//     http://oasis.halfmoon.jp/
//
// version 1.0 (2010/08/04)
// version 1.1 (2022/03/31)
// version 2.0 (2025/07/29) : CLIインターフェース実装
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
$strThisScriptName = basename($_SERVER['PHP_SELF']);
// アップロード先のディレクトリ名（末尾は/で終わること）
$strUploadDir = './data/';
// 一時保存ファイル名の作成
srand(time());
$strBaseFilename = sprintf("temp%06d.jpg", rand(0, 999999));

$uploadfile = $strUploadDir . $strBaseFilename;

// Webアップロードする素材写真jpeg画像ファイルの最大サイズ (Byte)
define('UPLOAD_FILESIZE_LIMIT', 1000 * 1024);

// 素材写真jpeg画像ファイルの縦・横最大ピクセル数 (pix)
define('SOURCE_IMAGE_SIZE_LIMIT', 1980);

// ログファイル（IPアドレスを記録するファイル）
$logfilename = "./data/logfile.csv";

// (参考) 印画紙サイズ
// E判 : 117mm * 83mm
// L判 : 127mm * 89mm .... 標準的な写真プリントサイズ

// 印画紙のサイズ（横サイズ, 縦サイズ）
$size_paper = [3072, 2048];
// 写真の縦横比（横サイズ, 縦サイズ）
$size_aspect = [24.0, 30.0];
// 1mmあたりのピクセル数 (ユーザが指定できる範囲は 20.0 〜 25.0 に制限する)
$size_scale = 23.01; // 縦サイズ 2048px / 89mm = 23.01px/mm

// 余白
$white_space = (int)(7 * $size_scale);    // 7mm

// サイズを変化させる場合 true
$sw_change_size = false;
// 結果画像ファイルをダウンロードする場合 true
$sw_download_file = false;

// アクセス元のIP
$ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
$host = (!empty($_SERVER['REMOTE_HOST'])) ? $_SERVER['REMOTE_HOST'] : '';

/**
 * HTMLのメニューページを表示する
 */
function printHtmlMenupage(): void
{
    // スクリプトをリロードするための、このスクリプトの名前
    $strThisScriptName = basename($_SERVER['PHP_SELF']);
    // アクセス元のIPアドレス
    $ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
    $ip = filter_var($ip, FILTER_VALIDATE_IP);  // IPアドレス書式に一致しない場合はfalseを返す
    $ip = $ip ? $ip : '';

?>
    <!DOCTYPE html>
    <html lang="ja">

    <head>
        <meta charset="utf-8" />
        <title>証明書用写真 作成プログラム</title>
    </head>

    <body>
        <p>証明書写真 作成プログラム</p>
        <form enctype="multipart/form-data" action="<?php echo $strThisScriptName ?>" method="post">
            <!-- アップロードするファイルのサイズ上限設定 -->
            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo UPLOAD_FILESIZE_LIMIT; ?>" />
            <table style="border-style: none;">
                <tr>
                    <td>印画紙方向</td>
                    <td><input name="paper_orientation" type="radio" value="landscape" checked="checked" />横（3072x2048）
                        <input name="paper_orientation" type="radio" value="portrait" />縦（2048x3072）
                    </td>
                </tr>
                <tr>
                    <td>写真サイズ</td>
                    <td><input name="photo_size" type="radio" value="30x24" checked="checked" />30mmx24mm(運転免許)
                        <input name="photo_size" type="radio" value="40x30" />40mmx30mm(履歴書)
                        <input name="photo_size" type="radio" value="45x35" />45mmx35mm(パスポート)
                        <input name="photo_size" type="radio" value="50x40" />50mmx40mm
                    </td>
                </tr>
                <tr>
                    <td>オプション</td>
                    <td><input name="change_size" type="checkbox" value="on" />サイズを少しずつ大きくする
                        <input name="download_file" type="checkbox" value="on" />画像をダウンロードする
                    </td>
                </tr>
                <tr>
                    <td>スケーリング</td>
                    <td><input name="size_scale" type="text" value="23.01" /> px/mm (L判印刷の場合 2048px/89mm = 23.01)
                    </td>
                </tr>
                <tr>
                    <td>ファイル</td>
                    <td><input name="userfile" type="file" size="40" /></td>
                </tr>
                <tr>
                    <td colspan="2"><input type="submit" value="画像ファイルを送信" /></td>
                </tr>
            </table>
        </form>
        <p></p>
        <p>送信可能なファイル：jpeg形式、<?php echo round(UPLOAD_FILESIZE_LIMIT / 1024, 1); ?>kBytes以下、縦横それぞれ100ピクセル以上<?php echo SOURCE_IMAGE_SIZE_LIMIT; ?>ピクセル未満</p>
        <p style="color:gray;">このサイトはPHPスクリプトの確認用サイトです。不正利用を検知するため、IPアドレスを全て記録しています<br />
            あなたのIPアドレス <?php echo $ip; ?></p>
        <p style="color:gray;">このPHPスクリプトの<a href="https://github.com/oasis3855/webservice-script">説明・配布ページに移動する</a>
            / テスト用顔写真 <a href="./Napoleon.jpg">Napoleon</a> | <a href="./Turkish_Van_Cat.jpg">Turkish Van Cat</a></p>
        <!-- php.ini upload_max_filesize = <?php echo ini_get('upload_max_filesize'); ?> -->
    </body>

    </html>
<?php
}

/**
 * エラーページを表示する
 * 
 * @param string $strMessage : エラーメッセージ文字列
 */
function printHtmlErrorpage(string $strMessage): void
{
?>
    <!DOCTYPE html>
    <html lang="ja">

    <head>
        <meta charset="utf-8" />
        <title>証明書用写真 作成プログラム : 処理エラー</title>
    </head>

    <body>
        <p>証明書写真 作成プログラム ： 処理エラー</p>
        <p><?php echo htmlspecialchars($strMessage, ENT_NOQUOTES, 'UTF-8'); ?></p>
    </body>

    </html>
<?php
}

/**
 * アクセスログに、日時とipアドレス等を記録する
 * 
 * @param string $logFilepath : ログファイルのファイル名
 */
function logWrite(string $logFilepath): void
{
    // アクセス元のIPアドレス
    $ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
    $ip = filter_var($ip, FILTER_VALIDATE_IP);  // IPアドレス書式に一致しない場合はfalseを返す
    $ip = $ip ? $ip : '';
    // アクセス元のリモートホスト名
    $host = (!empty($_SERVER['REMOTE_HOST'])) ? $_SERVER['REMOTE_HOST'] : '';

    // ログファイルに書き込む
    $strLog = date("Y/m/d H:i:s") . "," . $ip . "," . $host . "\n";
    file_put_contents($logFilepath, $strLog, FILE_APPEND | LOCK_EX);
}

/**
 * Web実行の場合の一連の処理を行う
 */
function main_web(): void
{
    global $uploadfile;
    global $logfilename;
    // 結果画像ファイルをダウンロードする場合 true
    global $sw_download_file;

    // POSTデータが送信されてきた場合は、ファイルのアップロード処理。そうでない場合は入力フォームを表示
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['userfile'])) {
        // アップロードされたファイルをチェックする
        $uploadTmpPath = validateUploadedFile($_FILES['userfile']);
        if (!$uploadTmpPath) exit();
        // アップロードされたファイルが一時ファイルとして保存されているので、それを作業ディレクトリに移動する
        if (!move_uploaded_file($uploadTmpPath, $uploadfile)) {
            printHtmlErrorpage("アップロードされたファイルの保存に失敗しました");
            exit();
        }
    } else {
        printHtmlMenupage();
        exit();
    }

    // POSTされたformデータの取り込み
    parseWebFormOption();

    // ログファイルに記録する
    logWrite($logfilename);

    $image = imageProcess($uploadfile);
    unlink($uploadfile);
    if (!$image) exit();


    header("content-type: image/jpeg");
    if ($sw_download_file == true) {
        header("Content-Disposition: attachment; filename=\"image.jpg\"");
    }
    header('Cache-Control: Private');

    // JPEG画像を直接ブラウザへ出力
    imagejpeg($image);

    // ビットマップを破棄する
    imagedestroy($image);
}

/**
 * CLI(コマンドライン)実行の場合の一連の処理を行う
 */
function main_cli(): void
{

    // 印画紙のサイズ（横サイズ, 縦サイズ）
    global $size_paper;
    // 写真の縦横比（横サイズ, 縦サイズ）
    global $size_aspect;
    // 1mmあたりのピクセル数
    global $size_scale;
    // 余白
    global $white_space;
    // サイズを変化させる場合 true
    global $sw_change_size;
    // 結果画像ファイルをダウンロードする場合 true
    global $sw_download_file;

    // --- CLI引数の処理 ---
    $args = $_SERVER['argv'];
    array_shift($args); // スクリプト名除去
    if (count($args) === 0) {
        showUsage();
        exit();
    }
    $filenames = parseCliOption($args);
    $input_filename = $filenames[0];
    $output_filename = $filenames[1];

    // ******** デバッグ表示
    echo "引数取り込み状況の表示\n";
    print("size_paper = " . $size_paper[0] . "x" . $size_paper[1] . "\n");
    print("size_paper = " . $size_aspect[0] . "x" . $size_aspect[1] . "\n");
    print("size_scale = " . $size_scale . "\n");
    print("sw_change_size = " . ($sw_change_size ? "true" : "false") . "\n");
    print("filenames = " . $filenames[0] . ", " . $filenames[1] . "\n");

    print("input file filenames[0] check : " . (validateLocalFile($input_filename) ? "ファイルOK" : "ファイルNG") . "\n");

    $image = imageProcess($input_filename);
    if (!imagejpeg($image, $output_filename, 85)) {
        echo $output_filename . " に保存失敗\n";
    }
    echo $output_filename . " に保存成功\n";
    // ビットマップを破棄する
    imagedestroy($image);
}


/**
 * コマンドラインで、引数なし(または引数書式エラー)の場合に表示するヘルプメッセージ
 */
function showUsage(): void
{
    $script = basename($_SERVER['PHP_SELF']);
    echo <<<EOT
使い方:
  php {$script} [-ls|-pt] [-s3024|-s4030|-s4535|-s5040] [-cs] input_filename output_filename
オプション:
  -ls      Land Scape 印画紙方向を横向き (デフォルト値)
  -pt      PorTrait 印画紙方向を縦向き
  -s3024   写真サイズ30mmx24mm (デフォルト値)
  -s4030   写真サイズ40mmx30mm
  -s4535   写真サイズ45mmx35mm
  -s5040   写真サイズ50mmx40mm
  -cs      写真サイズを少しずつ大きくする
  -ss{値}  スケール：1mmあたりのピクセル数（デフォルト値 : -ss23.01）
  input_filename   入力(読み込み)jpegファイル名
  output_filename  出力(保存)jpegファイル名

EOT;
}

/**
 * アップロードされたファイルの基本的情報(ファイルサイズ、アクセス権、jpegかどうか)をチェックする
 * 
 * @param array $file : POSTデータに格納されている $_FILES['userfile']
 * 
 * @return string : 正しいjpegファイルの場合アップロードされ保存された一時ファイル名、エラーの場合は空文字列('')
 */
function validateUploadedFile(array $file): string
{
    if (empty($file['name'])) {
        printHtmlMenupage();
        return '';
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        printHtmlErrorpage("ファイルのアップロード中にエラーが発生しました");
        return '';
    }

    if ($file['size'] === 0) {
        printHtmlErrorpage("アップロードされたファイルサイズが 0 です");
        return '';
    }

    if ($file['size'] > UPLOAD_FILESIZE_LIMIT) {
        printHtmlErrorpage("アップロード可能なファイルサイズは1.0MBytesです");
        return '';
    }

    if (!is_uploaded_file($file['tmp_name']) || !is_readable($file['tmp_name'])) {
        printHtmlErrorpage("アップロードされたファイルにアクセスできません");
        return '';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if ($mime !== 'image/jpeg') {
        printHtmlErrorpage("JPEG画像ファイル以外がアップロードされました（MIME: " . $mime . "）");
        return '';
    }

    return $file['tmp_name'];
}

/**
 * CLI起動時に、入力jpegファイルの基本的情報(ファイルサイズ、アクセス権、jpegかどうか)をチェックする
 * 
 * @param string $filepath : 入力jpegファイル名
 * 
 * @return bool : 正しいjpegファイルの場合 true, それ以外の場合 false
 */
function validateLocalFile(string $filepath): bool
{
    // ファイル存在と読み取り可能性
    if (!file_exists($filepath) || !is_file($filepath) || !is_readable($filepath)) {
        return false;
    }

    // ファイルサイズ検証 0 以上 UPLOAD_FILESIZE_LIMIT 未満
    $size = filesize($filepath);
    if ($size === false || $size <= 0 || $size > UPLOAD_FILESIZE_LIMIT) {
        return false;
    }

    // MIMEタイプ確認（finfoによる検査）
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return false; // MIMEタイプの判定に失敗
    }
    $mime = finfo_file($finfo, $filepath);
    finfo_close($finfo);

    return ($mime === 'image/jpeg');
}

/**
 * 画像の縦横サイズや顔写真サイズなど、画像生成に必要な設定値をform POSTデータより読み込む
 */
function parseWebFormOption(): void
{

    // 印画紙のサイズ（横サイズ, 縦サイズ）
    global $size_paper;
    // 写真の縦横比（横サイズ, 縦サイズ）
    global $size_aspect;
    // 1mmあたりのピクセル数
    global $size_scale;
    // 余白
    global $white_space;
    // サイズを変化させる場合 true
    global $sw_change_size;
    // 結果画像ファイルをダウンロードする場合 true
    global $sw_download_file;

    // プログラム引数を解析、変数に格納する
    if (isset($_POST["paper_orientation"])) {
        switch ($_POST["paper_orientation"]) {
            case "landscape":
                $size_paper = [3072, 2048];
                break;
            case "portrait":
                $size_paper = [2048, 3072];
                break;
        }
    }

    if (isset($_POST["photo_size"])) {
        switch ($_POST["photo_size"]) {
            case "30x24":
                $size_aspect = [24.0, 30.0];
                break;
            case "40x30":
                $size_aspect = [30.0, 40.0];
                break;
            case "45x35":
                $size_aspect = [35.0, 45.0];
                break;
            case "50x40":
                $size_aspect = [40.0, 50.0];
                break;
        }
    }

    if (isset($_POST["change_size"]) && $_POST["change_size"] === "on") {
        $sw_change_size = true;
    }

    if (isset($_POST["download_file"]) && $_POST["download_file"] === "on") {
        $sw_download_file = true;
    }

    if (isset($_POST['size_scale'])) {
        $raw_value = trim($_POST['size_scale']);

        // 数値チェック
        if (is_numeric($raw_value) && $raw_value >= 20.0 && $raw_value <= 25.0) {
            $size_scale = (float) $raw_value;
        }
    }
}

/**
 * 画像の縦横サイズや顔写真サイズなど、画像生成に必要な設定値をコマンドライン引数より読み込む
 * 
 * @param array $args : コマンドライン引数
 * 
 * @return array : array [string 入力jpegファイル名, string 出力jpegファイル名]
 */
function parseCliOption(array $args): array
{

    // 印画紙のサイズ（横サイズ, 縦サイズ）
    global $size_paper;
    // 写真の縦横比（横サイズ, 縦サイズ）
    global $size_aspect;
    // 1mmあたりのピクセル数
    global $size_scale;
    // 余白
    global $white_space;
    // サイズを変化させる場合 true
    global $sw_change_size;


    $validSwitches = ['-ls', '-pt', '-s3024', '-s4030', '-s4535', '-s5040', '-cs'];
    $filenames = [];

    foreach ($args as $arg) {
        if (substr($arg, 0, 1) === '-') {
            if (preg_match('/^-ss([0-9.]+)$/', $arg, $matches)) {
                $scaleVal = floatval($matches[1]);
                if ($scaleVal >= 20.0 && $scaleVal <= 25.0) {
                    // 1mmあたりのピクセル数は20.0以上25.0以下
                    $size_scale = $scaleVal;
                    continue;
                } else {
                    echo "エラー: -ss オプションの値が無効です ({$matches[1]})\n";
                    exit(1);
                }
            }
            if (!in_array($arg, $validSwitches)) {
                echo "未定義スイッチ {$arg} が指定されました\n";
                exit(1);
            }
            switch ($arg) {
                case '-ls':
                    $size_paper = [3072, 2048];
                    break;
                case '-pt':
                    $size_paper = [2048, 3072];
                    break;
                case '-s3024':
                    $size_aspect = [24.0, 30.0];
                    break;
                case '-s4030':
                    $size_aspect = [30.0, 40.0];
                    break;
                case '-s4535':
                    $size_aspect = [35.0, 45.0];
                    break;
                case '-s5040':
                    $size_aspect = [40.0, 50.0];
                    break;
                case '-cs':
                    $sw_change_size = true;
                    break;
            }
        } else {
            $filenames[] = $arg;
        }
    }

    if (count($filenames) !== 2) {
        echo "ファイル名が不足しています。input_filename と output_filename を指定してください。\n";
        exit(1);
    }

    // $input_filename = $filenames[0];
    // $output_filename = $filenames[1];
    return $filenames;
}

/**
 * 証明書写真画像(GdImage)オブジェクトを作成する
 * 
 * @param string $image_filepath : 作成する証明書写真画像に埋め込む、素材写真jpeg画像ファイルのファイル名
 * 
 * @return GdImage : 作成した証明書写真画像GdImageオブジェクト, エラーの場合はnull
 */
function imageProcess(string $image_filepath)
{

    // 印画紙のサイズ（横サイズ, 縦サイズ）
    global $size_paper;
    // 写真の縦横比（横サイズ, 縦サイズ）
    global $size_aspect;
    // 1mmあたりのピクセル数
    global $size_scale;
    // 余白
    global $white_space;
    // サイズを変化させる場合 true
    global $sw_change_size;

    // 素材写真jpeg画像ファイルの縦横サイズ（ピクセル）を得る
    $size = getimagesize($image_filepath);

    // 素材写真jpeg画像ファイルで許容されるピクセル数以内であるかチェック
    if ($size[0] < 100 || $size[1] < 100 || $size[0] > SOURCE_IMAGE_SIZE_LIMIT || $size[1] > SOURCE_IMAGE_SIZE_LIMIT) {
        printHtmlErrorpage("アップロード可能な画像は縦横それぞれ100px以上、" . SOURCE_IMAGE_SIZE_LIMIT . "px未満です");
        return null;
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
    $image_src = imagecreatefromjpeg($image_filepath);


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

    // ビットマップを破棄する
    imagedestroy($image_src);
    // 完成したGdImageを返す
    return $image;
}


/**
 * PHPスクリプトがコンソールから呼ばれたかどうかを判定
 * 
 * @return bool : コンソールから呼ばれた場合は True, それ以外(Webなど)の場合はFalse
 */
function isCli(): bool
{
    return (php_sapi_name() === 'cli');
}

// ********* MAIN

if (isCli()) {
    main_cli();
} else {
    main_web();
}

exit();


?>