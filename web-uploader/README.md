## Webファイルアップローダー（Linux, BSD Web Service）<br />Web file uploader<!-- omit in toc -->

[Home](https://oasis3855.github.io/webpage/) > [Software](https://oasis3855.github.io/webpage/software/index.html) > [Software Download](https://oasis3855.github.io/webpage/software/software-download.html) > [webservice-scripts](../README.md) > ***web-uploader*** (this page)

<br />
<br />

Last Updated : Jan. 2015

- [ソフトウエアのダウンロード](#ソフトウエアのダウンロード)
- [概要](#概要)
- [動作確認済み](#動作確認済み)
- [インストール方法](#インストール方法)
- [このスクリプトは個人利用想定のセキュリティ確保しかしていません](#このスクリプトは個人利用想定のセキュリティ確保しかしていません)
- [バージョン情報](#バージョン情報)
- [ライセンス](#ライセンス)

<br />
<br />

## ソフトウエアのダウンロード

- ![download icon](../readme_pics/soft-ico-download-darkmode.gif)   [このGitHubリポジトリを参照する（ソースコード）](../web-uploader/) 

## 概要

任意のファイルをWebサーバにアップロードするWeb Serviceスクリプト。アップロードするファイルが画像ファイルの場合は、[fancybox](http://fancybox.net/) (jQuery lightbox) に対応したタグを含めたHTMLタグを結果表示する。

![ホーム画面](readme_pics/webuploader-homescreen.jpg)

ホーム画面

![画像ファイルのHTMLタグ表示（fileinfo画面）](readme_pics/webuploader-fileinfo.jpg)

画像ファイルのHTMLタグ表示（fileinfo画面）

## 動作確認済み

- FreeBSD 11.2 , Perl 5.26  (さくらインターネット 共用サーバ)

## インストール方法

設置するサーバのルールに従って、スクリプトと設定ファイルの属性を設定してください。```config/init.pl```はユーザ環境にしたがって書き換えてください。

```Perl
our $str_webaddr = 'http://localhost';
our $str_basedir = '/var/www';
our @arr_updirs = ('/test/photo-up',
			'/test/data',
			'/test/日本語フォルダ');
our $flag_overwrite = 0;
our $n_target_size = 320;
```

他者がこのスクリプトを使えないように、認証などを行うことをお勧めします。 

## このスクリプトは個人利用想定のセキュリティ確保しかしていません

ここで配布するスクリプトは、個人用として使うことを想定し、不特定多数に公開するレベルのセキュリティ基準を満たしていません。必ず、.htaccess によるディレクトリ自体のアクセス認証を掛けて、本人以外のアクセスが行えないよう設定して下さい。

## バージョン情報

- Version 0.1 (2011/05/05)
- Version 0.1.1 (2012/03/26)
  - fancybox対応
- Version 0.1.2 (2012/06/18)
- Version 0.1.3 (2013/11/10)
  - ディレクトリ選択を記憶
  - INIファイル
- Version 0.1.4 (2015/01/17)
  - 出力HTMLのa target属性 OFFをデフォルト選択に


## ライセンス

このスクリプトは [GNU General Public License v3ライセンスで公開する](https://gpl.mhatta.org/gpl.ja.html) フリーソフトウエア
