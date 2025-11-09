<?php

/**
 * ファイルアップローダPHPスクリプト
 * 
 * @author INOUE. Hirokazu
 * @version 1.1 (2025/09/02) : mime-type svg対応
 *          1.0 (2025/08/10)
 * @link https://oasis3855.github.io/webpage/
 * @link https://oasis.halfmoon.jp/
 */

// JSON設定ファイル、ログファイルの絶対パス
// (JSON設定ファイルは、コマンドラインで実行すると自動的に作成されます)
$configFilepath = __DIR__ . "/config/config.json";
$logFilepath = __DIR__ . "/log/log.txt";

// ***********
// JSON設定ファイルから読み込む変数
$base_url = "https://www.example.com";
$base_dir = "/var/www/html";    // (ローカルディスクの)ベースディレクトリ
$array_subdir = [];             // (相対)サブディレクトリの配列
$filename_ascii_only = false;   // アップロードするファイル名は英数(ASCII)のみ:true, 日本語も許可:false
$max_upload_size = 10485760;    // アップロードするファイルの最大容量 デフォルトは 10MB
$mimetype_fileext_limit = true; // ファイルのMIMEタイプと拡張子でアップロードするファイルを制限するかどうか
$flag_overwrite = false;        // Web Form初期値設定 既存ファイルが存在しても上書きするかどうか
$flag_no_alt_filename = true;   // Web Form初期値設定 alt属性にファイル名を使用するかどうか
$flag_target_blank = false;     // Web Form初期値設定 ファイル（画像）を新しいウインドウで開くかどうか
$flag_fancybox = true;          // Web Form初期値設定 fancybox対応（&lt;a rel=... title=...&gt;）を有効にするかどうか
$flag_preview_abs = false;      // Web Form初期値設定 画像のプレビューを絶対URLで表示するかどうか
$default_image_size = 320;      // Web Form初期値設定 画像表示サイズ(px)
$default_dir_index = 0;         // Web Form初期値設定 array_subdirの選択中index no
// ***********

// 認証コンポーネントを用いる
// (認証システムは、このパッケージに含まれていません。別途、ユーザ環境のものを呼び出してください)
$USER_HOME_DIR = posix_getpwuid(posix_geteuid())['dir'];
require_once($USER_HOME_DIR . '/auth/auth.php');

/**
 * HTMLのホーム画面を出力
 * 
 * @param string $mode 表示モード ('mainmenu', 'listmenu', 'fileinfo', 'viewlog', 'listdir', 'uploadfile')
 */
function printHtmlHome(string $mode = 'mainmenu'): void
{

    // スクリプトをリロードするための、このスクリプトの名前
    $scriptname = basename($_SERVER['PHP_SELF']);

    echo <<<EOT_HEADER
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8" />
    <title>Web File Uploader</title>
    <script type="text/javascript" src="../utf.js"></script>
    <script type="text/javascript" src="../md5.js"></script>
    <script type="text/javascript" src="../authpage_form_md5.js"></script>
    <link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>
EOT_HEADER;

    // 認証コンポーネントを用いる
    // $USER_HOME_DIR = posix_getpwuid(posix_geteuid())['dir'];
    // require_once($USER_HOME_DIR . '/auth/auth.php');

    // ユーザ認証を行う
    $boolAuthResult = func_check_auth(basename($_SERVER['PHP_SELF']), 'web-uploader-' . basename($_SERVER['PHP_SELF']), True, 'UTF-8');
    if (!$boolAuthResult) {
        echo "<p class=\"error\">認証が行われていません。またはCookieが使えない状況です。<br />\n";
        echo "<a href=\"{$scriptname}\">再度ログオン画面を表示する</a></p>\n";
        echo "</body>\n</html>\n";
        return;
    }


    echo <<<EOT_H1
    <div style="height:65px; width:100%; padding:0px; margin:0px;"> 
        <p><span style="margin:0px 20px; font-size:30px; font-weight:lighter;">Web File Uploader</span><span style="margin:0px 0px; font-size:25px; font-weight:lighter; color:lightgray;">file uploader for webpage</span></p> 
    </div> 
EOT_H1;


    $dt = new DateTime();
    $currentDateTime = $dt->format('Y/m/d H:i:s');
    $php_version = phpversion();
    echo <<<EOT_LEFT
    <div id="main_content_left">
        <h2>System</h2>
        <p>PHP Version{$php_version}</p>
        <p>{$currentDateTime}</p>
        <h2>Menu</h2>
        <ul>
        <li><a href="{$scriptname}">Home (Upload)</a></li>
        <li><a href="{$scriptname}?mode=listmenu">List Directory</a></li>
        <li><a href="{$scriptname}?mode=viewlog">Show Upload Log</a></li>
        <li><a href="{$scriptname}?mode=viewauthlog">Show Authenticate Log</a></li>
        <li><a href="{$scriptname}?mode=logoff">Logoff</a></li>
        </ul>
    </div>	<!-- id="main_content_left" -->
EOT_LEFT;

    echo "    <div id=\"main_content_right\">\n";
    if ($mode === 'mainmenu') {
        printHtml_MainMenu();
    } elseif ($mode === 'listmenu') {
        printHtml_ListMenu();
    } elseif ($mode === 'fileinfo') {
        printHtml_FileInfo();
    } elseif ($mode === 'viewlog') {
        printHtml_ViewLog();
    } elseif ($mode === 'viewauthlog') {
        $authlog = func_log_disp();
        echo "<pre>\n{$authlog}\n</pre>\n";
    } elseif ($mode === 'listdir') {
        printHtml_ListDir();
    } elseif ($mode === 'uploadfile') {
        // アップロードファイルの処理
        $array_result = uploadFile();
        if (!$array_result[0]) {
            echo "<div id=\"main_content_right\">\n<p class=\"error\">{$array_result[1]}<p/>\n</div>\n";
        } else {
            printHtml_FileInfo($array_result[1]);
        }
    } else {
        // デフォルトはメインメニュー
        printHtml_MainMenu();
    }
    echo "        <p>&nbsp;</p>\n    </div>    <!-- id=\"main_content_right\" -->\n";


    echo <<<EOT_FOOTER
    <p>&nbsp;</p> 
    <div class="clear"></div> 
    <div id="footer"> 
        <p><a href="https://github.com/oasis3855/webservice-script/tree/main/web-uploader">Web Uploader</a> version 1.0 &nbsp;&nbsp; GNU GPL free software</p> 
    </div>	<!-- id="footer" --> 
</body>
</html>
EOT_FOOTER;
}

/**
 * メインメニュー（アップロードメニュー）のHTMLを出力
 * 
 * @return void
 */
function printHtml_MainMenu(): void
{
    // ***********
    // JSON設定ファイルから読み込む変数
    global $base_url;
    global $base_dir;
    global $array_subdir;
    global $filename_ascii_only;
    global $max_upload_size;
    global $mimetype_fileext_limit;
    global $flag_overwrite;
    global $flag_no_alt_filename;
    global $flag_target_blank;
    global $flag_fancybox;
    global $flag_preview_abs;
    global $default_image_size;
    global $default_dir_index;
    // ***********

    // スクリプトをリロードするための、このスクリプトの名前
    $scriptname = basename($_SERVER['PHP_SELF']);

    $str_upload_dir_form = "\n";
    foreach ($array_subdir as $index => $dir) {
        $checked = ($index === $default_dir_index) ? 'checked="checked"' : '';
        $str_upload_dir_form .=
            "            &nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"dir\" value=\"{$index}\" {$checked} />" .
            htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . "<br />\n";
    }

    // input checkboxの状態を設定
    $checked_overwrite = $flag_overwrite ? 'checked' : '';
    $checked_flag_no_alt_filename = $flag_no_alt_filename ? 'checked' : '';
    $checked_target_blank = $flag_target_blank ? 'checked' : '';
    $checked_fancybox = $flag_fancybox ? 'checked' : '';
    $checked_preview_abs = $flag_preview_abs ? 'checked' : '';

    $max_upload_size_str = $max_upload_size > 0 ? number_format($max_upload_size / 1024) . ' kBytes' : '無制限';

    echo <<<EOT_RIGHT
        <h1>Home Screen / Upload File (ファイルのアップロード)</h1>
        <p>画像やデータファイルをアップロード出来ます<br />
        <span class="small">( ファイルサイズ制限 : {$max_upload_size_str}, ファイル名文字制限 : {$filename_ascii_only}, ファイル種別・拡張子制限 : {$mimetype_fileext_limit})</span></p>
        <form method="post" action="{$scriptname}?mode=upload" enctype="multipart/form-data">
            <p>対象ファイルを指定します<br />
            &nbsp;&nbsp;&nbsp;&nbsp;<input type="file" name="uploadfile" value="" size="50" /><br />
            &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="overwrite" value="enable" {$checked_overwrite} />既存ファイルが存在しても上書きする</p>
            <p>アップロード先ディレクトリを選択します<br />{$str_upload_dir_form}
            <div class="inbox">
            <table border="0">
                <tr><td colspan="2">webでの表現方法の設定
                <!-- Control value -->
                <input type="hidden" name="detect_form_post" value="on" /></td></tr>
                <tr><td>画像表示サイズ</td><td><input type="text" name="size" value="{$default_image_size}" size="15" /> (px) 
                &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="size_base" value="long" checked="checked" />長辺
                &nbsp;&nbsp;<input type="radio" name="size_base" value="v" />縦
                &nbsp;&nbsp;<input type="radio" name="size_base" value="h" />横
                &nbsp;&nbsp;<input type="radio" name="size_base" value="off" />OFF（実寸）</td></tr>
                <tr><td>alt属性値</td><td><input type="text" name="alt" value="" size="30" />&nbsp;<input type="checkbox" name="alt_fname" value="enable" {$checked_flag_no_alt_filename} />空欄の時はファイル名を利用</td></tr>
                <tr><td>title属性値</td><td><input type="text" name="title" value="" size="30" /> (空欄の時は属性削除) </td></tr>
                <tr><td>その他</td><td><input type="checkbox" name="target" value="blank" {$checked_target_blank} />ファイル（画像）を新しいウインドウで開く&nbsp;&nbsp;<input type="checkbox" name="fancybox" value="on" {$checked_fancybox} />fancybox対応（&lt;a rel=... title=...&gt;）&nbsp;&nbsp;<input type="checkbox" name="preview_abs" value="on" {$checked_preview_abs} />画像プレビューを絶対URL</td></tr>
            </table>
            </div>
            <p><input type="submit" value="アップロード" /></p>
        </form>
EOT_RIGHT;
}

/**
 * ファイル一覧メニュー(ディレクトリの選択と検索フィルター)のHTMLを出力
 * 
 * @return void
 */
function printHtml_ListMenu(): void
{
    // ***********
    // JSON設定ファイルから読み込む変数
    global $base_url;
    global $base_dir;
    global $array_subdir;
    global $filename_ascii_only;
    global $max_upload_size;
    global $mimetype_fileext_limit;
    global $flag_overwrite;
    global $flag_no_alt_filename;
    global $flag_target_blank;
    global $flag_fancybox;
    global $flag_preview_abs;
    global $default_image_size;
    global $default_dir_index;
    // ***********

    // スクリプトをリロードするための、このスクリプトの名前
    $scriptname = basename($_SERVER['PHP_SELF']);

    $str_upload_dir_form = "\n";
    foreach ($array_subdir as $index => $dir) {
        $checked = ($index === $default_dir_index) ? 'checked="checked"' : '';
        $str_upload_dir_form .=
            "            &nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"dir\" value=\"{$index}\" {$checked} />" .
            htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . "<br />\n";
    }

    echo <<<EOT_RIGHT
        <h1>File List (ファイル一覧)</h1>
        <form method="post" action="{$scriptname}?mode=listdir">
        <p>一覧を表示するディレクトリを選択します<br />{$str_upload_dir_form}
        </p>
        <p>検索条件を設定します<br />
        &nbsp;&nbsp;&nbsp;&nbsp;ファイル検索マスク : <input type="text" name="mask" value="*" size="30" /><br />
        &nbsp;&nbsp;&nbsp;&nbsp;ソート順 : &nbsp;&nbsp;<input type="radio" name="sort" value="fname_asc" checked="checked" />ファイル名A...Z&nbsp;&nbsp;<input type="radio" name="sort" value="fname_desc" />ファイル名Z...A&nbsp;&nbsp;<input type="radio" name="sort" value="off" />無し</p><input type="submit" value="指定したディレクトリの一覧を表示する" />
        </form>
EOT_RIGHT;
}

/**
 * 指定されたディレクトリのファイル一覧を表示する
 * 
 * @return void
 */
function printHtml_ListDir(): void
{
    // ***********
    // JSON設定ファイルから読み込む変数
    global $base_url;
    global $base_dir;
    global $array_subdir;
    global $filename_ascii_only;
    global $max_upload_size;
    global $mimetype_fileext_limit;
    global $flag_overwrite;
    global $flag_no_alt_filename;
    global $flag_target_blank;
    global $flag_fancybox;
    global $flag_preview_abs;
    global $default_image_size;
    global $default_dir_index;
    // ***********

    // スクリプトをリロードするための、このスクリプトの名前
    $scriptname = basename($_SERVER['PHP_SELF']);

    // --- POST値の取得 ---
    $dir_index = isset($_POST['dir']) ? intval($_POST['dir']) : 0;
    $mask      = isset($_POST['mask']) ? trim($_POST['mask']) : '*';
    $sort_mode = $_POST['sort'] ?? 'off';
    // --- ディレクトリの決定 ---
    $rel_dir  = is_string($array_subdir[$dir_index]) ? $array_subdir[$dir_index] : '';
    $fullpath = realpath($base_dir . '/' . $rel_dir);
    // --- 安全性チェック ---
    if (!$fullpath || !is_dir($fullpath) || strpos($fullpath, realpath($base_dir)) !== 0) {
        echo "        <p>指定されたディレクトリ{$rel_dir}が無効です。</p>\n";
        return;
    }
    // --- ファイル一覧の取得 ---
    $files = glob($fullpath . DIRECTORY_SEPARATOR . $mask, GLOB_NOSORT);
    if ($files === false) {
        echo "        <p>ディレクトリ{$rel_dir}のファイル一覧の取得に失敗しました。</p>\n";
        return;
    }

    // --- ファイル情報抽出 ---
    $info_list = [];
    foreach ($files as $filepath) {
        if (!is_file($filepath)) continue;

        $stat = stat($filepath);
        $info_list[] = [
            'size' => $stat['size'],
            'mtime' => date('Y-m-d H:i:s', $stat['mtime']),
            'name' => basename($filepath),
            'perms' => getFilePermissionString($filepath)
        ];
    }
    // --- ソート ---
    switch ($sort_mode) {
        case 'fname_asc':
            usort($info_list, fn($a, $b) => strcmp($a['name'], $b['name']));
            break;
        case 'fname_desc':
            usort($info_list, fn($a, $b) => strcmp($b['name'], $a['name']));
            break;
        case 'off':
        default:
            // 順不同
            break;
    }

    // --- 表示 ---
    echo "        <h1>File List (ファイル一覧)</h1>\n";
    // --- 条件表示 ---
    echo "        <p>対象ディレクトリ : {$array_subdir[$dir_index]}<br />\n";
    echo "        ファイル検索マスク : {$mask}<br />\n";
    echo "        ソート : {$sort_mode}</p>\n";
    echo "        <pre>\n      size     time                filename\n";
    $count = 0;
    foreach ($info_list as $entry) {
        printf(
            "%s %9d %s <a href=\"%s\">%s</a>\n",
            $entry['perms'],
            $entry['size'],
            $entry['mtime'],
            "{$scriptname}?mode=fileinfo&amp;dir={$dir_index}&amp;filename=" . rawurlencode($entry['name']),
            htmlspecialchars($entry['name'], ENT_QUOTES, 'UTF-8')
        );
        if (++$count >= 100) {
            echo "\n\n100件以上は省略しました\n";
            break;
        }
    }
    echo "        </pre>\n";
}

/**
 * ファイル情報を表示する(画像の場合は画像も表示)
 * 
 * @param string $uploaded_filename アップロード後のファイル名。空文字列の場合はGET/POSTから取得
 * @return void
 */
function printHtml_FileInfo(string $uploaded_filename = ''): void
{
    // ***********
    // JSON設定ファイルから読み込む変数
    global $base_url;
    global $base_dir;
    global $array_subdir;
    global $filename_ascii_only;
    global $max_upload_size;
    global $mimetype_fileext_limit;
    global $flag_overwrite;
    global $flag_no_alt_filename;
    global $flag_target_blank;
    global $flag_fancybox;
    global $flag_preview_abs;
    global $default_image_size;
    global $default_dir_index;
    // ***********

    // スクリプトをリロードするための、このスクリプトの名前
    $scriptname = basename($_SERVER['PHP_SELF']);

    // --- パラメータ取得 ---
    $dir_index = (isset($_GET['dir']) && is_string($_GET['dir'])) ?
        intval($_GET['dir']) : ((isset($_POST['dir']) && is_string($_POST['dir'])) ? intval($_POST['dir']) : -1);
    $filename  = (isset($_GET['filename']) && is_string($_GET['filename'])) ?
        $_GET['filename'] : ((isset($_POST['filename']) && is_string($_POST['filename'])) ? $_POST['filename'] : '');
    if ($uploaded_filename !== '') $filename = $uploaded_filename; // アップロード後のファイル名を優先
    $size_base = isset($_POST['size_base']) && is_string($_POST['size_base']) ? ($_POST['size_base']) : 'long'; // POSTから取得。デフォルトは "long"
    $size = (isset($_POST['size']) && is_numeric($_POST['size'])) ? intval($_POST['size']) : $default_image_size; // POSTから取得。デフォルトは 320px
    $image_alt = isset($_POST['alt']) && is_string($_POST['alt']) ? ($_POST['alt']) : '';
    $image_title = isset($_POST['title']) && is_string($_POST['title']) ? ($_POST['title']) : '';
    if (isset($_POST['detect_form_post']) && $_POST['detect_form_post'] === 'on') {
        // このfunctionがform POSTで呼び出された場合、各global変数のデフォルト値をform input checkboxの選択で上書きする
        $flag_no_alt_filename = isset($_POST['alt_fname']) && $_POST['alt_fname'] === 'enable' ? true : false;  // alt_fnameがPOSTされている場合、空欄の時はファイル名を利用する
        $flag_fancybox = isset($_POST['fancybox']) && $_POST['fancybox'] === 'on' ? true : false;       // fancybox対応
        $flag_target_blank = isset($_POST['target']) && $_POST['target'] === 'blank' ? true : false;    // 新しいウインドウで開くかどうか
        $flag_preview_abs = isset($_POST['preview_abs']) && $_POST['preview_abs'] === 'on' ? true : false;      // 画像プレビューを絶対URLとするかどうか
    }

    if (!isset($array_subdir[$dir_index]) || $filename === '') {
        echo "        <p>ディレクトリまたはファイルのURL送信データが無効です。</p>\n";
        return;
    }

    // input checkboxの状態を設定
    $checked_flag_no_alt_filename = $flag_no_alt_filename ? 'checked' : '';
    $checked_target_blank = $flag_target_blank ? 'checked' : '';
    $checked_fancybox = $flag_fancybox ? 'checked' : '';
    $checked_preview_abs = $flag_preview_abs ? 'checked' : '';


    // URLエンコードされて送られてきたファイル名をデコードする
    $filename = rawurldecode($filename);
    // 汚染除去
    $safe_filename = basename($filename); // ディレクトリトラバーサル防止
    $target_dir    = realpath($base_dir . $array_subdir[$dir_index]);
    $target_file   = $target_dir . DIRECTORY_SEPARATOR . $safe_filename;
    // Web表示用のURL
    $file_url = $base_url . $array_subdir[$dir_index] . '/' . rawurlencode($safe_filename);
    $file_url_rel = $array_subdir[$dir_index] . '/' . rawurlencode($safe_filename);

    if (!is_file($target_file)) {
        echo "        <p>指定されたファイル{$array_subdir[$dir_index]} / {$safe_filename}が存在しません。</p>\n";
        return;
    }

    $stat  = stat($target_file);    // ファイルの統計情報(['size'],['mtime']等)を取得
    $perms = getFilePermissionString($target_file);     // 標準的なパーミッション表示文字列（例: -rw-r--r--）
    $image_info = @getimagesize($target_file);          // 画像情報 ([0]width, [1]height, [2]type, [3]html_img_tag) を取得

    // $quoted_filename = htmlspecialchars($safe_filename, ENT_QUOTES, 'UTF-8');
    $quoted_filename = rawurlencode($safe_filename);    // URLエンコードされたファイル名(' '->%20, 'あ'->%E3%81%82, etc.)
    $formatted_file_size = number_format($stat['size']);
    $formatted_datetime = date('Y/m/d H:i:s', $stat['mtime']);

    if ($flag_no_alt_filename && $image_alt === '') {
        // alt属性が空欄で、alt_fnameが有効な場合はファイル名を代入
        $image_alt = htmlspecialchars($safe_filename, ENT_QUOTES, 'UTF-8');
    } else {
        // alt属性が空欄で、alt_fnameが無効な場合は空欄のまま
        $image_alt = htmlspecialchars($image_alt, ENT_QUOTES, 'UTF-8');
    }

    $image_target_html = '';
    if ($flag_target_blank) {
        $image_target_html = " target=\"_blank\"";
    }

    $image_title_html = '';
    if ($image_title != '') {
        $image_title = htmlspecialchars($image_title, ENT_QUOTES, 'UTF-8');
        $image_title_html = " title=\"{$image_title}\"";
    }

    $image_fancybox_html = '';
    if ($flag_fancybox) {
        if ($image_title != '') $image_fancybox_html = " rel=\"lightbox_group\" title=\"{$image_title}\"";
        else $image_fancybox_html = " rel=\"lightbox_group\"";
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $target_file);
    finfo_close($finfo);

    echo "        <h1>File Information (ファイル情報)</h1>\n";

    // ******* DEBUG
    echo "<!-- Debug Information (form POST parameter)\n";
    print_r($_POST);
    echo "-->\n";
    // ******* DEBUG

    // --- JPEG(1), GIF(2), PNG(3) 以外は、ファイル情報のみ表示（img src=... 画像表示なし） ---
    if (!is_array($image_info) || !isset($image_info[0], $image_info[1], $image_info[2]) || !in_array($image_info[2], [1, 2, 3], true)) {
        echo <<<EOT_NONIMAGE
        <form method="post" action="{$scriptname}?mode=fileinfo" class="inbox">
            <table border="0">
                <tr><td colspan="2">webでの表現方法の設定</td></tr>
                <tr><td>元ファイルの情報</td><td>属性 {$perms} サイズ {$formatted_file_size} bytes 日時 {$formatted_datetime} MIME {$mime_type}
                <!-- Control value : directory index, filename -->
                <input type="hidden" name="dir" value="{$dir_index}" />
                <input type="hidden" name="filename" value="{$quoted_filename}" />
                <input type="hidden" name="detect_form_post" value="on" /></td></tr>
                <tr><td>その他</td><td><input type="checkbox" name="target" value="blank" {$checked_target_blank} />ファイル（画像）を新しいウインドウで開く</td></tr>
            </table>
            <input type="submit" value="表示条件を変更して再表示する" />
        </form>
        <pre class="fold">
&lt;a href="{$file_url_rel}" {$image_target_html}&gt;{$safe_filename}をダウンロードする&lt;/a&gt;
</pre>
<pre class="fold">
&lt;a href="{$file_url}" {$image_target_html}&gt;{$safe_filename}をダウンロードする&lt;/a&gt;
</pre>
        <p>Web上での表示例</p>
        <p><a href="{$file_url}" {$image_target_html}>{$safe_filename}をダウンロードする</a></p>
EOT_NONIMAGE;
        return;
    }

    $w = $image_info[0];
    $h = $image_info[1];

    // --- 表示サイズの計算 ---
    switch ($size_base) {
        case 'long':
            if ($w >= $h) {
                $scaled_w = $size;
                $scaled_h = intval($h * ($size / $w));
            } else {
                $scaled_h = $size;
                $scaled_w = intval($w * ($size / $h));
            }
            break;

        case 'v': // 縦
            $scaled_h = $size;
            $scaled_w = intval($w * ($size / $h));
            break;

        case 'h': // 横
            $scaled_w = $size;
            $scaled_h = intval($h * ($size / $w));
            break;

        case 'off': // 実寸
        default:
            $scaled_w = $w;
            $scaled_h = $h;
            break;
    }

    // form input radio のチェック項目 htmlコード
    $checked_size_base = [
        'long' => '',
        'v'    => '',
        'h'    => '',
        'off'  => ''
    ];
    $checked_size_base[$size_base] = 'checked="checked"';

    $html_code_rel = <<<HTML_CODE_REL
<a href="{$file_url_rel}" {$image_target_html} {$image_fancybox_html}><img src="{$file_url_rel}" width="{$scaled_w}" height="{$scaled_h}" alt="{$image_alt}" {$image_title_html}/></a>
HTML_CODE_REL;

    $html_code_abs = <<<HTML_CODE_ABS
<a href="{$file_url}" {$image_target_html} {$image_fancybox_html}><img src="{$file_url}" width="{$scaled_w}" height="{$scaled_h}" alt="{$image_alt}" {$image_title_html}/></a>
HTML_CODE_ABS;

    $html_code_photoframe_rel = <<<HTML_CODE_PHOTOFRAME_REL
<div class="clear"></div>
<div class="photo_left">
{$html_code_rel}<br /><br />
{$image_title}<br />
</div>
HTML_CODE_PHOTOFRAME_REL;

    $html_code_photoframe_abs = <<<HTML_CODE_PHOTOFRAME_ABS
<div class="clear"></div>
<div class="photo_left">
{$html_code_abs}<br /><br />
{$image_title}<br />
</div>
HTML_CODE_PHOTOFRAME_ABS;

    $html_code_preview = $html_code_photoframe_rel;
    if ($flag_preview_abs) $html_code_preview = $html_code_photoframe_abs;


    $escaped_html_code_rel = htmlspecialchars($html_code_rel, ENT_QUOTES, 'UTF-8');
    $escaped_html_code_abs = htmlspecialchars($html_code_abs, ENT_QUOTES, 'UTF-8');
    $escaped_html_code_photoframe_rel = htmlspecialchars($html_code_photoframe_rel, ENT_QUOTES, 'UTF-8');
    $escaped_html_code_photoframe_abs = htmlspecialchars($html_code_photoframe_abs, ENT_QUOTES, 'UTF-8');

    $html_exif_dump = '';
    if (file_exists($target_file) && mime_content_type($target_file) === 'image/jpeg') {
        $exif = @exif_read_data($target_file, 'ANY_TAG', true);
        $target_sections = ['IFD0', 'EXIF', 'GPS'];
        if ($exif !== false) {
            foreach ($target_sections as $section) {
                if (!isset($exif[$section])) continue;
                foreach ($exif[$section] as $key => $val) {
                    if (is_array($val)) {
                        $val = implode(', ', $val);
                    }
                    $html_exif_dump .= "{$section}: {$key} = {$val}\n";
                }
            }
            $html_exif_dump = "<details><summary style=\"color:gray; font-size:0.8em;\">Exifデータ</summary><pre>"
                . htmlspecialchars($html_exif_dump, ENT_QUOTES, 'UTF-8') . "</pre></details>";
        }
    }

    echo <<<EOT_IMAGE
        <form method="post" action="{$scriptname}?mode=fileinfo" class="inbox">
            <table border="0">
                <tr><td colspan="2">webでの表現方法の設定</td></tr>
                <tr><td>元画像の情報</td><td>横(x)={$w}, 縦(y)={$h}, 属性 {$perms} サイズ {$formatted_file_size} bytes 日時 {$formatted_datetime} MIME {$mime_type}
                <!-- Control value : directory index, filename -->
                <input type="hidden" name="dir" value="{$dir_index}" />
                <input type="hidden" name="filename" value="{$quoted_filename}" />
                <input type="hidden" name="detect_form_post" value="on" /></td></tr>
                <tr><td>画像表示サイズ</td><td><input type="text" name="size" value="{$size}" size="15" /> (px) 
                &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="size_base" value="long" {$checked_size_base['long']} />長辺
                &nbsp;&nbsp;<input type="radio" name="size_base" value="v" {$checked_size_base['v']} />縦
                &nbsp;&nbsp;<input type="radio" name="size_base" value="h" {$checked_size_base['h']} />横
                &nbsp;&nbsp;<input type="radio" name="size_base" value="off" {$checked_size_base['off']} />OFF（実寸）</td></tr>
                <tr><td>alt属性値</td><td><input type="text" name="alt" value="{$image_alt}" size="30" />&nbsp;<input type="checkbox" name="alt_fname" value="enable" {$checked_flag_no_alt_filename} />空欄の時はファイル名を利用</td></tr>
                <tr><td>title属性値</td><td><input type="text" name="title" value="{$image_title}" size="30" /> (空欄の時は属性削除) </td></tr>
                <tr><td>その他</td><td><input type="checkbox" name="target" value="blank" {$checked_target_blank}/>ファイル（画像）を新しいウインドウで開く&nbsp;&nbsp;<input type="checkbox" name="fancybox" value="on" {$checked_fancybox} />fancybox対応（&lt;a rel=... title=...&gt;）&nbsp;&nbsp;<input type="checkbox" name="preview_abs" value="on" {$checked_preview_abs} />画像プレビューを絶対URL</td></tr>
            </table>
            <input type="submit" value="表示条件を変更して再表示する" />
        </form>
        {$html_exif_dump}
        <details open>
            <summary style="color:gray; font-size:0.8em;">HTMLコード（相対URL）</summary>
            <pre style="white-space: pre-wrap;">
{$escaped_html_code_rel}
</pre>
        </details>
        <details>
            <summary style="color:gray; font-size:0.8em;">HTMLコード（絶対URL）</summary>
            <pre style="white-space: pre-wrap;">
{$escaped_html_code_abs}
</pre>
        </details>
        <details>
            <summary style="color:gray; font-size:0.8em;">HTMLコード（フォトフレーム表示 相対URL）</summary>
            <pre style="white-space: pre-wrap;">
{$escaped_html_code_photoframe_rel}
</pre>
        </details>
        <details>
            <summary style="color:gray; font-size:0.8em;">HTMLコード（フォトフレーム表示 絶対URL）</summary>
            <pre style="white-space: pre-wrap;">
{$escaped_html_code_photoframe_abs}
</pre>
        </details>
        <p>Web上での表示例</p>
        {$html_code_preview}
        
EOT_IMAGE;
}

/**
 * アップロードログを表示する
 * 
 * @return void
 */
function printHtml_ViewLog(): void
{
    global $logFilepath;

    if (!file_exists($logFilepath)) {
        echo "        <p>ログファイルが存在しません。</p>\n";
        return;
    }

    $lines = file($logFilepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recent = array_slice(array_reverse($lines), 0, 100);

    echo "        <h1>Upload Log (アップロードログ)</h1>\n";
    echo "        <pre class=\"scroll-x\">\n";
    foreach ($recent as $line) {
        echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "\n";
    }
    echo "        </pre>\n";
    echo "        <p>最新の100件のログを表示しています。</p>\n";
    return;
}

/**
 * ファイルパーミッションを -rwxrwxrwx 形式で返す
 *
 * @param string $filepath 対象ファイルパス
 * @return string 標準的なパーミッション表示文字列（例: -rw-r--r--）
 */
function getFilePermissionString(string $filepath): string
{
    $perms = fileperms($filepath);

    // ファイル種別
    $info = '';
    switch ($perms & 0xF000) {
        case 0xC000:
            $info = 's';
            break; // ソケット
        case 0xA000:
            $info = 'l';
            break; // シンボリックリンク
        case 0x8000:
            $info = '-';
            break; // 通常ファイル
        case 0x6000:
            $info = 'b';
            break; // ブロック型
        case 0x4000:
            $info = 'd';
            break; // ディレクトリ
        case 0x2000:
            $info = 'c';
            break; // キャラクタ型
        case 0x1000:
            $info = 'p';
            break; // FIFOパイプ
        default:
            $info = 'u';
            break; // 不明
    }

    // 権限ビット（owner/group/other）
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
        (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));

    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
        (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));

    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
        (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));

    return $info;
}

/**
 * ファイルアップロード処理
 * 
 * @return array 成功時は [true, 保存したファイル名]、失敗時は [false, エラーメッセージ]
 */
function uploadFile(): array
{
    // ***********
    // JSON設定ファイルから読み込む変数
    global $base_url;
    global $base_dir;
    global $array_subdir;
    global $filename_ascii_only;
    global $max_upload_size;
    global $mimetype_fileext_limit;
    global $flag_overwrite;
    global $flag_no_alt_filename;
    global $flag_target_blank;
    global $flag_fancybox;
    global $flag_preview_abs;
    global $default_image_size;
    global $default_dir_index;
    // ***********

    // スクリプトをリロードするための、このスクリプトの名前
    $scriptname = basename($_SERVER['PHP_SELF']);

    // --- パラメータ取得 ---
    $dir_index = (isset($_GET['dir']) && is_string($_GET['dir'])) ?
        intval($_GET['dir']) : ((isset($_POST['dir']) && is_string($_POST['dir'])) ? intval($_POST['dir']) : -1);

    if (!isset($array_subdir[$dir_index])) {
        return [false, "無効なディレクトリ指定です"];
    }

    $target_dir    = realpath($base_dir . $array_subdir[$dir_index]);

    if (
        !$target_dir ||         // realpath失敗（存在しない or 解決不能）
        strpos($target_dir, realpath($base_dir)) !== 0 ||    // ベースディレクトリ外を指している(ディレクトリトラバース対策)
        !is_dir($target_dir)    // 実在しないディレクトリ
    ) {
        return [false, "指定されたディレクトリが無効です"];
    }
    // --- ファイル検証 ---
    if (!isset($_FILES['uploadfile']) || $_FILES['uploadfile']['error'] !== UPLOAD_ERR_OK) {
        return [false, "ファイルアップロードに失敗しました"];
    }

    $tmp_path = $_FILES['uploadfile']['tmp_name'];

    // --- ファイルサイズの検証($max_upload_size <= 0のときは制限なし) ---
    if ($max_upload_size > 0 && $_FILES['uploadfile']['size'] > $max_upload_size) {
        if (file_exists($tmp_path)) unlink($tmp_path);
        return [false, "ファイルサイズの制限値を超えています(" . number_format($_FILES['uploadfile']['size'] / 1024) . " kBytes > " . number_format($max_upload_size / 1024) . " kBytes)"];
    }

    // --- ファイルのMIMEタイプと拡張子でアップロードの制限 ---
    if ($mimetype_fileext_limit) {
        $array_result = uploadFile_checkMimeExt();
        if (!$array_result[0]) {
            if (file_exists($tmp_path)) unlink($tmp_path);
            return $array_result; // エラーメッセージを返す
        }
    }


    $orig_name = basename($_FILES['uploadfile']['name']);
    if ($filename_ascii_only && preg_match('/[^a-zA-Z0-9 _.-]/', $orig_name)) {
        return [false, "ファイル名はASCII文字・空白・アンダースコア・ドット・ダッシュのみが許可されています"];
    }
    // UTF-8文字で[^○△✕]（○△✕という文字以外）、ここでは英数・マイナス・ドット日本語以外の文字を、アンダースコアに置換する
    $safe_name = preg_replace('/[^a-zA-Z0-9_\-\.ぁ-んァ-ヶ一-龠]/u', '_', $orig_name); // 日本語対応＋安全化
    $save_path = $target_dir . DIRECTORY_SEPARATOR . $safe_name;

    // --- 上書き判定 ---
    $overwrite = (isset($_POST['overwrite']) && $_POST['overwrite'] === 'enable');
    if (!$overwrite && file_exists($save_path)) {
        if (file_exists($tmp_path)) unlink($tmp_path);
        return [false, "既存ファイルが存在します。上書きしません"];
    }

    // --- 保存処理 ---
    if (!move_uploaded_file($tmp_path, $save_path)) {
        if (file_exists($tmp_path)) unlink($tmp_path);
        return [false, "ファイル保存に失敗しました"];
    }

    uploadFile_writeLog("{$array_subdir[$dir_index]}/{$safe_name}");

    if (!is_file($save_path)) {
        return [false, "アップロードし保存したファイルが異常です"];
    }

    // JSON設定ファイルの更新 ($dir_indexのみ対象)
    writeJsonConfig($dir_index);

    return [true, $safe_name];
}

/**
 * MIMEタイプと拡張子のチェック
 * 
 * @return array 成功時は [true, '']、失敗時は [false, エラーメッセージ]
 */
function uploadFile_checkMimeExt(): array
{
    $tmp_path = $_FILES['uploadfile']['tmp_name'];
    $orig_name = $_FILES['uploadfile']['name'];
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

    // アップロードを許可する拡張子とMIMEタイプの対応表
    $allowed = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'svg'  => ['image/svg+xml'],
        'pdf'  => ['application/pdf'],
        'zip'  => ['application/zip'],
        '7z'   => ['application/x-7z-compressed'],
        'gz'   => ['application/gzip', 'application/x-gzip'],
        'tar'  => ['application/x-tar'],
        'tgz'  => ['application/gzip'],
        'xz'   => ['application/x-xz'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'xls'  => ['application/vnd.ms-excel'],
        'odt'  => ['application/vnd.oasis.opendocument.text'],
        'ods'  => ['application/vnd.oasis.opendocument.spreadsheet'],
        'csv'  => ['text/csv', 'text/plain'],
        'txt'  => ['text/plain'],
        'html' => ['text/html'],
        'gpx'  => ['application/gpx+xml', 'text/xml'],
        'kml'  => ['application/vnd.google-earth.kml+xml', 'text/xml'],
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $tmp_path);
    finfo_close($finfo);

    // 拡張子が許可されているか
    if (!isset($allowed[$ext])) {
        return [false, "許可されていないファイル拡張子です: {$ext}"];
    }

    // 拡張子が.txtの場合は、MIMEタイプがバイナリでないか
    if ($ext === 'txt') {
        if (isBinaryMime($mime_type)) return [false, "拡張子が .txt のファイルにバイナリ MIME が検出されました: {$mime_type}"];
        else return [true, '']; // バイナリが検出されないテキストファイル(スクリプト、プログラムソースコードなど)は許可
    }

    // MIMEタイプが拡張子に対応しているか
    if (!in_array($mime_type, $allowed[$ext], true)) {
        return [false, "拡張子とMIMEタイプが一致しません: {$ext} / {$mime_type}"];
    }

    // アップロード許可
    return [true, ''];
}

/**
 * アップロードログにメッセージを書き込む
 *
 * @param string $message ログに書き込むメッセージ
 * @return void
 */
function uploadFile_writeLog(string $message): void
{
    global $logFilepath;

    // ログファイルが存在しない場合は作成
    if (!file_exists($logFilepath)) {
        touch($logFilepath);
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $host = gethostbyaddr($ip);

    // ログファイルに追記
    file_put_contents($logFilepath, date('Y/m/d,H:i:s') . "," . $ip . "," . $host . "," . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * MIMEタイプがバイナリかどうかを判定する
 *
 * @param string $mime MIMEタイプ
 * @return bool バイナリMIMEならtrue、そうでなければfalse
 */
function isBinaryMime(string $mime): bool
{
    $binary_patterns = [
        'application/octet-stream',
        'application/x-*',
        'application/*-binary',
    ];

    // 検出したいバイナリのMIMEタイプ（主に実行ファイル）
    // 'application/octet-stream',
    // 'application/x-executable',
    // 'application/x-msdownload',
    // 'application/x-dosexec',
    // 'application/x-mach-binary',
    // 'application/x-elf',
    // 'application/x-sharedlib',
    // 'application/x-object',

    foreach ($binary_patterns as $pattern) {
        if (fnmatch($pattern, $mime)) {
            return true;
        }
    }
    return false;
}

/**
 * JSON設定ファイルを読み込み、グローバル変数に設定値を反映
 *
 * @return array 成功時は [true, '']、失敗時は [false, エラーメッセージ]
 */
function readJsonConfig(): array
{
    global $configFilepath;

    // ***********
    // JSON設定ファイルから読み込む変数
    global $base_url;
    global $base_dir;
    global $array_subdir;
    global $filename_ascii_only;
    global $max_upload_size;
    global $mimetype_fileext_limit;
    global $flag_overwrite;
    global $flag_no_alt_filename;
    global $flag_target_blank;
    global $flag_fancybox;
    global $flag_preview_abs;
    global $default_image_size;
    global $default_dir_index;
    // ***********

    // JSONファイルが存在し、読み込み可能かどうかを確認
    if (!is_file($configFilepath) || !is_readable($configFilepath)) {
        return [false, "JSON設定ファイルが存在しないか、読み込み不可です。このスクリプトをコマンドラインで実行すると、JSON設定ファイルを新規作成できます"];
    }
    // JSONファイルを読み込む
    $json = file_get_contents($configFilepath);
    if ($json === false) {
        return [false, "JSON設定ファイルの読み込みに失敗しました"];
    }
    // JSONをデコードして配列に変換
    $data_array = json_decode($json, true);
    if (!is_array($data_array)) {
        return [false, "JSON設定ファイルのdecodeができません。書式が不正です"];
    }

    if (isset($data_array['base_url'])) {
        $base_url = $data_array['base_url'];
    }
    if (isset($data_array['base_dir'])) {
        $base_dir = $data_array['base_dir'];
    }
    if (isset($data_array['array_subdir']) && is_array($data_array['array_subdir'])) {
        $array_subdir = $data_array['array_subdir'];
    }
    if (isset($data_array['filename_ascii_only']) && is_bool($data_array['filename_ascii_only'])) {
        $filename_ascii_only = $data_array['filename_ascii_only'] ? true : false;
    }
    if (isset($data_array['max_upload_size']) && is_int($data_array['max_upload_size'])) {
        $max_upload_size = intval($data_array['max_upload_size']);
    }
    if (isset($data_array['mimetype_fileext_limit']) && is_bool($data_array['mimetype_fileext_limit'])) {
        $mimetype_fileext_limit = $data_array['mimetype_fileext_limit'] ? true : false;
    }
    if (isset($data_array['flag_overwrite']) && is_bool($data_array['flag_overwrite'])) {
        $flag_overwrite = $data_array['flag_overwrite'] ? true : false;
    }
    if (isset($data_array['flag_no_alt_filename']) && is_bool($data_array['flag_no_alt_filename'])) {
        $flag_no_alt_filename = $data_array['flag_no_alt_filename'] ? true : false;
    }
    if (isset($data_array['flag_target_blank']) && is_bool($data_array['flag_target_blank'])) {
        $flag_target_blank = $data_array['flag_target_blank'] ? true : false;
    }
    if (isset($data_array['flag_fancybox']) && is_bool($data_array['flag_fancybox'])) {
        $flag_fancybox = $data_array['flag_fancybox'] ? true : false;
    }
    if (isset($data_array['flag_preview_abs']) && is_bool($data_array['flag_preview_abs'])) {
        $flag_preview_abs = $data_array['flag_preview_abs'] ? true : false;
    }
    if (isset($data_array['default_image_size']) && is_int($data_array['default_image_size'])) {
        $default_image_size = intval($data_array['default_image_size']);
    }
    if (isset($data_array['default_dir_index']) && is_int($data_array['default_dir_index'])) {
        $default_dir_index = intval($data_array['default_dir_index']);
    }

    // ベースディレクトリの存在チェック
    if (!is_dir($base_dir)) {
        return [false, "ベースディレクトリ{$base_dir}が存在しません"];
    }
    // サブディレクトリの個数チェック、選択中Noがサブディレクトリ個数以下であることのチェック
    if (count($array_subdir) === 0) {
        return [false, "サブディレクトリが設定されていません"];
    } elseif (count($array_subdir) <= $default_dir_index || $default_dir_index < 0) {
        return [false, "選択中のサブディレクトリNo={$default_dir_index}が範囲外です"];
    }
    // サブディレクトリのチェック
    foreach ($array_subdir as $index => $subdir) {
        if (!is_string($subdir) || trim($subdir) === '') {
            // サブディレクトリが空文字列
            return [false, "サブディレクトリ名が空文字列です"];
        } elseif (!is_dir(realpath($base_dir . $subdir))) {
            return [false, "サブディレクトリ" . htmlspecialchars($subdir, ENT_QUOTES, 'UTF-8') . "が存在しません"];
        } elseif (!is_writable(realpath($base_dir . $subdir))) {
            return [false, "サブディレクトリ" . htmlspecialchars($subdir, ENT_QUOTES, 'UTF-8') . "が書き込み不可です"];
        }
    }

    // 設定ファイル読み込みが正常に終了
    return [true, ''];
}

/**
 * JSON設定ファイルに現在の選択中のサブディレクトリNoを保存 
 * 
 * @param int $current_default_dir_index 現在の選択中のサブディレクトリNo
 * @return bool 成功時はtrue、失敗時はfalse
 */
function writeJsonConfig(int $current_default_dir_index): bool
{
    global $configFilepath;

    // JSONファイルが存在し、読み書き可能かどうかを確認
    if (!is_file($configFilepath) || !is_readable($configFilepath) || !is_writable($configFilepath)) {
        return false;
    }

    // JSONファイルを読み込む
    $json = file_get_contents($configFilepath);
    if ($json === false) {
        return false;
    }
    // JSONをデコードして配列に変換
    $data_array = json_decode($json, true);
    if (!is_array($data_array)) {
        return false;
    }

    // 書き込み対象のdefault_dir_indexがJSONに存在すれば、その値を読み出す
    $json_default_dir_index = -1;
    if (isset($data_array['default_dir_index']) && is_int($data_array['default_dir_index'])) {
        $json_default_dir_index = intval($data_array['default_dir_index']);
    }

    if ($json_default_dir_index != $current_default_dir_index) {
        // 選択されたサブディレクトリNoを更新
        $data_array["default_dir_index"] = $current_default_dir_index;
        // 配列をJSON形式にエンコード
        $new_json = json_encode($data_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($new_json === false) {
            return false;
        }
        // JSONファイルに書き込む
        return file_put_contents($configFilepath, $new_json) !== false;
    } else {
        // 選択されたサブディレクトリNoが変更されていない場合は何もしない
        return true;
    }
}

/**
 * JSON設定ファイルを新規作成する
 * 
 * @return bool 成功時 true、失敗時 false
 */
function createNewJsonConfig(): bool
{
    global $configFilepath;

    // JSONファイルが存在する場合は終了する
    if (is_file($configFilepath)) {
        return false;
    }

    // 設定値を配列にまとめる
    $config = [
        'base_url'             => 'https://www.example.com',
        'base_dir'             => '/var/www/html',
        'array_subdir'         => ['/upload/dir_1', '/upload/dir_2'],
        'filename_ascii_only'  => false,
        'max_upload_size'      => 10485760,
        'mimetype_fileext_limit' => true,
        'flag_overwrite'       => false,
        'flag_no_alt_filename' => true,
        'flag_target_blank'    => false,
        'flag_fancybox'        => true,
        'flag_preview_abs'     => false,
        'default_image_size'   => 320,
        'default_dir_index'    => 0,
    ];

    // JSONエンコード（整形付き）
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // 書き込み処理
    if (file_put_contents($configFilepath, $json, LOCK_EX) === false) {
        return false;
    }

    return true;
}

/**
 * Web実行の場合の一連の処理を行う
 */
function main_web(): void
{
    global $configFilepath;

    // スクリプトをリロードするための、このスクリプトの名前
    $scriptname = basename($_SERVER['PHP_SELF']);

    $array_result = readJsonConfig($configFilepath);
    if (!$array_result[0]) {
        // JSON設定ファイルの読み込みに失敗
        echo "<html lang=\"ja\"><head><meta charset=\"utf-8\" /></head><body><p class=\"error\">{$array_result[1]}</p></body></html>";
        return;
    }

    // HTTP GET/POST引数により、処理分岐を行う
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ((isset($_GET['mode']) ? $_GET['mode'] : '') === 'listdir') {
            // ディレクトリ内ファイル一覧の表示
            printHtmlHome('listdir');
        } elseif ((isset($_GET['mode']) ? $_GET['mode'] : '') === 'fileinfo') {
            // ファイル情報表示の処理(情報表示画面のform post buttonを押して呼び出された場合)
            printHtmlHome('fileinfo');
        } elseif ((isset($_GET['mode']) ? $_GET['mode'] : '') === 'upload') {
            // ファイルアップロードの処理
            printHtmlHome('uploadfile');
        } elseif (isset($_POST['user']) && isset($_POST['password'])) {
            // 認証ログオンデータがPOSTされている場合
            // メインメニューを表示することで、func_check_auth関数を使い認証を行う
            printHtmlHome('mainmenu');
        } else {
            echo "<html lang=\"ja\"><head><meta charset=\"utf-8\" /></head><body><p class=\"error\">POST/GETで受信したmodeパラメータ未定義エラー</p></body></html>";
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['mode'])) {
            $mode = $_GET['mode'];
            if ($mode === 'listmenu') {
                // ディレクトリ一覧表示の処理
                printHtmlHome('listmenu');
            } elseif ($mode === 'fileinfo') {
                // ファイル情報表示の処理
                printHtmlHome('fileinfo');
            } elseif ($mode === 'viewlog') {
                // アップロードログ表示の処理
                printHtmlHome('viewlog');
            } elseif ($mode === 'viewauthlog') {
                // 共通認証ログ表示の処理
                printHtmlHome('viewauthlog');
            } elseif ($mode === 'logoff') {
                // ログオフの処理
                func_logoff_auth($scriptname, True, True);
            } else {
                echo "<html lang=\"ja\"><head><meta charset=\"utf-8\" /></head><body><p class=\"error\">GETで受信したmodeパラメータ未定義エラー</p></body></html>";
            }
        } else {
            // メインメニュー表示
            printHtmlHome('mainmenu');
        }
    } else {
        echo "<html lang=\"ja\"><head><meta charset=\"utf-8\" /></head><body><p class=\"error\">REQUEST_METHODがPOST/GET以外です(想定外)</p></body></html>";
    }
}


/**
 * CLI(コマンドライン)実行の場合の一連の処理を行う
 */
function main_cli(): void
{
    global $configFilepath;

    // ***********
    // JSON設定ファイルから読み込む変数
    global $base_url;
    global $base_dir;
    global $array_subdir;
    global $filename_ascii_only;
    global $max_upload_size;
    global $mimetype_fileext_limit;
    global $flag_overwrite;
    global $flag_no_alt_filename;
    global $flag_target_blank;
    global $flag_fancybox;
    global $flag_preview_abs;
    global $default_image_size;
    global $default_dir_index;
    // ***********

    if (!file_exists($configFilepath)) {
        // JSON設定ファイルが存在しない場合は新規作成
        if (!createNewJsonConfig()) {
            echo "JSON設定ファイルの新規作成に失敗しました。\n";
            return;
        }
        echo "JSON設定ファイル{$configFilepath}が新規作成されました。 サンプルデータが初期値として設定されていますので、環境に合わせて編集してください\n";
    }

    // JSON設定ファイルの読み込み
    $array_result = readJsonConfig($configFilepath);

    echo "JSON設定ファイル{$configFilepath}の読み込みを完了しました。 データの整合性をチェックしています...\n";

    if (!$array_result[0]) {
        // JSON設定ファイルの読み込みに失敗
        echo "{$array_result[1]}\n";
        return;
    }

    echo "JSON config data\n" .
        "  base_url: {$base_url}\n" .
        "  base_dir: {$base_dir}\n" .
        "  array_subdir: " . implode(", ", $array_subdir) . "\n" .
        "  filename_ascii_only: " . ($filename_ascii_only ? 'true' : 'false') . "\n" .
        "  max_upload_size: " . number_format($max_upload_size) . " bytes\n" .
        "  mimetype_fileext_limit: " . ($mimetype_fileext_limit ? 'true' : 'false') . "\n" .
        "  flag_overwrite: " . ($flag_overwrite ? 'true' : 'false') . "\n" .
        "  flag_no_alt_filename: " . ($flag_no_alt_filename ? 'true' : 'false') . "\n" .
        "  flag_target_blank: " . ($flag_target_blank ? 'true' : 'false') . "\n" .
        "  flag_fancybox: " . ($flag_fancybox ? 'true' : 'false') . "\n" .
        "  flag_preview_abs: " . ($flag_preview_abs ? 'true' : 'false') . "\n" .
        "  default_image_size: {$default_image_size}\n" .
        "  default_dir_index no: {$default_dir_index}\n";

    return;
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
