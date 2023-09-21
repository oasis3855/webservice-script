## 静的サイトのXML形式サイトマップファイル自動生成スクリプト（Linux, BSD）<br />sitemap build script<!-- omit in toc -->

[Home](https://oasis3855.github.io/webpage/) > [Software](https://oasis3855.github.io/webpage/software/index.html) > [Software Download](https://oasis3855.github.io/webpage/software/software-download.html) > [webservice-scripts](../README.md) > ***sitemap-txt2xml*** (this page)

<br />
<br />

Last Updated : Sep. 2023

- [ソフトウエアのダウンロード](#ソフトウエアのダウンロード)
- [概要](#概要)
  - [STEP-1 : 対象ファイルのフルパスを一時テキストファイルに保存](#step-1--対象ファイルのフルパスを一時テキストファイルに保存)
  - [STEP-2 : 一時テキストファイルを読み込み、sitemap.xmlを作成](#step-2--一時テキストファイルを読み込みsitemapxmlを作成)
  - [STEP-1で作成する一時テキストファイルの例](#step-1で作成する一時テキストファイルの例)
  - [STEP-2で作成されるsitemap.xmlファイルの例](#step-2で作成されるsitemapxmlファイルの例)
  - [テキスト形式サイトマップファイルの作成はsedコマンドで可能](#テキスト形式サイトマップファイルの作成はsedコマンドで可能)
- [robots.txt を作成する](#robotstxt-を作成する)
- [参考資料](#参考資料)
- [制限事項](#制限事項)
- [バージョンアップ履歴](#バージョンアップ履歴)
- [ライセンス](#ライセンス)


<br />
<br />

## ソフトウエアのダウンロード

- ![download icon](../readme_pics/soft-ico-download-darkmode.gif)   [このGitHubリポジトリを参照する（ソースコード）](../sitemap-txt2xml/) 

<br />
<br />

## 概要

UNIXまたはLinuxのwebサーバで、cronなどを用いて自動的にXML形式サイトマップファイル（sitemap.xml）を自動生成するためのスクリプト

次の2ステップでsitemap.xmlファイルを作成する

<br />

### STEP-1 : 対象ファイルのフルパスを一時テキストファイルに保存

find コマンドを用いて、対象ファイル一覧を作成する。最も基本的な構文は次のようなものになる

```BASH
find /var/www/* -name '*.htm*' -print > sitemap_temp.txt
```

登録するファイルの拡張子を複数条件（ .htm* および .shtml） 、特定のディレクトリを除外（ cgi-bin と data）する場合の例として

```BASH
find /var/www/* -not \( -path '*/cgi-bin/*' -or -path '*/data/*' \) \( -name "*.htm*" -or -name "*.shtml" \) -print > sitemap_temp.txt
```

複数回の find コマンドの出力を連結する場合は

```BASH
find /var/www/* -name '*.htm*' -print > sitemap_temp.txt
find /var/www/* -name '*.shtml' -print >> sitemap_temp.txt
find /var/www/* -name '*.php' -print >> sitemap_temp.txt
```
<br />

### STEP-2 : 一時テキストファイルを読み込み、sitemap.xmlを作成

このページで配布しているPerlスクリプト（ [sitemap-txt2xml.pl](./sitemap-txt2xml.pl) ） を実行して、ローカルディスク上のフルパス名をweb urlに変換し、xml形式で保存する

<br />
<br />

### STEP-1で作成する一時テキストファイルの例

```
/var/www/index.shtml
/var/www/about/index.html
/var/www/photo/index.html
/var/www/photo/page-1.html

… 以下省略
```

<br />

### STEP-2で作成されるsitemap.xmlファイルの例

```XML
<?xml version="1.0" encoding="UTF-8"?>
<!-- sitemap created automatically by script on 2023-09-21 16:14:17 (JST).  contains 50 pages -->
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>http://www.example.com/index.shtml</loc>
    <lastmod>2023-05-01T09:10:00+09:00</lastmod>
    <changefreq>daily</changefreq>
  </url>
  <url>
    <loc>http://www.example.com/about/index.html</loc>
    <lastmod>2021-07-15T08:16:24+09:00</lastmod>
    <changefreq>daily</changefreq>
  </url>

… 以下省略
```

<br />

### テキスト形式サイトマップファイルの作成はsedコマンドで可能

```
sed  "s/\/var\/www/http:\/\/www.example.com/g" sitemap_temp.txt > sitemap.txt
```

<br />
<br />

## robots.txt を作成する

サイトのルートディレクトリに置いたrobots.txtに、そのサイト内の全てのサイトマップファイルを指定する。 

```
User-Agent: *
Disallow: /cgi-bin/
Allow: /
Sitemap: http://example.com/sitemap-1.xml
Sitemap: http://example.com/sitemap-2.xml
```

<br />
<br />

## 参考資料

sitemap XML ファイルの書式は、sitemaps.org の 「[サイトマップの XML 形式](https://sitemaps.org/ja/protocol.html)」に詳しく説明されている

Google検索セントラルには、「[サイトマップの作成と送信](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap?hl=ja)」という解説記事も公開されている

<br />
<br />

## 制限事項

- エスケープが必要な文字が含まれるパス名（ASCII文字以外、スペースなどの記号）は考慮されていません

<br />
<br />

## バージョンアップ履歴

- Version 1.0 (2010/04/07)
- Version 1.1 (2023/09/21)

<br />
<br />

## ライセンス

このスクリプトは [GNU General Public License v3ライセンスで公開する](https://gpl.mhatta.org/gpl.ja.html) フリーソフトウエア

