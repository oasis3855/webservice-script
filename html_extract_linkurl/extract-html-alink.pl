#!/usr/bin/perl

# save this file in << UTF-8  >> encode !
# ******************************************************
# Software name : HTMLファイルからリンクのみを抜き出す
#
# Copyright (C) INOUE Hirokazu, All Rights Reserved
#     http://oasis.halfmoon.jp/
#
# extract-html-link.pl
#
# Version 0.1  : 2011/02/13
# Version 0.2  : 2014/01/03
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
use LWP::Simple;
use URI::Split;

# プログラムの引数が存在しないとき、ヘルプ画面を表示
if ( $#ARGV < 0 ) {
    print(  "リンク抽出スクリプト\n\n" . "使用法\n" . " "
          . basename($0)
          . " ([a|img]) [url_list_file.txt|url] ([domain|+|-] [path])\n\n"
          . "   a : a link タグより抽出（指定しない場合のデフォルト値）\n"
          . "   img : img タグより抽出\n"
          . "   url_list_file.txt : urlを列挙したテキストファイル\n"
          . "   url : urlを直接指定（例 http://example.com/path/target.html\n"
          . "   domain : 指定したドメイン名に一致するもののみ\n"
          . "            + の場合はurlのドメイン名と一致\n"
          . "            - の場合は全てに一致\n"
          . "   path : パス名に一致するもののみ\n" );
    exit();
}

my $filename;    # 入力ファイル名

# 第1引数のスイッチを解釈、第2引数のファイル名を格納
my $mode = shift;
if ( $mode ne 'a' && $mode ne 'img' ) { $filename = $mode; $mode = 'a'; }
else                                  { $filename = shift; }

if ( !defined($filename) ) { die("Error : no filename|url .\n"); }

# 第3引数、第4引数の格納
my $auth_user = shift;
my $path_user = shift;

# ベースURLマッチパターンが - の時は、undefしておく（以後の判定のため）
if ( defined($auth_user) && $auth_user eq '-' ) { $auth_user = undef; }

# 対象urlリストの作成
my @arr_url;

if ( -r $filename ) {
    open( my $fh, "<", $filename ) || die("Error : unable open file $filename .\n");
    @arr_url = <$fh>;
    close($fh);
}
else {
    push( @arr_url, $filename );
}

foreach my $url (@arr_url) {

    # urlリストの対象行がurlでない場合、あるいはダウンロード不可能な場合、次行へ
    if ( !defined($url) || length($url) <= 0 || $url !~ /^http:\/\// ) { next; }
    my $content;
    unless ( $content = LWP::Simple::get($url) ) { next; }

    my ( $scheme_base, $auth_base, $path_base, $query_base, $frag_base ) =
      URI::Split::uri_split($url);

    my @arr_extracted;

    my $p = HTML::LinkExtor->new(
        sub {
            my ( $tag, %links ) = @_;

            # A タグ または IMG タグの場合のみ処理開始
            if (   lc($tag) eq ( ( $mode eq 'a' ) ? 'a' : 'img' )
                && defined( ( $mode eq 'a' ) ? $links{'href'} : $links{'src'} )
                && length( ( $mode eq 'a' )  ? $links{'href'} : $links{'src'} ) > 0 )
            {
                my $extracted_url = ( ( $mode eq 'a' ) ? $links{'href'} : $links{'src'} );
                my ( $scheme, $auth, $path, $query, $frag ) = URI::Split::uri_split($extracted_url);

                if ( defined($auth) && length($auth) <= 0 ) { $auth = undef; }

                # 抽出したリンクをフィルタに掛ける
                if    ( defined($scheme) && lc($scheme) ne 'http' ) { } # http:// 以外
                elsif ( !defined($scheme) && defined($auth) )       { } # サイト内（http://未指定）で、ドメイン名がある
                elsif ( !defined($auth) && ( !defined($path) || length($path) <= 0 ) ) {
                }                                                       # urlで無い可能性
                elsif (defined($auth_user)
                    && $auth_user eq '+'
                    && defined($auth)
                    && ( lc($auth) ne lc($auth_base) ) )
                {
                }                                                       # 他のドメインは無視
                elsif (defined($auth_user)
                    && $auth_user ne '+'
                    && defined($auth)
                    && ( $auth !~ m/$auth_user/ ) )
                {
                }                                                       # 指定ドメイン以外
                elsif (defined($auth_user)
                    && $auth_user ne '+'
                    && !defined($auth)
                    && ( $auth_base !~ m/$auth_user/ ) )
                {
                }                                                       # 指定ドメイン以外
                elsif ( defined($path_user) && $path !~ /$path_user/ ) { }    # ユーザ指定のパス文字が含まれない
                else {
                    my $href = $extracted_url;

                    # ドメイン名がない場合の補完
                    if ( !defined($scheme) && !defined($auth) && $path !~ /^\// ) {

                        # 相対ディレクトリの場合
                        $href =
                            'http://'
                          . $auth_base
                          . substr( $path_base, 0, rindex( $path_base, '/' ) + 1 )
                          . $href;
                    }
                    elsif ( !defined($scheme) && !defined($auth) ) {
                        $href = 'http://' . $auth_base . $href;
                    }    # 絶対ディレクトリの場合
                         # 完成した抽出リンクを、一旦配列に格納
                    push( @arr_extracted, $href );
                }
            }
        }
    );

    $p->parse($content);

    foreach my $str (@arr_extracted) {
        print $str. "\n";
    }
}
