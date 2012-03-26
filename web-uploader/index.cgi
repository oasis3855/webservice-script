#!/usr/bin/perl

# save this file in << UTF-8  >> encode !
# ******************************************************
# Software name : Web-Uploader （ファイルアップローダ）
#
# Copyright (C) INOUE Hirokazu, All Rights Reserved
#     http://oasis.halfmoon.jp/
#
# csv2html-thumb.pl
# version 0.1 (2011/May/05)
#
# GNU GPL Free Software
#
# このプログラムはフリーソフトウェアです。あなたはこれを、フリーソフトウェア財
# 団によって発行された GNU 一般公衆利用許諾契約書(バージョン2か、希望によっては
# それ以降のバージョンのうちどれか)の定める条件の下で再頒布または改変することが
# できます。
# 
# このプログラムは有用であることを願って頒布されますが、*全くの無保証* です。
# 商業可能性の保証や特定の目的への適合性は、言外に示されたものも含め全く存在し
# ません。詳しくはGNU 一般公衆利用許諾契約書をご覧ください。
# 
# あなたはこのプログラムと共に、GNU 一般公衆利用許諾契約書の複製物を一部受け取
# ったはずです。もし受け取っていなければ、フリーソフトウェア財団まで請求してく
# ださい(宛先は the Free Software Foundation, Inc., 59 Temple Place, Suite 330
# , Boston, MA 02111-1307 USA)。
#
# http://www.opensource.jp/gpl/gpl.ja.html
# ******************************************************

use strict;
use warnings;
use utf8;

# ユーザディレクトリ下のCPANモジュールを読み込む
use lib ((getpwuid($<))[7]).'/local/cpan/lib/perl5';    # ユーザ環境にCPANライブラリを格納している場合
use lib ((getpwuid($<))[7]).'/local/lib/perl5';         # ユーザ環境にCPANライブラリを格納している場合
use lib ((getpwuid($<))[7]).'/local/lib/perl5/site_perl/5.8.9/mach';         # ユーザ環境にCPANライブラリを格納している場合

use CGI;
use File::Basename;
use File::Copy;
use Image::Size;
use Encode::Guess qw/euc-jp shiftjis iso-2022-jp/;	# 必要ないエンコードは削除すること
use HTML::Entities;
use Stat::lsMode qw/format_mode/;	# ファイル属性を -rwxr-xr-x のように整形する

# 認証システムは、このパッケージに含まれていません。別途、ユーザ環境のものを呼び出してください
require ((getpwuid($<))[7]).'/auth/auth.pl';    # 認証システム

my $flag_os = 'linux';	# linux/windows
my $flag_charcode = 'utf8';		# utf8/shiftjis
# IOの文字コードを規定
if($flag_charcode eq 'utf8'){
#	binmode(STDIN, ":utf8");	# コンソール入力があるコマンドライン版の時
	binmode(STDOUT, ":utf8");
	binmode(STDERR, ":utf8");
}
if($flag_charcode eq 'shiftjis'){
#	binmode(STDIN, "encoding(sjis)");	# コンソール入力があるコマンドライン版の時
	binmode(STDOUT, "encoding(sjis)");
	binmode(STDERR, "encoding(sjis)");
}

my $str_fpath_config = './config/init.pl';	# 設定ファイル
my $str_fpath_log = './log/log.txt';		# ログファイル

my $str_this_script = basename($0);		# このスクリプト自身のファイル名

main();
exit;

sub main{
	my $q = new CGI;

	# 必要なディレクトリ、ファイルが存在するかチェックする。
	sub_check_files(\$q);

	# 初期設定ファイルの読み込み
	require $str_fpath_config;

	# 認証システムは、このパッケージに含まれていません。別途、ユーザ環境のものを呼び出してください
	# 認証状態のチェック（認証されていない場合は、認証画面を表示する）
	sub_check_auth($str_this_script, 'web-uploader', 0);
	# ログオフの場合
	if(defined($q->url_param('mode')) && $q->url_param('mode') eq 'logoff'){
					sub_logoff_auth($str_this_script, 0);   # ログオフしてスクリプト終了
	}
	#### 認証システム利用ここまで

	# HTML出力を開始する
	sub_print_start_html(\$q);


	# 処理内容に合わせた処理と、画面表示
	if(defined($q->url_param('mode'))){
		if($q->url_param('mode') eq 'upload' && defined($q->param('uploadfile')) && length($q->param('uploadfile'))>0){
			sub_upload(\$q);
		}
		elsif($q->url_param('mode') eq 'fileinfo'){
			sub_fileinfo(\$q);
		}
		elsif($q->url_param('mode') eq 'viewlog'){
			sub_view_log(\$q);
		}
		elsif($q->url_param('mode') eq 'listmenu'){
			sub_list(\$q);
		}
		elsif($q->url_param('mode') eq 'listdir'){
			sub_list(\$q, $q->param('dir'));
		}
		else{
			print("<p class=\"error\">プログラムが意図しない方法で起動されました</p>\n");
		}
	}
	else{
		sub_disp_home();
	}

	# HTML出力を閉じる（フッタ部分の表示）
	sub_print_close_html();

}


# htmlを開始する（HTML構文を開始して、ヘッダを表示する）
sub sub_print_start_html{

	my $q_ref = shift;	# CGIオブジェクト

	print($$q_ref->header(-type=>'text/html', -charset=>'utf-8'));
	print($$q_ref->start_html(-title=>"Web Uploader (file uploader for blog)",
			-dtd=>['-//W3C//DTD XHTML 1.0 Transitional//EN','http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'],
			-lang=>'ja-JP',
			-style=>{'src'=>'style.css'}));

# ヘッダの表示
print << '_EOT';
<div style="height:100px; width:100%; padding:0px; margin:0px;"> 
<p><span style="margin:0px 20px; font-size:30px; font-weight:lighter;">Web Uploader</span><span style="margin:0px 0px; font-size:25px; font-weight:lighter; color:lightgray;">file uploader for blog</span></p> 
</div> 
_EOT

	# 左ペイン（メニュー）の表示
	print("<div id=\"main_content_left\">\n". 
		"<h2>System</h2>\n");
	{
		my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
		printf("<p>%04d/%02d/%02d %02d:%02d:%02d</p>\n", $year+1900, $mon+1, $mday, $hour, $min, $sec); 
	}
	print("<h2>Menu</h2>\n".
		"<ul>\n".
		"<li><a href=\"".$str_this_script."\">Home (Upload)</a></li>\n".
		"<li><a href=\"".$str_this_script."?mode=listmenu\">List Directory</a></li>\n".
		"<li><a href=\"".$str_this_script."?mode=viewlog\">Show Upload Log</a></li>\n".
		"<li><a href=\"".$str_this_script."?mode=logoff\">Logoff</a></li>\n".
		"</ul>\n".
		"</div>	<!-- id=\"main_content_left\" -->\n");

	# 右ペイン（主要表示部分）の表示
	print("<div id=\"main_content_right\">\n");

}


# htmlを閉じる（フッタ部分を表示して、HTML構文を閉じる）
sub sub_print_close_html{

print << '_EOT_FOOTER';
<p>&nbsp;</p>
</div>	<!-- id="main_content_right" --> 
<p>&nbsp;</p> 
<div class="clear"></div> 
<div id="footer"> 
<p><a href="http://oasis.halfmoon.jp/">Web Uploader</a> version 0.1 &nbsp;&nbsp; GNU GPL free software</p> 
</div>	<!-- id="footer" --> 
_EOT_FOOTER

#	print $q->end_html;
	print("</body>\n</html>\n");
}

# エラー終了時に呼ばれるsub
# sub_error_exit('message');
# sub_error_exit('message', \$q);	# HTML構文を始める場合
sub sub_error_exit{
	my $str = shift;	# 出力する文字列
	my $q_ref = shift;	# CGIオブジェクトへのリファレンス：HTML構文を始める場合のみ

	# HTML構文を始める
	if(defined($q_ref)){
		sub_print_start_html($q_ref);
	}
	
	print("<p class=\"error\">".(defined($str)?$str:'error')."</p>\n");
	sub_print_close_html();
	exit;
}


# 各種ファイル、DBが読み書きできるか初期チェック（新規作成含む）
sub sub_check_files{
	my $q_ref = shift;

	# 初期設定ファイルの存在チェック
	unless( -f $str_fpath_config ){
		sub_error_exit("Error : 初期設定ファイル ".$str_fpath_config . " が見つかりません", $q_ref);
	}

	unless( -f $str_fpath_log ){
		open(FH, '>'.$str_fpath_log) or sub_error_exit("Error : ログファイル ".$str_fpath_log . " を新規作成できません", $q_ref);
		print(FH "Logfile create\n") or sub_error_exit("Error : ログファイル ".$str_fpath_log . " に書き込めません", $q_ref);
		close(FH);
	}

	unless( -w $str_fpath_log ){
		sub_error_exit("Error : ログファイル ".$str_fpath_log . " に書き込めません", $q_ref);
	}
}


# ログファイルに書き込む
sub sub_write_log {
	my $str = shift;

	my ($sec,$min,$hour,$day,$mon,$year) = localtime(time);

	# アクセス元のIPアドレス
	my $ip = $ENV{'REMOTE_ADDR'};
	if(!defined($ip) || length($ip)<=0){ $ip = '0.0.0.0'; }
   
	$ip = sub_conv_to_safe_str($ip, 255);

	# アクセス元のホスト名
	my $hostname = $ENV{'REMOTE_HOST'};
	if(!defined($hostname) || length($hostname)<=0){ $hostname = ''; }
	if ($hostname eq "" || $hostname eq $ip) {
			if($ip eq '127.0.0.1' || $ip eq ''){ $hostname = 'localhost'; }
			elsif($ip eq '0.0.0.0'){ $hostname = ''; }
			else{ $hostname = gethostbyaddr(pack("C4", split(/\./, $ip)), 2); }
	}
   
	$hostname = sub_conv_to_safe_str($hostname, 255);

	open(FH, '>>'.$str_fpath_log) or sub_error_exit("Error : ログファイル ".$str_fpath_log . " に書き込めません");
	binmode(FH, ':utf8');
	printf(FH "%04d/%02d/%02d,%02d:%02d:%02d,%s,%s,%s\n", $year+1900, $mon+1, $day, $hour, $min, $sec, $ip, $hostname, $str);
	close(FH);

}


# ログファイルを画面表示
sub sub_view_log {
	my $q_ref = shift;

	print("<h1>Show Last Upload Log (直近のアップロード・ログ)</h1>\n".
		"<pre class=\"scroll-x\">\n");
		system("tail -n 20 ".$str_fpath_log)."\n".
	print("</pre>\n");


}


# ホーム画面（アップロード対象ファイルの指定画面）
sub sub_disp_home{
	our @arr_updirs;
	our $flag_overwrite;
	our $n_target_size;

	print("<h1>Home Screen / Upload File (ファイルのアップロード)</h1>\n".
		"<p>画像やデータファイルをアップロード出来ます</p>\n".
		"<form method=\"post\" action=\"".$str_this_script."?mode=upload\" enctype=\"multipart/form-data\">\n".
		"<p>対象ファイルを指定します<br />\n".
		"&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"file\" name=\"uploadfile\" value=\"\" size=\"50\" /><br />\n".
		"&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" name=\"overwrite\" value=\"enable\" ".($flag_overwrite == 1 ? "checked=\"checked\"" : '')." />既存ファイルが存在しても上書きする</p>\n".
		"<p>アップロード先ディレクトリを選択します<br />\n");

	for(my $i=0; $i<=$#arr_updirs; $i++){
		print("&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"dir\" value=\"".$i."\" ".($i==0 ? "checked=\"checked\"" : '').
			" />".sub_conv_to_flagged_utf8($arr_updirs[$i],'utf8').($i==$#arr_updirs ? '</p>' : '<br />')."\n");
	}
	
	print("<div class=\"inbox\">\n".
		"<table border=\"0\">\n".
		"<tr><td colspan=\"2\">webでの表現方法の設定</td></tr>\n".
		#####
		"<tr><td>画像表示サイズ</td><td><input type=\"text\" name=\"size\" value=\"".$n_target_size."\" size=\"15\" /> (px) \n".
		"&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"size_base\" value=\"long\" checked=\"checked\" />長辺\n".
		"&nbsp;&nbsp;<input type=\"radio\" name=\"size_base\" value=\"v\" />縦\n".
		"&nbsp;&nbsp;<input type=\"radio\" name=\"size_base\" value=\"h\" />横\n".
		"&nbsp;&nbsp;<input type=\"radio\" name=\"size_base\" value=\"off\" />OFF（実寸）</td></tr>\n".
		"<tr><td>alt属性値</td><td><input type=\"text\" name=\"alt\" value=\"\" size=\"30\" />&nbsp;<input type=\"checkbox\" name=\"alt_fname\" value=\"enable\" checked=\"checked\" />空欄の時はファイル名を利用</td></tr>\n".
		"<tr><td>title属性値</td><td><input type=\"text\" name=\"title\" value=\"\" size=\"30\" /> (空欄の時は属性削除) </td></tr>\n".
		"<tr><td>その他</td><td><input type=\"checkbox\" name=\"target\" value=\"blank\" checked=\"checked\" />ファイル（画像）を新しいウインドウで開く&nbsp;&nbsp;<input type=\"checkbox\" name=\"fancybox\" value=\"on\" checked=\"checked\" />fancybox対応（&lt;a rel=... title=...&gt;）</td></tr>\n".
		#####
		"</table>\n".
		"</div>\n".
		"<p><input type=\"submit\" value=\"アップロード\" /></p>\n".
		"</form>\n");



}


# アップロード
sub sub_upload{
	my $q_ref = shift;

	# init.plで設定されたグローバル変数
	our $str_webaddr;
	our $str_basedir;
	our @arr_updirs;
	
	my $flag_is_image = 0;	# 画像の場合 1

	print("<h1>Upload File Information (ファイル情報)</h1>\n");

	# アップロードファイル名の取得
	my $str_filename = sub_conv_to_flagged_utf8(decode_entities($$q_ref->param('uploadfile'), 'utf8'));
	print("<!--Uploaded Filename = ".encode_entities($str_filename, '&<>\\\"\'')." -->\n");

	# 入力条件のチェック
	unless(defined($$q_ref->param('dir')) && length($$q_ref->param('dir'))>0 && 
			$$q_ref->param('dir')+0 >= 0 && $$q_ref->param('dir')+0 <= $#arr_updirs){
		sub_error_exit("Error : アップロード先ディレクトリの指定が対象範囲外");
	}
	if($str_filename =~ m#[\x00-\x1f]|/|<|>|&|\*|\"|\'#){
		sub_error_exit("Error : ファイル名に利用できない文字が含まれています");
	}

	# 保存先ファイルの上書きチェック
	my $str_save_filepath = $str_basedir . sub_conv_to_flagged_utf8($arr_updirs[$$q_ref->param('dir')+0],'utf8') . '/' . $str_filename;
	if( -e $str_save_filepath){
		if(!( -f $str_save_filepath )){
			sub_error_exit("Error : 指定されたファイル名はディレクトリなどの名前で使われています");
		}
		elsif(!( -w $str_save_filepath )){
			sub_error_exit("Error : 指定されたファイル名は存在し、書き込み禁止です");
		}
		unless(defined($$q_ref->param('overwrite')) && length($$q_ref->param('overwrite'))>0 &&
			$$q_ref->param('overwrite') eq 'enable'){
			sub_error_exit("Error : 指定されたファイル名は存在します。<br />上書きする場合は、アップロード時に「上書きする」をONにしてください。");
		}
		if( -e $str_save_filepath ){
			print "<p class=\"info\">ファイル ".$str_filename." に上書きします</p>\n";
		}
	}

	# アップロードファイルの取り込み（一時ファイルに言ったん保存し、それを最終ディレクトリに移動する）
	my $fh = $$q_ref->upload('uploadfile');
	my $str_temp_filepath = $$q_ref->tmpFileName($fh);
	print("<!-- Temporary Fileneme = ".$str_temp_filepath." -->\n");
	File::Copy::move($str_temp_filepath, $str_save_filepath) or sub_error_exit("Error : ".$str_save_filepath."への移動処理失敗");
	close($fh);

	unless( -f $str_save_filepath ){ sub_error_exit("Error : 保存後の ".$str_save_filepath." の存在が検知できない"); }

	print "<p class=\"ok\">ファイル ".$str_filename." のアップロードが完了しました</p>\n";

	# ログファイルに記録する
	sub_write_log(sub_conv_to_flagged_utf8($arr_updirs[$$q_ref->param('dir')+0],'utf8') . '/' . $str_filename);
	# ファイル情報を画面表示する
	sub_disp_file_code($q_ref, $$q_ref->param('dir')+0, $str_filename);

}


# 指定されたファイルのHTMLコード例を画面表示する
sub sub_disp_file_code {
	my $q_ref = shift;
	my $updirs_index = shift;	# 利用する @arr_updirs のインデックス
	my $filename = shift;		# ファイル名

	# init.plで設定されたグローバル変数
	our $n_target_size;
	our $str_webaddr;
	our $str_basedir;
	our @arr_updirs;

	my $filepath = $str_basedir . sub_conv_to_flagged_utf8($arr_updirs[$updirs_index],'utf8') . '/' . $filename;
	my $flag_is_image = 0;	# 画像かどうかの判定結果 0:画像でない。1:画像

	# ファイルが読み込み可能かチェック
	unless( -f $filepath && -r $filepath){
		sub_error_exit("Error : 指定されたファイル ".$filename." が読み込めません");
	}

	# ファイルが画像かそれ以外かを判定。画像の場合は @arr_imgsize にサイズを得る
	my @arr_imgsize = undef;
	if($filename =~ m/.gif$|.jpg$|.jpeg$|.png$/i){
		@arr_imgsize = Image::Size::imgsize($filepath);
	}
	if(!defined($arr_imgsize[0]) || !defined($arr_imgsize[1]) || $arr_imgsize[0] == 0 || $arr_imgsize[1] == 0){
		# 画像でない
		$flag_is_image = 0;
	}
	else{
		# 画像
		$flag_is_image = 1;
	}

	if($flag_is_image == 1){
		my $size_base;
		my $size;
		# 画像サイズ変更のために必要な入力項目をチェックする
		unless(defined($$q_ref->param('size')) && length($$q_ref->param('size'))>0 &&
				defined($$q_ref->param('size_base')) && length($$q_ref->param('size_base'))>0 &&
				$$q_ref->param('size')>1 && $$q_ref->param('size')<9999 && 
				($$q_ref->param('size_base') eq 'long' || $$q_ref->param('size_base') eq 'v' ||
				$$q_ref->param('size_base') eq 'h' || $$q_ref->param('size_base') eq 'off')){
			#表示サイズが正しく入力されていない場合は、デフォルトの値を使う
			$size_base = 'long';
			$size = $n_target_size;
		}
		else{
			$size_base = $$q_ref->param('size_base');
			$size = int($$q_ref->param('size'));
		}
		my $x0 = $arr_imgsize[0];	# 元画像 横サイズ
		my $y0 = $arr_imgsize[1];	# 元画像 縦サイズ
		my $x;	# サイズ変更後の 画像横サイズ
		my $y;	# サイズ変更後の 画像縦サイズ
		if($size_base eq 'long'){
			if($x0 > $y0){ $x = $size; $y = int($x*$y0/$x0); }
			else{ $y = $size; $x = int($y*$x0/$y0); }
		}
		elsif($size_base eq 'v'){
			$y = $size; $x = int($y*$x0/$y0);
		}
		elsif($size_base eq 'h'){
			$x = $size; $y = int($x*$y0/$x0);
		}
		else{
			$x = $x0; $y = $y0;
		}

		# alt属性の設定
		my $alt;
		if(defined($$q_ref->param('alt')) && length($$q_ref->param('alt'))>0){
			$alt = encode_entities(sub_conv_to_flagged_utf8(decode_entities($$q_ref->param('alt')), 'utf8'), '&<>\\\"\'');
		}
		elsif(defined($$q_ref->param('alt_fname')) && length($$q_ref->param('alt_fname'))>0 && $$q_ref->param('alt_fname') eq 'enable'){
			$alt = encode_entities($filename, '&<>\\\"\'');
		}
		else{
			$alt = ' ';
		}

		# title属性の設定
		my $title;
		if(defined($$q_ref->param('title')) && length($$q_ref->param('title'))>0){
			$title = encode_entities(sub_conv_to_flagged_utf8(decode_entities($$q_ref->param('title')), 'utf8'), '&<>\\\"\'');
		}
		
		# target=_blankの設定
		my $flag_target = 0;
		if(defined($$q_ref->param('target')) && length($$q_ref->param('target'))>0 && $$q_ref->param('target') eq 'blank'){
			$flag_target = 1;
		}
		
		# fancybox対応（<a>タグにrel="lightbox_group"とtitle="..."を追加）
		my $flag_fancybox = 0;
		if(defined($$q_ref->param('fancybox')) && length($$q_ref->param('fancybox'))>0 && $$q_ref->param('fancybox') eq 'on'){
			$flag_fancybox = 1;
		}
		

	print("<form method=\"post\" action=\"".$str_this_script."?mode=fileinfo\" class=\"inbox\">\n".
		"<table border=\"0\">\n".
		"<tr><td colspan=\"2\">webでの表現方法の設定</td></tr>\n".
		"<tr><td>元画像の情報</td><td>横(x)=".$x0.", 縦(y)=".$y0.", サイズ=".( -s $filepath)." bytes</td></tr>\n".
		"<input type=\"hidden\" name=\"dir\" value=\"".$updirs_index."\" size=\"30\" />\n".
		"<input type=\"hidden\" name=\"filename\" value=\"".$filename."\" size=\"30\" />\n".
		#####
		"<tr><td>画像表示サイズ</td><td><input type=\"text\" name=\"size\" value=\"".$n_target_size."\" size=\"15\" /> (px) \n".
		"&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"size_base\" value=\"long\" checked=\"checked\" />長辺\n".
		"&nbsp;&nbsp;<input type=\"radio\" name=\"size_base\" value=\"v\" />縦\n".
		"&nbsp;&nbsp;<input type=\"radio\" name=\"size_base\" value=\"h\" />横\n".
		"&nbsp;&nbsp;<input type=\"radio\" name=\"size_base\" value=\"off\" />OFF（実寸）</td></tr>\n".
		"<tr><td>alt属性値</td><td><input type=\"text\" name=\"alt\" value=\"".$alt."\" size=\"30\" />&nbsp;<input type=\"checkbox\" name=\"alt_fname\" value=\"enable\" checked=\"checked\" />空欄の時はファイル名を利用</td></tr>\n".
		"<tr><td>title属性値</td><td><input type=\"text\" name=\"title\" value=\"".(defined($title) ? $title : '')."\" size=\"30\" /> (空欄の時は属性削除) </td></tr>\n".
		"<tr><td>その他</td><td><input type=\"checkbox\" name=\"target\" value=\"blank\" checked=\"checked\" />ファイル（画像）を新しいウインドウで開く&nbsp;&nbsp;<input type=\"checkbox\" name=\"fancybox\" value=\"on\" checked=\"checked\" />fancybox対応（&lt;a rel=... title=...&gt;）</td></tr>\n".
		#####
		"</table>\n".
		"<input type=\"submit\" value=\"表示条件を変更して再表示する\" />\n".
		"</form>\n");

		
		# 貼り付け用コード例を画面表示する
		print("<pre class=\"fold\">\n".
				"&lt;a href=\"".encode_entities(sub_conv_to_flagged_utf8($arr_updirs[$updirs_index],'utf8')."/".$filename)."\"".
				($flag_fancybox == 1 ? ' rel="lightbox_group"' : '').
				($flag_fancybox == 1 && defined($title) ? ' title="'.$title.'"' : '').
				($flag_target == 1 ? ' target="_blank"' : '')."&gt;".
				"&lt;img src=\"".encode_entities(sub_conv_to_flagged_utf8($arr_updirs[$updirs_index],'utf8')."/".$filename)."\" width=\"".$x."\" height=\"".$y."\" ".
				"alt=\"".$alt."\" ".(defined($title) ? 'title="'.$title.'"' : '')." /&gt;".
				"&lt;/a&gt;\n".
				"</pre>\n");
		# 貼り付け用コード例を画面表示する
		print("<pre class=\"fold\">\n".
				"&lt;a href=\"".encode_entities($str_webaddr.sub_conv_to_flagged_utf8($arr_updirs[$updirs_index],'utf8')."/".$filename)."\"".
				($flag_fancybox == 1 ? ' rel="lightbox_group"' : '').
				($flag_fancybox == 1 && defined($title) ? ' title="'.$title.'"' : '').
				($flag_target == 1 ? ' target="_blank"' : '')."&gt;".
				"&lt;img src=\"".encode_entities($str_webaddr.sub_conv_to_flagged_utf8($arr_updirs[$updirs_index],'utf8')."/".$filename)."\" width=\"".$x."\" height=\"".$y."\" ".
				"alt=\"".$alt."\" ".(defined($title) ? 'title="'.$title.'"' : '')." /&gt;".
				"&lt;/a&gt;\n".
				"</pre>\n");
		
		# 貼り付け用コードの描画例を表示する
		print("<p>Web上での表示例</p>\n".
				"<p><a href=\"".encode_entities($str_webaddr.sub_conv_to_flagged_utf8($arr_updirs[$updirs_index],'utf8')."/".$filename)."\"".
				($flag_target == 1 ? ' target="_blank"' : '').">".
				"<img src=\"".encode_entities($str_webaddr.sub_conv_to_flagged_utf8($arr_updirs[$updirs_index],'utf8')."/".$filename)."\" width=\"".$x."\" height=\"".$y."\" ".
				"alt=\"".encode_entities($alt, '&<>\\\"\'')."\" ".(defined($title) ? 'title="'.$title.'"' : '')." />".
				"</a></p>\n");
		
	}
	else{
		# 貼り付け用コード例を画面表示する
		print("<pre class=\"fold\">\n".
			"&lt;a href=\"".encode_entities(sub_conv_to_flagged_utf8($arr_updirs[$updirs_index],'utf8')."/".$filename)."\"&gt;".
			$filename."をダウンロードする&lt;/a&gt;\n".
			"</pre>\n");

		# 貼り付け用コード例を画面表示する
		print("<pre class=\"fold\">\n".
			"&lt;a href=\"".encode_entities($str_webaddr.sub_conv_to_flagged_utf8($arr_updirs[$updirs_index],'utf8')."/".$filename)."\"&gt;".
			$filename."をダウンロードする&lt;/a&gt;\n".
			"</pre>\n");
		
		# 貼り付け用コードの描画例を表示する
		print("<p>Web上での表示例</p>\n".
			"<p><a href=\"".encode_entities($str_webaddr.sub_conv_to_flagged_utf8($arr_updirs[$updirs_index],'utf8')."/".$filename)."\">".
			$filename."をダウンロードする</a></p>\n");
	}


}


# 指定されたファイル情報を表示する
sub sub_fileinfo {
	my $q_ref = shift;

	# init.plで設定されたグローバル変数
	our $str_webaddr;
	our $str_basedir;
	our @arr_updirs;
	
	my $filename;

	print("<h1>File Information (ファイル情報)</h1>\n");

	# 入力条件のチェック
	unless(defined($$q_ref->param('dir')) && length($$q_ref->param('dir'))>0 && 
			$$q_ref->param('dir')+0 >= 0 && $$q_ref->param('dir')+0 <= $#arr_updirs){
		sub_error_exit("Error : アップロード先ディレクトリの指定が対象範囲外");
	}
	unless(defined($$q_ref->param('filename')) && length($$q_ref->param('filename'))>0){
		sub_error_exit("Error : ファイル名が指定されていない");
	}
	$filename = sub_conv_to_flagged_utf8(decode_entities($$q_ref->param('filename'), 'utf8'));
	if($filename =~ m#[\x00-\x1f]|/|<|>|&|\*|\"|\'#){
		sub_error_exit("Error : ファイル名に利用できない文字が含まれています");
	}

	sub_disp_file_code($q_ref, $$q_ref->param('dir')+0, $filename);


}


# アップロード先ディレクトリ内のファイル一覧を表示
sub sub_list {
	my $q_ref = shift;
	my $updirs_index = shift;	# 利用する @arr_updirs のインデックス

	# init.plで設定されたグローバル変数
	our $str_webaddr;
	our $str_basedir;
	our @arr_updirs;
	
	print("<h1>File List (ファイル一覧)</h1>\n");

	if(defined($updirs_index) && $updirs_index+0 >= 0 && $updirs_index+0 <= $#arr_updirs){
		my $search_mask = "*";
		if(defined($$q_ref->param('mask')) && length($$q_ref->param('mask'))>0 &&
			!($$q_ref->param('mask') =~ m#/|<|>|&|!|\"|\'|%#)){
			$search_mask = $$q_ref->param('mask');
		}
		my @arr_filelist = glob($str_basedir.sub_conv_to_flagged_utf8($arr_updirs[$updirs_index+0],'utf8').'/'.$search_mask);
		if(defined($$q_ref->param('sort'))){
			if($$q_ref->param('sort') eq 'fname_asc'){ @arr_filelist = sort @arr_filelist; }
			if($$q_ref->param('sort') eq 'fname_desc'){ @arr_filelist = sort { $b cmp $a } @arr_filelist; }
		}
		print("<pre>\n      size time                filename\n");
		for(my $i=0,my $filename; $i<=$#arr_filelist; $i++){
			if($i>=100){
				print("ファイル数が100個を超えたので、以降省略します<br />\n");
				last;
			}
			$filename = encode_entities(sub_conv_to_flagged_utf8($arr_filelist[$i], 'utf8'), '&<>\\\"\'');
			my @filestat = stat($filename);
			my ($sec,$min,$hour,$day,$mon,$year) = localtime($filestat[9]);
			my $str_attr = Stat::lsMode::format_mode($filestat[2]);
			my $str_timestamp = sprintf("%s %10d %04d-%02d-%02d %02d:%02d:%02d", $str_attr, $filestat[7], $year+1900, $mon+1, $day, $hour, $min, $sec);
			print($str_timestamp." <a href=\"$str_this_script?mode=fileinfo&amp;dir=".($updirs_index+0)."&filename=".basename($filename)."\">".basename($filename)."</a>\n");
		}
		print("</pre>\n");
	}
	else{
		print("<form method=\"post\" action=\"".$str_this_script."?mode=listdir\">\n".
			"<p>一覧を表示するディレクトリを選択します<br />\n");
		for(my $i=0; $i<=$#arr_updirs; $i++){
			print("&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"dir\" value=\"".$i."\" ".($i==0 ? "checked=\"checked\"" : '').
				" />".sub_conv_to_flagged_utf8($arr_updirs[$i],'utf8').($i==$#arr_updirs ? '</p>' : '<br />')."\n");
		}
		print("<p>検索条件を設定します<br />\n".
			"&nbsp;&nbsp;&nbsp;&nbsp;ファイル検索マスク : <input type=\"text\" name=\"mask\" value=\"*\" size=\"30\" /><br />\n".
			"&nbsp;&nbsp;&nbsp;&nbsp;ソート順 : ".
			"&nbsp;&nbsp;<input type=\"radio\" name=\"sort\" value=\"fname_asc\" checked=\"checked\" />ファイル名A...Z".
			"&nbsp;&nbsp;<input type=\"radio\" name=\"sort\" value=\"fname_desc\" />ファイル名Z...A".
			"&nbsp;&nbsp;<input type=\"radio\" name=\"sort\" value=\"off\" />無し</p>".
			"<input type=\"submit\" value=\"指定したディレクトリの一覧を表示する\" />\n".
			"</form>\n");
	}

}


# 以下、他のスクリプトと同一の共通関数

# 任意の文字コードの文字列を、UTF-8フラグ付きのUTF-8に変換する
sub sub_conv_to_flagged_utf8{
	my $str = shift;
	my $enc_force = undef;
	if(@_ >= 1){ $enc_force = shift; }		# デコーダの強制指定
	
	# デコーダが強制的に指定された場合
	if(defined($enc_force)){
		if(ref($enc_force)){
			$str = $enc_force->decode($str);
			return($str);
		}
		elsif($enc_force ne '')
		{
			$str = Encode::decode($enc_force, $str);
		}
	}

	my $enc = Encode::Guess->guess($str);	# 文字列のエンコードの判定

	unless(ref($enc)){
		# エンコード形式が2個以上帰ってきた場合 （shiftjis or utf8）
		my @arr_encodes = split(/ /, $enc);
		if(grep(/^$flag_charcode/, @arr_encodes) >= 1){
			# $flag_charcode と同じエンコードが検出されたら、それを優先する
			$str = Encode::decode($flag_charcode, $str);
		}
		elsif(lc($arr_encodes[0]) eq 'shiftjis' || lc($arr_encodes[0]) eq 'euc-jp' || 
			lc($arr_encodes[0]) eq 'utf8' || lc($arr_encodes[0]) eq 'us-ascii'){
			# 最初の候補でデコードする
			$str = Encode::decode($arr_encodes[0], $str);
		}
	}
	else{
		# UTF-8でUTF-8フラグが立っている時以外は、変換を行う
		unless(ref($enc) eq 'Encode::utf8' && utf8::is_utf8($str) == 1){
			$str = $enc->decode($str);
		}
	}

	return($str);
}


# 任意の文字コードの文字列を、UTF-8フラグ無しのUTF-8に変換する
sub sub_conv_to_unflagged_utf8{
	my $str = shift;

	# いったん、フラグ付きのUTF-8に変換
	$str = sub_conv_to_flagged_utf8($str);

	return(Encode::encode('utf8', $str));
}


# UTF8から現在のOSの文字コードに変換する
sub sub_conv_to_local_charset{
	my $str = shift;

	# UTF8から、指定された（OSの）文字コードに変換する
	$str = Encode::encode($flag_charcode, $str);
	
	return($str);
}


# 引数で与えられたファイルの エンコードオブジェクト Encode::encode を返す
sub sub_get_encode_of_file{
	my $fname = shift;		# 解析するファイル名

	# ファイルを一気に読み込む
	open(FH, "<".sub_conv_to_local_charset($fname));
	my @arr = <FH>;
	close(FH);
	my $str = join('', @arr);		# 配列を結合して、一つの文字列に

	my $enc = Encode::Guess->guess($str);	# 文字列のエンコードの判定

	# エンコード形式の表示（デバッグ用）
	print("Automatick encode ");
	if(ref($enc) eq 'Encode::utf8'){ print("detect : utf8\n"); }
	elsif(ref($enc) eq 'Encode::Unicode'){
		print("detect : ".$$enc{'Name'}."\n");
	}
	elsif(ref($enc) eq 'Encode::XS'){
		print("detect : ".$enc->mime_name()."\n");
	}
	elsif(ref($enc) eq 'Encode::JP::JIS7'){
		print("detect : ".$$enc{'Name'}."\n");
	}
	else{
		# 二つ以上のエンコードが推定される場合は、$encに文字列が返る
		print("unknown (".$enc.")\n");
	}

	# エンコード形式が2個以上帰ってきた場合 （例：shiftjis or utf8）でテクと失敗と扱う
	unless(ref($enc)){
		$enc = '';
	}

	# ファイルがHTMLの場合 Content-Type から判定する
	if(lc($fname) =~ m/html$/ || lc($fname) =~ m/htm$/){
		my $parser = HTML::HeadParser->new();
		unless($parser->parse($str)){
			my $content_enc = $parser->header('content-type');
			if(defined($content_enc) && $content_enc ne '' && lc($content_enc) =~ m/text\/html/ ){
				if(lc($content_enc) =~ m/utf-8/){ $enc = 'utf8'; }
				elsif(lc($content_enc) =~ m/shift_jis/){ $enc = 'shiftjis'; }
				elsif(lc($content_enc) =~ m/euc-jp/){ $enc = 'euc-jp'; }
				
				print("HTML Content-Type detect : ". $content_enc ." (is overrided)\n");
				$enc = $content_enc;
			}
		}
	}

	return($enc);
}

# *************
# SQL格納に有害になる文字を除去し、文字コードをSJISに変更する
# $str_clean = sub_conv_to_safe_str($str, $n_max_length);
sub sub_conv_to_safe_str
{
        # 引数
        my $str = shift;
        my $n_max_length = shift;
        # 引数チェック
        if(!defined($str) || length($str)<=0){ $str = ''; }
        if(!defined($n_max_length) || $n_max_length<=0){ $n_max_length = 255; }

        chomp($str);    # 末尾改行を削除
        if($str eq ''){ return ''; }

        # 文字エンコードをshiftjisに変換
        $str = sub_conv_to_flagged_utf8($str);  # 任意encode → utf8
        $str = Encode::encode('shiftjis', $str);        # utf-8 → sjis

        # バイナリや特殊記号を削除（SQLインジェクション防止）
        # （なお、0x5cのバックスラッシュはSJISの文字コードを侵食するため削除しない
        $str =~ tr/(\x00-\x1f|\x21-\x27)//;
        $str =~ s/(\x2c|\x3b|<|>|\x3f|\x60)//g;

        # 文字列を最大長さで切り捨てる
        $str = substr($str, 0, $n_max_length);
       
        return $str;
}

