#!/usr/bin/perl
# ******************************************************
# Software name : HTML::TreeBuilderによるHTML整形 （XHTML対応）
#                 ファイルアップロード版
#
# Copyright (C) INOUE Hirokazu, All Rights Reserved
#     http://oasis.halfmoon.jp/
#
# version 1.0 (2010/03/13)
# version 1.1 (2010/03/19)
# version 1.2 (2022/03/24)
#
# GNU GPL Free Software
# http://www.opensource.jp/gpl/gpl.ja.html
# ******************************************************
use strict;
use utf8;                   # スクリプト内utf8処理
binmode STDOUT, ":utf8";    # messagesに「Wide character in print at …」書き込み回避

# ユーザディレクトリ下のCPANモジュールを読み込む
use lib ( ( getpwuid($<) )[7] ) . '/local/cpan/lib/perl5';
use lib ( ( getpwuid($<) )[7] ) . '/local/lib/perl5';
use lib ( ( getpwuid($<) )[7] ) . '/local/lib/perl5/site_perl/5.8.9/mach';
use lib ( ( getpwuid($<) )[7] ) . '/local/lib/perl5/amd64-freebsd';
use CGI;                            # GET, POSTの処理
use File::Basename qw(basename);    # 自身のスクリプト名を得るため
use HTTP::Lite;
use HTML::TreeBuilder;
use HTML::Entities;
use Encode;
use Encode::Guess;
use HTML::Tagset;
use Image::Size;
use LWP::UserAgent;

# 自身のスクリプト名
my $strThisScript = basename( $0, '' );

# 機能のスイッチ（POSTで送信されてくる）
my $boolWriteBooleanAttr = 0;
my $boolRewriteAttrSmall = 0;
my $boolImgAlt           = 0;
my $boolIndentTab        = 0;
my $boolDownload         = 0;
my $boolSetImgsize       = 0;
my $strRefImgHttp        = "";    # NULL文字列の場合は画像サイズを消去

# デバッグ用
my $boolDebug = 0;

# 属性の値を「小文字」にする属性名の配列
my @arrAttr = (
    [ 'basefont', 'color' ],
    [ 'body',     'bgcolor', 'text', 'link', 'vlink', 'alink' ],
    [ 'br',       'clear' ],
    [ 'div',      'align' ],
    [ 'font',     'color' ],
    [ 'form',     'method' ],
    [ 'h1',       'align' ],
    [ 'h2',       'align' ],
    [ 'h3',       'align' ],
    [ 'h4',       'align' ],
    [ 'h5',       'align' ],
    [ 'hr',       'color', 'align' ],
    [ 'img',      'align' ],
    [ 'input',    'type' ],
    [ 'li',       'type' ],
    [ 'option',   'selected', 'disabled' ],
    [ 'p',        'align' ],
    [ 'select',   'multiple', 'disabled' ],
    [
        'table',            'align',           'bgcolor', 'bordercolor',
        'bordercolorlight', 'bordercolordark', 'frame',   'rules'
    ],
    [ 'td',       'bgcolor',  'align',    'valign', 'nowrap' ],
    [ 'textarea', 'disabled', 'readonly', 'wrap' ],
    [ 'th',       'bgcolor',  'align',    'valign', 'nowrap' ],
    [ 'tr',       'bgcolor',  'align',    'valign' ],
    [ 'ul',       'type' ]
);
my $q = CGI->new;

# 機能スイッチの設定
if ( $q->param('chk_bool') )       { $boolWriteBooleanAttr = 1; }
if ( $q->param('chk_attrsmall') )  { $boolRewriteAttrSmall = 1; }
if ( $q->param('chk_imgalt') )     { $boolImgAlt           = 1; }
if ( $q->param('chk_tab') )        { $boolIndentTab        = 1; }
if ( $q->param('chk_debug') )      { $boolDebug            = 1; }
if ( $q->param('chk_download') )   { $boolDownload         = 1; }
if ( $q->param('chk_setimgsize') ) { $boolSetImgsize       = 1; }
if ( $q->param('refimghttp') )     { $strRefImgHttp        = $q->param('refimghttp'); }
sub_print_html_header();

# ファイルアップロードされたときの処理
if ( $q->param('uploadfile') ) {
    my $fi          = $q->param('uploadfile');
    my $strMimeType = $q->uploadInfo($fi)->{'Content-Type'};
    if ( $strMimeType =~ /^text\// ) {

        # ファイルの読み込み
        my $datBuffer = "";
        my $datFile   = "";
        while ( read( $fi, $datBuffer, 2048 ) ) {
            $datFile .= $datBuffer;
        }

        # ファイルへ保存
        open( fo, "> upload/temp.txt" ) or sub_error_exit($!);
        binmode(fo);
        print( fo $datFile );
        close(fo);
        if ( !$boolDownload ) { print "<p>一時ファイルに保存しました</p>\n"; }
        sub_print_html();
    }
    else {
        print "<p>テキストファイル以外はアップロードできません</p>\n";
    }
}
else {
    sub_print_inputform();
}
if ( !$boolDownload ) {
    print "<p>&nbsp;</p>\n<p><a href=\"" . $strThisScript . "\">初期画面に戻る</a></p>\n";
}
sub_print_html_footer();

#一時ファイルの削除
unlink("upload/temp.txt");
exit(0);

sub sub_print_html_header {
    if ($boolDownload) {
        print "Content-Type: application/octet-stream\n"
          . "Content-Disposition: attachment; filename=output.html\n" . "\n";
    }
    else {
        print "Content-type: text/html\n\n";
        print
"<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n"
          . "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"ja\" lang=\"ja\">\n"
          . "<head>\n"
          . "    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n"
          . "    <meta http-equiv=\"Content-Language\" content=\"ja\" />\n"
          . "    <title>HTML::TreeBuilderによるHTML整形</title>\n"
          . "    <style type=\"text/css\">\n"
          . "    pre { border:1px solid gray; background-color: #c0c0c0; width: 795px; overflow-x: scroll; font-size: 10pt;}\n"
          . "    body {position: relative; width: 800px; margin: 0 auto; font-size: 11pt;};\n"
          . "    </style>\n"
          . "</head>\n"
          . "<body>\n"
          . "<p>HTML::TreeBuilderによるHTML整形</p>\n";
    }
    return;
}

sub sub_print_html_footer {
    if ( !$boolDownload ) {
        print "</body>\n</html>\n";
    }
    return;
}

sub sub_print_inputform {
    print "<form action=\""
      . $strThisScript
      . "\" method=\"post\" enctype=\"multipart/form-data\">\n"
      . "<input type=\"file\" name=\"uploadfile\" size=\"50\" />\n"
      . "<input type=\"submit\" value=\"ファイルをアップロードする\" /><br />\n"
      . "<input type=\"checkbox\" name=\"chk_bool\" checked=\"checked\" />ブール値属性を省略しない(XHTML), \n"
      . "<input type=\"checkbox\" name=\"chk_attrsmall\" checked=\"checked\" />属性値は英数小文字(XHTML), \n"
      . "<input type=\"checkbox\" name=\"chk_imgalt\" checked=\"checked\" />imgにalt属性無い場合は補完(XHTML)<br />\n"
      . "<input type=\"checkbox\" name=\"chk_tab\" checked=\"checked\" />インデントをTABで出力, \n"
      . "<input type=\"checkbox\" name=\"chk_debug\" />デバッグ表示, \n"
      . "<input type=\"checkbox\" name=\"chk_download\" />結果をファイルとしてダウンロード<br />\n"
      . "<input type=\"checkbox\" name=\"chk_setimgsize\" />画像サイズ再設定 → ベースURL : \n"
      . "<input type=\"text\" name=\"refimghttp\" size=\"50\" value=\"http://www.example.com/pics/\" />\n"
      . "</form>\n"
      . "<p>読み込むファイルはあらかじめUTF-8に変換しておくと、文字化けを防げます</p>\n";
    return;
}

sub sub_print_html {
    if ( !$boolDownload ) { print "<p>整形するHTMLファイル：./upload/temp.txt</p>\n"; }
    open( fi, "< upload/temp.txt" ) or sub_error_exit($!);
    my $body      = "";
    my $datBuffer = "";
    while ( read( fi, $datBuffer, 2048 ) ) {
        $body .= $datBuffer;
    }
    close(fi);

    # 文字コードをUTF-8に変換する
    my $encode = guess_encoding( $body, qw/ euc-jp shiftjis 7bit-jis / );
    $body = decode( $encode->name, $body ) unless ( utf8::is_utf8($body) );
    my $tree = HTML::TreeBuilder->new;

    # ブール値属性のハッシュ値を削除する（ハッシュ値を削除することで、省略させないようにする）
    if ($boolWriteBooleanAttr) {
        *HTML::Element::boolean_attr = \%HTML::Tagset::boolean_attr;    # legacy
        foreach my $key ( keys %HTML::Element::boolean_attr ) {
            if ($boolDebug) {
                print "<p>debug(WriteBooleanAttr):  boolean_attr keys="
                  . $key
                  . ",values="
                  . $HTML::Element::boolean_attr{$key}
                  . "</p>\n";
            }
            delete $HTML::Element::boolean_attr{$key};
        }
    }
    $tree->parse($body);

    # imgタグにalt属性が足りない場合、新規作成する
    if ($boolImgAlt) {
        foreach my $node ( $tree->look_down( '_tag' => 'img' ) ) {
            if ( !defined( $node->attr('alt') ) ) {
                my $strHref = " ";
                if ( defined( $node->attr('src') ) ) {

                    # ファイルのベースネームのみ取り出す（指定拡張子も削除する）
                    $strHref = basename( $node->attr('src'), ( '.jpg', '.jpeg', '.gif', 'png' ) );
                    if ($boolDebug) {
                        print "<p>debug (ImgAlt):  alt="
                          . $strHref
                          . ", src="
                          . $node->attr('src')
                          . "</p>\n";
                    }
                }
                $node->attr( 'alt', $strHref );
            }
        }
    }

    # imgタグ内のheight, widthサイズ設定の編集
    if ($boolSetImgsize) {
        foreach my $node ( $tree->look_down( '_tag' => 'img' ) ) {
            if ( defined( $node->attr('width') ) || defined( $node->attr('height') ) ) {
                my $ua     = LWP::UserAgent->new;
                my $strUrl = $strRefImgHttp . $node->attr('src');
                my $req    = HTTP::Request->new( GET => $strUrl );
                my $res    = $ua->request($req);
                if ( $res->is_success ) {
                    my ( $width, $height ) = imgsize( \$res->content );
                    $node->attr( 'width',  $width );
                    $node->attr( 'height', $height );
                }
                else {
                    $node->attr( 'width',  undef );
                    $node->attr( 'height', undef );
                    if ($boolDebug) {
                        print "<p>debug (SetImgsize):  url open error "
                          . basename( $node->attr('src') )
                          . "</p>\n";
                    }
                }
            }
        }
    }

    # 属性値を英数小文字に変換する
    if ($boolRewriteAttrSmall) {
        for my $i ( 0 .. $#arrAttr ) {
            for my $j ( 1 .. $#{ $arrAttr[$i] } ) {

                # $arrAttr[$i][0] : タグ名、$arrAttr[$i][$j] : そのタグに対する属性名
                foreach my $node ( $tree->look_down( '_tag' => $arrAttr[$i][0] ) ) {
                    if ( defined( $node->attr( $arrAttr[$i][$j] ) ) ) {
                        if ( $boolDebug && $node->attr( $arrAttr[$i][$j] ) =~ /[A-Z]/ ) {
                            print "<p>debug (RewriteAttrSmall):  tag="
                              . $arrAttr[$i][0]
                              . ", attr="
                              . $arrAttr[$i][$j]
                              . ", value="
                              . $node->attr( $arrAttr[$i][$j] )
                              . "</p>\n";
                        }
                        $node->attr( $arrAttr[$i][$j], lc( $node->attr( $arrAttr[$i][$j] ) ) );
                    }
                }
            }
        }
    }

    # 全てのタグと、属性を画面表示する
    if ($boolDebug) {
        my @arrAllAttr;
        foreach my $node ( $tree->look_down( '_tag' => qr/.*/ ) ) {
            print "<p>" . $node->tag() . " : " . $node->as_text() . " : \n";
            @arrAllAttr = $node->all_attr();
            foreach my $strAttr (@arrAllAttr) {
                print $strAttr. " , ";
            }
            print "</p>\n";
        }
    }
    $tree->eof();

    # HTMLソースコードを画面出力
    my $strEntityChar = "<>&";

    #	my $strEntityChar = "<>&".pack("U", 0x81)."-".pack("U", 0x38f);
    for ( my $i = 0x0081 ; $i < 0x038f ; $i++ ) { $strEntityChar .= pack( "U", $i ); }
    if ($boolDownload) {
        if   ($boolIndentTab) { print $tree->as_HTML( $strEntityChar, "\t", {} ); }
        else                  { print $tree->as_HTML( $strEntityChar, "  ", {} ); }
    }
    else {
        print "<pre>\n";
        if ($boolIndentTab) {
            print encode_entities( $tree->as_HTML( $strEntityChar, "\t", {} ), '<>&' );
        }
        else { print encode_entities( $tree->as_HTML( $strEntityChar, "  ", {} ), '<>&' ); }
        print "</pre>\n";
    }

    # ツリーを削除
    $tree = $tree->delete;
    return;
}

sub sub_error_exit {
    print "<p>ファイル入出力エラー （Error ID:" . $_[0] . "）</p>\n";
    print "<p>&nbsp;</p>\n<p><a href=\"" . $strThisScript . "\">初期画面に戻る</a></p>\n";
    sub_print_html_footer();

    #一時ファイルの削除
    unlink("upload/temp.txt");
    exit(0);
}
