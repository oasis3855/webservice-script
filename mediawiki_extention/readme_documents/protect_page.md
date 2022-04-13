## ![icon](../readme_pics/softdown-ico-MediaWiki.png) (MediaWiki) protect page拡張機能<!-- omit in toc -->

[Home](https://oasis3855.github.io/webpage/) > [Software](https://oasis3855.github.io/webpage/software/index.html) > [Software Download](https://oasis3855.github.io/webpage/software/software-download.html) > [webservice-scripts](../../README.md) > [mediawiki_extention](../README.md) > ***protect_page*** (this page)

<br />
<br />

Last Updated : Jan. 2012 -- ***this is discontinued software 開発終了***

- [ソフトウエアのダウンロード](#ソフトウエアのダウンロード)
- [概要](#概要)
- [動作確認済み](#動作確認済み)
- [インストール方法](#インストール方法)
- [バージョン情報](#バージョン情報)
- [ライセンス](#ライセンス)

<br />
<br />

## ソフトウエアのダウンロード

- ![download icon](../../readme_pics/soft-ico-download-darkmode.gif)   [このGitHubリポジトリを参照する（ソースコード）](../../mediawiki_extention/) 

## 概要

protect page拡張機能（protect page extention）は、MediaWikiで任意のページに未ログオンユーザによる閲覧制限を儲けることができる拡張機能です。 ページに ```<protect></protect>``` というタグを記述することで、そのページに閲覧制限を儲けることが出来ます。

この拡張機能は、```MediaWiki$wgParser```のsetHookメンバ関数を利用して、タグを検出して、フック機能のBeforePageDisplayを利用して、コンテンツの出力前に該当ページの出力を制限します。

閲覧制限したいページに書き込むタグ

```
    <protect></protect> 
```

## 動作確認済み

- MediaWiki 1.18 

## インストール方法

LocalSettings.phpに次の行を追加する。

```php
require_once("$IP/extensions/protect_page.php");
```

## バージョン情報

- Version 1.0 2012/01/21

  -  当初バージョン MediaWiki 1.18対応 

## ライセンス

このスクリプトは [GNU General Public License v3ライセンスで公開する](https://www.gnu.org/licenses/gpl-3.0.html) フリーソフトウエア
