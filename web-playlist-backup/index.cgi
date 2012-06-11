#!/usr/bin/perl

# save this file in << UTF-8  >> encode !
# ******************************************************
# Software name : Web-Playlist backup （PLSバックアップ）
#
# Copyright (C) INOUE Hirokazu, All Rights Reserved
#     http://oasis.halfmoon.jp/
#
# csv2html-thumb.pl
# version 0.1 (2011/April/06)
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
use DBI;
use Text::CSV_XS;
use Data::Dumper;
use Encode::Guess qw/euc-jp shiftjis iso-2022-jp/;	# 必要ないエンコードは削除すること
use HTML::Entities;
use DBD::SQLite;	# SQLiteバージョンを出すためのみに使用


my $flag_os = 'linux';	# linux/windows
my $flag_charcode = 'utf8';		# utf8/shiftjis
# IOの文字コードを規定
if($flag_charcode eq 'utf8'){
	binmode(STDIN, ":utf8");
	binmode(STDOUT, ":utf8");
	binmode(STDERR, ":utf8");
}
if($flag_charcode eq 'shiftjis'){
	binmode(STDIN, "encoding(sjis)");
	binmode(STDOUT, "encoding(sjis)");
	binmode(STDERR, "encoding(sjis)");
}


my $str_dir_db = './data';		# DBや一時ファイルが存在するdir名
my $str_dir_backup = './backup';		# バックアップファイルを格納するdir名

my $str_filepath_csv_tmp = './data/temp.pls';	# アップロード時の一時ファイル名
my $str_dsn = "dbi:SQLite:dbname=./data/data.sqlite";	# SQLite DB

my $str_this_script = basename($0);		# このスクリプト自身のファイル名

my $q = new CGI;

# 必要なディレクトリ、ファイル、DBが存在するかチェックする。DBが無い場合は作成する
sub_check_files(\$q);

# ファイルダウンロード処理の場合
if(defined($q->url_param('mode'))){
	if($q->url_param('mode') eq 'download_pls'){
		sub_download_csv(\$q, 'pls', 'download');
		exit;
	}
	elsif($q->url_param('mode') eq 'download_pls_ft'){
		sub_download_csv(\$q, 'pls_ft', 'download');
		exit;
	}
	elsif($q->url_param('mode') eq 'download_csv'){
		sub_download_csv(\$q, 'csv', 'download');
		exit;
	}
}


# HTML出力を開始する
sub_print_start_html(\$q);


# 処理内容に合わせた処理と、画面表示
if(defined($q->url_param('mode'))){
	if($q->url_param('mode') eq 'list'){
		sub_list_db();
	}
	elsif($q->url_param('mode') eq 'edit'){
		sub_edit_db(\$q);
	}
	elsif($q->url_param('mode') eq 'add'){
		sub_edit_addnew_db(\$q);
	}
	elsif($q->url_param('mode') eq 'backup'){
		sub_backup_db();
	}
	elsif($q->url_param('mode') eq 'upload_pick'){
		sub_disp_upload_filepick();
	}
	elsif($q->url_param('mode') eq 'download'){
		sub_disp_download();
	}
	elsif($q->url_param('mode') eq 'restore_pick'){
		sub_disp_restore_select();
	}
	elsif($q->url_param('mode') eq 'restore'){
		sub_restore($q->url_param('file'));
	}
}
elsif(defined($q->param('uploadfile')) && length($q->param('uploadfile'))>0){
	if(defined($q->param('purge_data')) && $q->param('purge_data') eq 'purge'){
		sub_upload_csv(\$q, 1);		# テーブルをクリアしてからデータアップロード
	}
	else{
		sub_upload_csv(\$q, 0);		# 既存データに追加で、データアップロード
	}
}
else{
	sub_disp_home();
}

# HTML出力を閉じる（フッタ部分の表示）
sub_print_close_html();

exit;


# htmlを開始する（HTML構文を開始して、ヘッダを表示する）
sub sub_print_start_html{

	my $q_ref = shift;	# CGIオブジェクト

	print($$q_ref->header(-type=>'text/html', -charset=>'utf-8'));
	print($$q_ref->start_html(-title=>"Playlist(PLS) Backup Strage",
			-dtd=>['-//W3C//DTD XHTML 1.0 Transitional//EN','http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'],
			-lang=>'ja-JP',
			-style=>{'src'=>'style.css'}));

# ヘッダの表示
print << '_EOT';
<div style="height:100px; width:100%; padding:0px; margin:0px;"> 
<p><span style="margin:0px 20px; font-size:30px; font-weight:lighter;">Web-Playlist backup</span><span style="margin:0px 0px; font-size:25px; font-weight:lighter; color:lightgray;">PLS file backup system</span></p> 
</div> 
_EOT

	# 左ペイン（メニュー）の表示
	print("<div id=\"main_content_left\">\n". 
		"<h2>System</h2>\n");
	{
		my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
		printf("<p>%04d/%02d/%02d %02d:%02d:%02d</p>\n", $year+1900, $mon+1, $mday, $hour, $min, $sec); 
	}
	print("<p>DBD::SQLite ".$DBD::SQLite::sqlite_version."</p>\n");
	print("<h2>Menu</h2>\n".
		"<ul>\n".
		"<li><a href=\"".$str_this_script."\">Home</a></li>\n".
		"<li><a href=\"".$str_this_script."?mode=list\">List Database</a></li>\n".
		"<li><a href=\"".$str_this_script."?mode=add\">Add one entry</a></li>\n".
		"<li><a href=\"".$str_this_script."?mode=backup\">Backup Database</a></li>\n".
		"<li><a href=\"".$str_this_script."?mode=upload_pick\">Upload PLS</a></li>\n".
		"<li><a href=\"".$str_this_script."?mode=download\">Download PLS,CSV</a></li>\n".
		"<li><a href=\"".$str_this_script."?mode=restore_pick\">Restore Menu</a></li>\n".
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
<p><a href="http://oasis.halfmoon.jp/">Web-Playlist backup</a> version 0.1 &nbsp;&nbsp; GNU GPL free software</p> 
</div>	<!-- id="footer" --> 
_EOT_FOOTER

	print $q->end_html;
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
	
	# 必要なディレクトリが存在しなければ作成する
	unless( -d $str_dir_db ){
		mkdir($str_dir_db) or sub_error_exit("Error : unable to create ".$str_dir_db, $q_ref);
	}
	unless( -d $str_dir_backup ){
		mkdir($str_dir_backup) or sub_error_exit("Error : unable to create ".$str_dir_backup, $q_ref);
	}
	
	# ディレクトリのアクセス権限がなければエラー
	unless( -w $str_dir_db ){ sub_error_exit("Error : unable to write at ".$str_dir_db, $q_ref); }
	unless( -w $str_dir_backup ){ sub_error_exit("Error : unable to write at ".$str_dir_backup, $q_ref); }

	# DBが存在しなければ、作成する
	sub_make_new_table($q_ref);

	# 一時ファイルを消去する
	if( -e $str_filepath_csv_tmp){
		unlink($str_filepath_csv_tmp) or sub_error_exit("Error : ".$str_filepath_csv_tmp." not possible delete", $q_ref);
	}

}

# ホーム画面（DB内のデータ数を表示）
sub sub_disp_home{
	print("<h1>Home Screen (ホーム画面)</h1>\n".
		"<p>Databaseに登録されているデータ数を検索中 ...</p>\n");

	my $dbh = undef;
	eval{
		# SQLサーバに接続
		$dbh = DBI->connect($str_dsn, "", "", {PrintError => 0, AutoCommit => 1}) or die("DBI open error : ".$DBI::errstr);

		# TBL_ADDRBOOK内のデータ行数を求める
		my $str_sql = "select count(*) from TBL_PLS";
		my $sth = $dbh->prepare($str_sql) or die("DBI prepare error : ".$DBI::errstr);
		$sth->execute() or die("DBI execute error : ".$DBI::errstr);

		my @arr = $sth->fetchrow_array();
		print("<p class=\"info\">データベースには  ".$arr[0]." 件のデータが格納されています</p>");
		$sth->finish();
		$dbh->disconnect or die(DBI::errstr);
	};
	if($@){
		# evalによるDBエラートラップ：エラー時の処理
		if(defined($dbh)){ $dbh->disconnect(); }
		my $str = $@;
		chomp($str);
		sub_error_exit($str);
	}
}

# データ一覧を画面表示
sub sub_list_db{
	print("<h1>List Database (データベース内のデータ一覧)</h1>\n");

	my $dbh = undef;
	eval{
		# SQLサーバに接続
		$dbh = DBI->connect($str_dsn, "", "", {PrintError => 0, AutoCommit => 1}) or die("DBI open error : ".$DBI::errstr);

		# TBL_ADDRBOOKの全データを読み出す
		my $str_sql = "select * from TBL_PLS ORDER BY title";
		my $sth = $dbh->prepare($str_sql) or die("DBI prepare error : ".$DBI::errstr);
		$sth->execute() or die("DBI execute error : ".$DBI::errstr);
		print("<ul>\n");
		while(my @arr = $sth->fetchrow_array()){
			for(my $i=0; $i<=$#arr; $i++){
				if(defined($arr[$i]) && length($arr[$i])>0){ $arr[$i] = encode_entities(sub_conv_to_flagged_utf8($arr[$i], 'utf8')); }
			}
			printf("<li class=\"music\"><a href=\"".$str_this_script."?mode=edit&amp;idx=%d\" class=\"music\">%s</a><span style=\"color:gray;\">&nbsp;(%s)</span></li>",
					defined($arr[0])?$arr[0]:'0', defined($arr[1])?$arr[1]:'', defined($arr[2])?$arr[2]:'');
		}
		print("</ul>\n");
		$sth->finish();
		$dbh->disconnect or die(DBI::errstr);
	};
	if($@){
		# evalによるDBエラートラップ：エラー時の処理
		if(defined($dbh)){ $dbh->disconnect(); }
		my $str = $@;
		chomp($str);
		sub_error_exit($str);
	}

}

# DBをバックアップ
sub sub_backup_db{
	my $flag_title_disable = shift;		# <h1>タグを省略する場合 1 を渡す

	my $str_filepath_backup;
	my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	$str_filepath_backup = sprintf("%s/%04d_%02d_%02d_%02d_%02d_%02d.pls", $str_dir_backup, $year+1900, $mon+1, $mday, $hour, $min, $sec); 

	unless(defined($flag_title_disable)){
		print("<h1>Backup Database (データベースのバックアップ)</h1>\n");
	}

	if( -e $str_filepath_backup ){ sub_error_exit("バックアップファイル ".$str_filepath_backup." がすでに存在します"); }

	sub_download_csv(\$q, 'pls', $str_filepath_backup);

	print("<p class=\"info\">$str_filepath_backup にバックアップ完了</p>\n");

}

# CSVファイルアップロードのためのファイル選択画面
sub sub_disp_upload_filepick{
	print("<h1>Upload PLS tyle playlist file (プレイリストファイルのアップロード)</h1>\n".
		"<p>Playlist（PLS形式）ファイルをDatabaseに取り込みます</p>\n".
		"<p>&nbsp;</p>\n".
		"<form method=\"post\" action=\"$str_this_script\" enctype=\"multipart/form-data\">\n".
		"CSVファイル\n".
		"<p><input type=\"file\" name=\"uploadfile\" value=\"\" size=\"20\" />\n".
		"<input type=\"submit\" value=\"アップロード\" /></p>\n".
		"<p><input type=\"checkbox\" name=\"purge_data\" value=\"purge\" checked=\"checked\" />DB初期化後に新規追加する</p>".
		"</form>\n");
}

# CSVファイルをアップロード
sub sub_upload_csv{
	my $q_ref = shift;
	my $flag_purge_data = shift;

	print("<h1>Upload CSV datafile (CSVファイルのアップロード)</h1>\n".
		"<p>アップロードファイルを一時保存中 ...</p>\n");
	my $str_filename = $$q_ref->param('uploadfile');
	print("<p>アップロードされたファイル = ".$str_filename."</p>\n");

	my $fh = $$q_ref->upload('uploadfile');
	my $str_temp_filepath = $$q_ref->tmpFileName($fh);

	print("<p>ファイルアップロード処理中 ...(".$str_temp_filepath.")</p>\n");

	File::Copy::move($str_temp_filepath, $str_filepath_csv_tmp) or sub_error_exit("Error : 一時ファイル ".$str_filepath_csv_tmp." の移動処理失敗");

	close($fh);

	unless( -f $str_filepath_csv_tmp ){ sub_error_exit("Error : 一時ファイル ".$str_filepath_csv_tmp." の存在が検知できない"); }


#	print("<p>Databaseをバックアップ中 ...</p>\n");
#	sub_backup_db('disbale_h1');
	
	if($flag_purge_data == 1){
		print("<p>既存テーブルを削除中 ...</p>\n");
		sub_purge_db_table($q_ref);
	}


	sub_add_from_csv();
	
	unlink($str_filepath_csv_tmp);

	print "<p class=\"ok\">データの取り込み完了</p>\n";
	
}

# テーブルが存在しない場合に新規作成
# sub_make_new_table();
# sub_make_new_table($q_ref);	# エラー時画面出力にHTML構文開始も含める
sub sub_make_new_table {
	my $q_ref = shift;

	my $dbh = undef;
	eval{
		# SQLサーバに接続
		$dbh = DBI->connect($str_dsn, "", "", {PrintError => 0, AutoCommit => 1}) or die("DBI open error : ".$DBI::errstr);

		# TABLEが存在するかクエリを行う
		my $str_sql = "select count(*) from sqlite_master where type='table' and name='TBL_PLS'";
		my $sth = $dbh->prepare($str_sql) or die("DBI prepare error : ".$DBI::errstr);
		$sth->execute() or die("DBI execute error : ".$DBI::errstr);
		my @arr = $sth->fetchrow_array();
		if($arr[0] == 1){
			# テーブル数が1の時は、テーブルが存在するためサブルーチンを終了する
			$sth->finish();
			$dbh->disconnect;
			return;
		}
		$sth->finish();

		# テーブルを新規作成する
		$str_sql = "CREATE TABLE TBL_PLS(".
					"idx INTEGER PRIMARY KEY AUTOINCREMENT,".
					"title TEXT,".
					"file TEXT,".
					"length TEXT)";
		$sth = $dbh->prepare($str_sql) or die(DBI::errstr);
		$sth->execute() or die(DBI::errstr);
		$sth->finish();

		$dbh->disconnect or die(DBI::errstr);
	};
	if($@){
		# evalによるDBエラートラップ：エラー時の処理
		if(defined($dbh)){ $dbh->disconnect(); }
		my $str = $@;
		chomp($str);
		if(defined($q_ref)){ sub_error_exit($str, $q_ref); }
		else{ sub_error_exit($str); }
	}
	
}

# DBのTBL_ADDRBOOKテーブルのデータを空にする
sub sub_purge_db_table{
	my $q_ref = shift;

	my $dbh = undef;
	eval{
		# SQLサーバに接続
		$dbh = DBI->connect($str_dsn, "", "", {PrintError => 0, AutoCommit => 1}) or die("DBI open error : ".$DBI::errstr);

		# TABLEが存在するかクエリを行う
		my $str_sql = "select count(*) from sqlite_master where type='table' and name='TBL_PLS'";
		my $sth = $dbh->prepare($str_sql) or die("DBI prepare error : ".$DBI::errstr);
		$sth->execute() or die("DBI execute error : ".$DBI::errstr);
		my @arr = $sth->fetchrow_array();
		if($arr[0] != 1){
			# テーブル数が1の時は、テーブルが存在しないためサブルーチンを終了する
			$sth->finish();
			$dbh->disconnect;
			return;
		}
		$sth->finish();

		# テーブルを削除する
		$str_sql = "drop table TBL_PLS";
		$sth = $dbh->prepare($str_sql) or die(DBI::errstr);
		$sth->execute() or die(DBI::errstr);
		$sth->finish();

		$dbh->disconnect or die(DBI::errstr);
		
		# テーブルを新規作成する
		sub_make_new_table();
	};
	if($@){
		# evalによるDBエラートラップ：エラー時の処理
		if(defined($dbh)){ $dbh->disconnect(); }
		my $str = $@;
		chomp($str);
		sub_error_exit($str);
	}

}

# thunderbird形式CSVを読み込んで、DBに追加する
sub sub_add_from_csv {
	# ファイル存在の判定、登録件数表示を追加する必要がある
	my @arr_data = ();
	sub_read_from_pls($str_filepath_csv_tmp, \@arr_data);
	sub_add_to_db($str_dsn, \@arr_data);


}


sub sub_read_from_pls{
	# 引数
	my $filepath = shift;	# 読み込むplaylist(PLS)ファイル名
	my $ref_arr = shift;	# PLSデータを格納する二次配列（へのリファレンス）

	my %hash_data;
	my $n_maxsuffix = 1;	# 添字（tilteキーの添字数字）の最大値

	print("<p>PLSファイルを読込中 ...</p>\n");

	unless( -f $filepath ){
		print("<p class=\"error\">作業用ファイル ".$filepath." が存在しません</p>\n");
		return;
	}
	my $enc = sub_get_encode_of_file($filepath);
	print("<p>入力PLSの文字コード検出 : ".$enc."</p>\n");


	# PLSファイルを読み込んで、ハッシュ形式に一旦格納する
	open(FH, '<'.$filepath) or return;
	while(<FH>){
		chomp;
		if(length($_)<3){ next; }	# 3文字以下は行スキップ
		my @arr = split(/=/, $_, 2);
		if($#arr != 1){ next; }		# = で分離できないときは行スキップ
		
		# キー名（title,file,lengthのいずれか）、値が存在することをチェック
		if($arr[1] eq '' and $arr[0] =~ m/^length[0-9]*$/i){ $arr[1] = '-1'; }	#lengthが空白の時は-1を代入（NetRadio）
		if($arr[0] eq '' or $arr[1] eq ''){ next; }	# キー名or値が空白の時はスキップ
		unless($arr[0] =~ m/^title[0-9]*$/i or $arr[0] =~ m/^file[0-9]*$/i or $arr[0] =~ m/^length[0-9]*$/i){ next; }

		# キー・値のペアを一旦ハッシュに代入しておく
		if(!defined($hash_data{lc($arr[0])})){
			# 一致するキーがなければハッシュに代入
			$hash_data{lc($arr[0])} = sub_conv_to_flagged_utf8($arr[1], $enc);
		}
		else{
			print("<p class=\"info\">重複キーをスキップ：".$arr[0]."<p>\n");
		}
		
		# キー名 title 末尾の添数字の最大値を覚えておく
		if($arr[0] =~ m/title([0-9]*)/i){
			if($n_maxsuffix < $1){ $n_maxsuffix = $1; }
		}
	}
	close(FH);


	# 添字の小さい順にスキャンし、配列（リファレンス）に格納する
	my $n_added_count = 0;
	for(my $i=0; $i<=$n_maxsuffix; $i++){
		if(defined($hash_data{'title'.$i})){
	#		print('title'.$i.'='.$hash_data{'title'.$i}."\n");
			my @arr = ($hash_data{'title'.$i},
					defined($hash_data{'file'.$i}) ? $hash_data{'file'.$i} : '',
					defined($hash_data{'length'.$i}) ? $hash_data{'length'.$i} : '-1'		
					);
			push(@$ref_arr, [@arr]);
			$n_added_count++;	# 追加した個数をカウント
		}
	}
	
	print("<p class=\"info\">".$n_added_count."件のデータがPLSファイルに確認されました<p>\n");

	return;
}



sub sub_add_to_db{
	# 引数
	my $dsn = shift;		# DSN
	my $ref_arr = shift;	# PLSデータを格納する二次配列（へのリファレンス）

	print("<p>Databaseに登録中 ...</p>\n");

	my $dbh = undef;
	eval{
		# SQLサーバに接続
		$dbh = DBI->connect($dsn, "", "", {PrintError => 0, AutoCommit => 1}) or die(DBI::errstr);


		# TABLEが存在するかクエリを行う
		my $str_sql = "select count(*) from sqlite_master where type='table' and name='TBL_PLS'";
		my $sth = $dbh->prepare($str_sql) or die(DBI::errstr);
		$sth->execute() or die(DBI::errstr);
		my @arr = $sth->fetchrow_array();
		$sth->finish();
		
		if($arr[0] != 1){
			print("<p class=\"error\">DatabaseにTBL_PLSが見つからない<p>\n");
			return;
		}

		# データを追加する
		my $n_added_count = 0;
		$str_sql = 'INSERT INTO TBL_PLS (title,file,length) VALUES (?,?,?)';
		$sth = $dbh->prepare($str_sql) or die(DBI::errstr);
		
		for(my $i=0; $i<=$#$ref_arr; $i++){
			print("<p>&nbsp;&nbsp;add: ".encode_entities($ref_arr->[$i][0])."</p>\n");
			$sth->execute($ref_arr->[$i][0],$ref_arr->[$i][1],$ref_arr->[$i][2]) or die(DBI::errstr);
			$n_added_count++;
		}
		$sth->finish();
		print("<p class=\"info\">".$n_added_count."件のデータをDatabaseに保存しました<p>\n");

		# DBを閉じる
		$dbh->disconnect() or die(DBI::errstr);
	};
	if($@){
			# evalによるDBエラートラップ：エラー時の処理
			if(defined($dbh)){ $dbh->disconnect(); }
			my $str = $@;
			chomp($str);
			sub_error_exit($str);
	}

	return;
}



# ダウンロード メニューの表示
sub sub_disp_download{
	print("<h1>Download CSV datafile (CSVファイルのダウンロード)</h1>\n");
	
	print("<ul>\n<li><a href=\"".$str_this_script."?mode=download_pls\">PLS形式でダウンロード（title,file順）</a></li>\n".
		"<li><a href=\"".$str_this_script."?mode=download_pls_ft\">PLS形式でダウンロード（file,title順）</a></li>\n".
		"<li><a href=\"".$str_this_script."?mode=download_csv\">CSV形式でダウンロード</a></li>\n</ul>\n");
}

# CSVのダウンロード
sub sub_download_csv{
	my $q_ref = shift;
	my $csv_mode = shift;	# 出力形式（pls, pls_ft, csv）
	my $output_mode = shift;	# 出力モード（download, $backup_filepath）

	my $str_filepath_backup = $output_mode;		# バックアップ時はファイル名

	# ダウンロード用のヘッダを出力
	if($output_mode eq 'download'){
		if($csv_mode eq 'pls' || $csv_mode eq 'pls_ft'){
			print $$q_ref->header(-type=>'application/octet-stream', -attachment=>'playlist.pls');
		}
		else{
			print $$q_ref->header(-type=>'application/octet-stream', -attachment=>'playlist.csv');
		}
		# print qq{Content-Disposition: attachment; filename="filename.csv"\n};
		# print qq{Content-type: application/octet-stream\n\n};
	}

	my $dbh = undef;
	eval{
		# SQLサーバに接続
		$dbh = DBI->connect($str_dsn, "", "", {PrintError => 0, AutoCommit => 1}) or die(DBI::errstr);

		# 件数を得る（$arr_rows[0]に件数が入る）
		my $str_sql = "select count(*) from TBL_PLS";
		my $sth = $dbh->prepare($str_sql) or die($DBI::errstr);
		$sth->execute() or die($DBI::errstr);
		my @arr_rows = $sth->fetchrow_array();
		$sth->finish();
		$sth = undef;
		
		# 全行のデータを読み出す
		$str_sql = "select title,file,length from TBL_PLS order by title";
		$sth = $dbh->prepare($str_sql) or die($DBI::errstr);
		$sth->execute() or die($DBI::errstr);

		if($output_mode ne 'download'){
			open(FH, '>'.$str_filepath_backup) or die("バックアップファイル ".$str_filepath_backup." に書き込めません");
			binmode(FH, ":utf8");
		}
		
		# CSVヘッダ行出力
		if($csv_mode eq 'csv'){
			if($output_mode eq 'download'){ print("title,file,length\n"); }
			else{ print(FH "title,file,length\n"); }
		}
		# Playlistヘッダ出力
		if($csv_mode eq 'pls' || $csv_mode eq 'pls_ft'){
			if($output_mode eq 'download'){ print("[playlist]\nnumberofentries=".$arr_rows[0]."\n\n"); }
			else{ print(FH "[playlist]\nnumberofentries=".$arr_rows[0]."\n\n"); }
		}
		

		my $csv = Text::CSV_XS->new({binary=>1});

		my $i=0;
		while(my @arr = $sth->fetchrow_array()){
			for(my $j=0; $j<=2; $j++){
				if($arr[$j] ne ''){ $arr[$j] = sub_conv_to_flagged_utf8($arr[$j], 'utf8'); }
			}
			
			my $str = '';
			if($csv_mode eq 'csv'){
				$csv->combine(@arr);
				$str = $csv->string() . "\n";
			}
			elsif($csv_mode eq 'pls'){
				$str = sprintf("Title%d=%s\nFile%d=%s\nLength%d=%s\n\n",
						$i, $arr[0], $i, $arr[1], $i, $arr[2]);
				$i++;
			}
			elsif($csv_mode eq 'pls_ft'){
				$str = sprintf("File%d=%s\nTitle%d=%s\nLength%d=%s\n\n",
						$i, $arr[1], $i, $arr[0], $i, $arr[2]);
				$i++;
			}
			
			if($output_mode eq 'download'){ print($str); }
			else{ print(FH $str); }
		}

		# Playlistフッタ出力
		if($csv_mode eq 'pls' || $csv_mode eq 'pls_ft'){
			if($output_mode eq 'download'){ print("Version=2\n"); }
			else{ print(FH "Version=2\n"); }
		}

		if($output_mode ne 'download'){ close(FH); }


		$sth->finish();
		# DBを閉じる
		$dbh->disconnect() or die(DBI::errstr);
	};
	if($@){
			# evalによるDBエラートラップ：エラー時の処理
			if(defined($dbh)){ $dbh->disconnect(); }
			my $str = $@;
			chomp($str);
			sub_error_exit($str);
	}
}

# リストアのファイル選択画面を表示する
sub sub_disp_restore_select{
	print("<h1>Select restore target file (データベース復元もとの選択)</h1>\n");
	
	my @arr_filelist = glob($str_dir_backup.'/*.pls');
	
	print("<ul>\n");
	foreach(@arr_filelist){
		print("<li><a href=\"".$str_this_script."?mode=restore&amp;file=".basename($_)."\">".$_."</a></li>\n");
	}
	print("</ul>\n");
}

# データベースを指定されたファイルでリストアする
sub sub_restore{
	my $str_filepath_backup = shift;
	my $flag_purge_data = 1;

	print("<h1>Restore database (データベースの復元)</h1>\n");
	
	defined($str_filepath_backup) or sub_error_exit("Error : 復元元ファイル名が指定されていません");
	$str_filepath_backup = $str_dir_backup . '/' . $str_filepath_backup;
	unless( -f $str_filepath_backup){ sub_error_exit("Error : 復元元ファイル".$str_filepath_backup."が存在しません"); }

	print("<p>復元元ファイル : ".$str_filepath_backup."</p>\n");

	File::Copy::copy($str_filepath_backup, $str_filepath_csv_tmp) or sub_error_exit("Restore File move error");


	print("<p>Databaseを検証中 ...</p>\n");
	if($flag_purge_data == 1){
		print("<p>既存テーブルを削除中 ...</p>\n");
		sub_purge_db_table();
		sub_make_new_table();
	}


	print("<p>Databaseに登録中 ...</p>\n");
	sub_add_from_csv();
	
	unlink($str_filepath_csv_tmp);

	print "<p class=\"ok\">データの取り込み完了</p>\n";

}


# DB項目の編集
# sub_edit_db(\$q);
sub sub_edit_db{
	my $q_ref = shift;

	my $idx = undef;	# TBL_PLS の idx に対応
	my $title = undef;	# 書き換え用 post パラメータ受け取り
	my $file = undef;	# 書き換え用 post パラメータ受け取り
	my $length = undef;	# 書き換え用 post パラメータ受け取り

	print("<h1>Edit database entry (データベースの項目編集)</h1>\n");
	
	if(defined($$q_ref->url_param('idx'))){ $idx = $$q_ref->url_param('idx'); }
	if(!defined($idx) or $idx < 0){ sub_error_exit('想定外のURLパラメータ'); }

	# URLパラメータとPOSTパラメータのidxは同一のはず（仕様）$q->postはpostパラメータを優先して出力
	if(defined($$q_ref->param('idx'))){
		if($$q_ref->param('idx') ne $idx){
			sub_error_exit('想定外のURLパラメータ');
		}
	}
	# postパラメータを得る
	if(defined($$q_ref->param('title'))){ $title = sub_conv_to_flagged_utf8(decode_entities($$q_ref->param('title')), 'utf8'); }
	if(defined($$q_ref->param('file'))){ $file = sub_conv_to_flagged_utf8(decode_entities($$q_ref->param('file')), 'utf8'); }
	if(defined($$q_ref->param('length'))){ $length = sub_conv_to_flagged_utf8(decode_entities($$q_ref->param('length')), 'utf8'); }

	my $dbh = undef;
	eval{
		# SQLサーバに接続
		$dbh = DBI->connect($str_dsn, "", "", {PrintError => 0, AutoCommit => 1}) or die("DBI open error : ".$DBI::errstr);

		# データ１件削除
		if(defined($$q_ref->param('delete'))){
			print("<p>1件のデータ (idx=".$idx.") を削除中...</p>\n");
			# TBL_PLSのidx=$idxデータを変更する
			my $str_sql = "delete from TBL_PLS where idx=?";
			my $sth = $dbh->prepare($str_sql) or die("DBI prepare error : ".$DBI::errstr);
			$sth->execute($idx) or die("DBI execute error : ".$DBI::errstr);
			$sth->finish();
			print("<p class=\"ok\">1件のデータ削除完了</p>\n");
			$dbh->disconnect or die(DBI::errstr);
			
			return;
		}


		# TBL_PLS のデータ書き換え（UPDATE命令）
		if(defined($title) and $title ne '' and defined($file) and $file ne '' and defined($length) and $length ne ''){
			print("<p>DB書き換え中...</p>\n");
			# TBL_PLSのidx=$idxデータを変更する
			my $str_sql = "update TBL_PLS set title=?,file=?,length=? where idx=?";
			my $sth = $dbh->prepare($str_sql) or die("DBI prepare error : ".$DBI::errstr);
			$sth->execute($title,$file,$length,$idx) or die("DBI execute error : ".$DBI::errstr);
			$sth->finish();
			print("<p class=\"ok\">DB書き換え完了</p>\n");
		}

		# TBL_PLSのidx=$idxデータを読み出す
		my $str_sql = "select idx,title,file,length from TBL_PLS where idx=?";
		my $sth = $dbh->prepare($str_sql) or die("DBI prepare error : ".$DBI::errstr);
		$sth->execute($idx) or die("DBI execute error : ".$DBI::errstr);

		my @arr = $sth->fetchrow_array();
		if($sth->rows != 1){
			print("<p>idxに一致するデータが存在しません</p>\n");
		}
		else{
			print("<form method=\"post\" action=\"".$str_this_script."?mode=edit&amp;idx=".$arr[0]."\" name=\"form1\"></p>\n".
					"<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n".
					" <tr><td>idx</td><td><input name=\"idx\" type=\"text\" size=\"10\" value=\"".$arr[0]."\" readonly=\"readonly\" />（変更不可）</td></tr>\n".
					" <tr><td>title</td><td><input name=\"title\" type=\"text\" size=\"50\" value=\"".encode_entities(sub_conv_to_flagged_utf8($arr[1], 'utf8'))."\" /></td></tr>\n".
					" <tr><td>file</td><td><input name=\"file\" type=\"text\" size=\"50\" value=\"".encode_entities(sub_conv_to_flagged_utf8($arr[2], 'utf8'))."\" /></td></tr>\n".
					" <tr><td>length</td><td><input name=\"length\" type=\"text\" size=\"10\" value=\"".encode_entities(sub_conv_to_flagged_utf8($arr[3],'utf8'))."\" /></td></tr>\n".
					"</table>\n".
					"<p><input type=\"submit\" value=\"データ更新\" />&nbsp;<input type=\"submit\" name=\"delete\" value=\"このデータを削除\" /></p>\n".
					"</form>\n");

		}
		
		$sth->finish();
		$dbh->disconnect or die(DBI::errstr);
	};
	if($@){
		# evalによるDBエラートラップ：エラー時の処理
		if(defined($dbh)){ $dbh->disconnect(); }
		my $str = $@;
		chomp($str);
		sub_error_exit($str);
	}
}


# DBに新規項目追加
# sub_edit_db(\$q);
sub sub_edit_addnew_db{
	my $q_ref = shift;

	my $title = undef;	# 書き換え用 post パラメータ受け取り
	my $file = undef;	# 書き換え用 post パラメータ受け取り
	my $length = undef;	# 書き換え用 post パラメータ受け取り

	print("<h1>Add database entry (データベースに1件追加)</h1>\n");
	
	# postパラメータを得る
	if(defined($$q_ref->param('title'))){ $title = sub_conv_to_flagged_utf8(decode_entities($$q_ref->param('title')), 'utf8'); }
	if(defined($$q_ref->param('file'))){ $file = sub_conv_to_flagged_utf8(decode_entities($$q_ref->param('file')), 'utf8'); }
	if(defined($$q_ref->param('length'))){ $length = sub_conv_to_flagged_utf8(decode_entities($$q_ref->param('length')), 'utf8'); }

	if((defined($title) and $title ne '') and (!defined($file) or $file eq '')){
		print("<p class=\"info\">fileが空欄のため、データ追加できません</p>\n");
		return;
	}
	elsif((!defined($title) or $title eq '') and (defined($file) and $file ne '')){
		print("<p class=\"info\">titleが空欄のため、fileの値をコピーしました</p>\n");
		$title = $file;
	}
	if(defined($title) and !defined($length)){ $length = -1; }

	if(defined($title) and $title ne ''){
		my $dbh = undef;
		eval{
			# SQLサーバに接続
			$dbh = DBI->connect($str_dsn, "", "", {PrintError => 0, AutoCommit => 1}) or die("DBI open error : ".$DBI::errstr);

			# TBL_PLS へのデータ追加（INSERT命令）
			print("<p>DBへ新規追加中...</p>\n");
			my $str_sql = "insert into TBL_PLS (title,file,length) values (?,?,?)";
			my $sth = $dbh->prepare($str_sql) or die("DBI prepare error : ".$DBI::errstr);
			$sth->execute($title,$file,$length) or die("DBI execute error : ".$DBI::errstr);
			$sth->finish();
			print("<p class=\"ok\">DBへ新規追加完了</p>\n");
			
			$dbh->disconnect or die(DBI::errstr);
		};
		if($@){
			# evalによるDBエラートラップ：エラー時の処理
			if(defined($dbh)){ $dbh->disconnect(); }
			my $str = $@;
			chomp($str);
			sub_error_exit($str);
		}
	}
	else{
		print("<form method=\"post\" action=\"".$str_this_script."?mode=add\" name=\"form1\"></p>\n".
				"<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n".
				" <tr><td>title</td><td><input name=\"title\" type=\"text\" size=\"50\" /></td></tr>\n".
				" <tr><td>file</td><td><input name=\"file\" type=\"text\" size=\"50\" /></td></tr>\n".
				" <tr><td>length</td><td><input name=\"length\" type=\"text\" size=\"10\" value=\"-1\" /></td></tr>\n".
				"</table>\n".
				"<p><input type=\"submit\" value=\"データ追加\" /></p>\n".
				"</form>\n");

	}
}


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
