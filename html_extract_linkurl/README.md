## HTMLからa hrefと画像 img src リンクURLを抽出するPerlスクリプト<br />Extract a href/img src Link URL from HTML file (Perl Script) <!-- omit in toc -->

[Home](https://oasis3855.github.io/webpage/) > [Software](https://oasis3855.github.io/webpage/software/index.html) > [Software Download](https://oasis3855.github.io/webpage/software/software-download.html) > [webservice-scripts](../README.md) > ***html_extract_linkurl*** (this page)

<br />
<br />

Last Updated : Oct. 2023

- [ソフトウエアのダウンロード](#ソフトウエアのダウンロード)
- [概要](#概要)
  - [コマンドライン引数](#コマンドライン引数)
- [動作イメージ](#動作イメージ)
- [バージョンアップ履歴](#バージョンアップ履歴)
- [ライセンス](#ライセンス)


## ソフトウエアのダウンロード

- ![download icon](../readme_pics/soft-ico-download-darkmode.gif)   [このGitHubリポジトリを参照する（ソースコード）](../html_extract_linkurl/)

<br />
<br />

## 概要

指定したWebページ内のリンク（a href="..."）や画像（img src="..."）のurlを、指定した条件のもとに抽出するスクリプト。

データや画像を連続（一括）ダウンロードするときの手助けとなりますが、webサーバに対して短い時間に多数のアクセスを行うことにもなりますので、利用方法には注意が必要です。 

<br />
<br />

### コマンドライン引数

```bash
extract-html-alink.pl ([a|img]) [url_list_file.txt|url] ([domain|+|-] [path])
```

引数の説明

- a : a link タグより抽出（指定しない場合のデフォルト値）
- img : img タグより抽出
- url_list_file.txt : urlを列挙したテキストファイル
- url : urlを直接指定（例 http://example.com/path/target.html
- domain : 指定したドメイン名に一致するもののみ 
    - \+ の場合はurlのドメイン名と一致 
    - \- の場合は全てに一致 
- path : パス名に一致するもののみ 

<br />
<br />

## 動作イメージ

NASAが公開している天文画像を一括ダウンロードする例。

terminalで実行
```bash
extract-html-alink.pl a http://hubblesite.org/gallery/album/nebula > step-1.txt
```

得られたstep-1.txtの不必要な行を削除し、取得したいページのみ残す

step-1.txt 編集後
```
http://hubblesite.org/gallery/album/nebula/pr2006001a/
http://hubblesite.org/gallery/album/nebula/pr2004032d/
http://hubblesite.org/gallery/album/nebula/pr1995044a/
http://hubblesite.org/gallery/album/nebula/pr2013012a/
```

terminalで実行
```bash
extract-html-alink.pl a step-1.txt - large > step-2.txt
```

得られたstep-2.txtの不必要な行を削除し、取得したいページのみ残す

step-2.txt 編集後
```
http://hubblesite.org/newscenter/archive/releases/2006/01/image/a/format/large_web/
http://hubblesite.org/newscenter/archive/releases/2006/01/image/a/format/xlarge_web/
http://hubblesite.org/gallery/album/nebula/pr2004032d/large_web/
http://hubblesite.org/gallery/album/nebula/pr1995044a/large_web/
http://hubblesite.org/gallery/album/nebula/pr2013012a/large_web/
http://hubblesite.org/gallery/album/nebula/pr2013012a/xlarge_web/
```

terminalで実行
```
extract-html-alink.pl img step-2.txt imgsrc.hubblesite.org large > step-3.txt
```

最終的に得られた step-3.txt
```
http://imgsrc.hubblesite.org/hu/db/images/hs-2006-01-a-large_web.jpg
http://imgsrc.hubblesite.org/hu/db/images/hs-2006-01-a-xlarge_web.jpg
http://imgsrc.hubblesite.org/hu/db/images/hs-2004-32-d-large_web.jpg
http://imgsrc.hubblesite.org/hu/db/images/hs-1995-44-a-large_web.jpg
http://imgsrc.hubblesite.org/hu/db/images/hs-2013-12-a-large_web.jpg
http://imgsrc.hubblesite.org/hu/db/images/hs-2013-12-a-xlarge_web.jpg
```

出力されたファイルをwgetコマンドで一括ダウンロードする

terminalで実行
```bash
cat step-3.txt | xargs wget
```

<br />
<br />

## バージョンアップ履歴

- Version 0.1 (2011/02/13)
    - 当初 
- Version 0.2 (2014/01/03)
    - url/urlリストファイル, img/a対象, フィルタ 
- Version 0.3 (2023/10/10)
    - https 対応

<br />
<br />

## ライセンス

このスクリプトは [GNU General Public License v3ライセンスで公開する](https://gpl.mhatta.org/gpl.ja.html) フリーソフトウエア





