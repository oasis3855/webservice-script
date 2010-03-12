#!/usr/bin/perl
# ******************************************************
# Software name : HTML::TreeBuilderによるHTML整形 （XHTML対応）
#                 指定URLよりダウンロード版
#
# Copyright (C) INOUE Hirokazu, All Rights Reserved
#     http://oasis.halfmoon.jp/
#
# version 1.0 (2010/March/13)
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

# 自身のスクリプト名
my $strThisScript = basename( $0, '' );
sub_print_html_header();
my $q = CGI->new;
if ( $q->param('url') ) {
    sub_print_html( $q->param('url') );
}
else {
    sub_print_inputform();
}
print "<p>&nbsp;</p>\n<p><a href=\"" . $strThisScript . "\">初期画面（URL入力）に戻る</a></p>\n";
sub_print_html_footer();
exit(0);

sub sub_print_html_header {
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
    return;
}

sub sub_print_html_footer {
    print "</body>\n</html>\n";
    return;
}

sub sub_print_inputform {
    print "<form action=\""
      . $strThisScript
      . "\" method=\"post\">\n"
      . "URL: \n"
      . "<input type=\"text\" name=\"url\" size=\"50\" />\n"
      . "<input type=\"submit\" value=\"HTMLを整形する\" />\n"
      . "</form>\n";
    return;
}

sub sub_print_html {
    my $strURL = $_[0];
    print "<p>整形するHTMLへのURL：" . $strURL . "</p>\n";
    my @arr_urlstr = split( /:/, $strURL );
    if ( lc( $arr_urlstr[0] ) eq 'https' ) {
        print "<p>https には対応していません (HTTP::Liteの制限)</p>\n";
        return;
    }
    elsif ( lc( $arr_urlstr[0] ) ne 'http' ) {
        print "<p>http 以外のプロトコルには対応していません</p>\n";
        return;
    }
    my $http   = new HTTP::Lite;
    my $req    = $http->request($strURL) or &sub_error_exit($!);
    my $body   = $http->body();
    my $encode = guess_encoding( $body, qw/ euc-jp shiftjis 7bit-jis / );
    $body = decode( $encode->name, $body ) unless ( utf8::is_utf8($body) );
    my $tree = HTML::TreeBuilder->new;
    $tree->parse($body);
    $tree->eof();
    print "<pre>\n";

    # インデントをtabで出力する場合
    # print encode_entities( $tree->as_HTML( '<>&', "\t", {} ), '<>&' );
    # インデントをspace2個で出力する場合
    print encode_entities( $tree->as_HTML( '<>&', "  ", {} ), '<>&' );
    print "</pre>\n";
    return;
}

sub sub_error_exit {
    print "<p>エラー （Error ID:" . $_[0] . "）</p>\n";
    print "<p>&nbsp;</p>\n<p><a href=\"" . $strThisScript . "\">初期画面（URL入力）に戻る</a></p>\n";
    &sub_print_html_footer();
    exit(0);
}
