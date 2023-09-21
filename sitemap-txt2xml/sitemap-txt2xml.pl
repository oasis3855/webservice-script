#!/usr/bin/perl
# URLリストから、sitemapファイルを作成
use strict;
use warnings;

# sitemap.txt : findコマンドで出力したディレクトリ一覧等
#       例 find /var/www -name '*.html' -print > sitemap.txt
my $strInFileName = 'sitemap_temp.txt';
# sitemap.xml : XML形式のsitemapファイル（出力）
my $strOutFileName = 'sitemap.xml';
# ファイルから一時的にデータを読み込む配列
my @aryData = [];
# ファイルの属性をstat関数で読み込むための配列
my @filestat = [];
# sitemap.txt の文字列置換前
my $strTxtFrom = '/var/www';
# sitemap.txt の文字列置換後
my $strTxtTo = 'http://www.example.com';

my $strTmp;
my $strTimestamp;
my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);

# ファイルハンドル
my $hFile;

# sitemap.txt を一気に読み込み、各行（ファイル名）を配列@aryDataに格納する
if(!open($hFile, $strInFileName))
{
    # 入力ファイルが開けない場合
    print "src file ($strInFileName) open error\n";
    exit;
}
@aryData = <$hFile>;
close($hFile);

if(!open($hFile, ">".$strOutFileName))
{
    # 出力ファイルが開けない場合
    print "output file ($strOutFileName) open error\n";
    exit;
}

print $hFile "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
printf $hFile "<!-- sitemap created automatically by script on " .
        "%04d-%02d-%02d %02d:%02d:%02d (JST).  contains %d pages -->\n",
        $year+1900, $mon+1, $mday, $hour, $min, $sec, $#aryData;
print $hFile "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

foreach $strTmp (@aryData) {
    # 行末の改行を除去
    $strTmp =~ s/\n//g;
    # ファイルの更新時刻を得る
    @filestat = stat $strTmp;
    if(@filestat) {
        ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($filestat[9]);
    } else {
        # ファイルが存在しない場合、lastmodは現在日時とする
        ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
    }
    # ファイル名をURLに変換
    $strTmp =~ s/$strTxtFrom/$strTxtTo/g;
#    $strTmp =~ s/\/var\/www/http:\/\/www.example.com/g;
    print $hFile "  <url>\n";
    print $hFile "    <loc>".$strTmp."</loc>\n";
    printf $hFile "    <lastmod>%04d-%02d-%02dT%02d:%02d:%02d+09:00</lastmod>\n",
        $year+1900, $mon+1, $mday, $hour, $min, $sec;
    print $hFile "    <changefreq>daily</changefreq>\n";
    print $hFile "  </url>\n";
}

print $hFile "</urlset>\n";

close($hFile);

exit(0);
