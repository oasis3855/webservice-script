#!/usr/bin/perl

# save this file in << UTF-8  >> encode !
# ******************************************************
# Software name : HTMLファイルから ALINK（リンク）のみを抜き出す
#
# Copyright (C) INOUE Hirokazu, All Rights Reserved
#     http://oasis.halfmoon.jp/
#
# extract-html-alink.pl
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
use File::Basename;
use HTML::LinkExtor;

# プログラムの引数が存在するとき
if ( $#ARGV == 0 && length( $ARGV[0] ) > 1 ) {
    if ( lc( $ARGV[0] ) eq '-h' || lc( $ARGV[0] ) eq '-?' || lc( $ARGV[0] ) eq '-help' ) {
        print(  "\n"
              . basename($0)
              . " - HTMLからリンクURLを抽出する\n\n"
              . " 使い方 : \n"
              . " 1.標準入力からパイプを通して読み込む場合\n    "
              . basename($0)
              . " < filename\n"
              . " 2.ファイルを指定する場合\n    "
              . basename($0)
              . " filename\n" );
        exit();
    }
}

my $file = shift || '';

if ( $file ne '' && !( -f $file ) ) {
    print( "error: file '" . basename($file) . "' not found\n" );
    exit();
}

my $p = HTML::LinkExtor->new( \&cb );

sub cb {
    my ( $tag, %links ) = @_;

    # <A HREF=...> の場合のみ読み込む
    if ( lc($tag) eq 'a' && defined( $links{'href'} ) && length( $links{'href'} ) > 0 ) {
        print( $links{'href'} . "\n" );
    }
}

if ( $file eq '' ) { $p->parse( join( '', <> ) ); }    # 標準入力から読み込む
else               { $p->parse_file($file); }          # ファイルから読み込む
