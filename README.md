## Webサーバサイド スクリプト（Linux, BSD）<br />Web server side scripts<!-- omit in toc -->

[Home](https://oasis3855.github.io/webpage/) > [Software](https://oasis3855.github.io/webpage/software/index.html) > [Software Download](https://oasis3855.github.io/webpage/software/software-download.html) > ***webservice-scripts*** (this page)

<br />
<br />

- [jQuery lightbox "FancyBox"にカスタムボタン追加](#jquery-lightbox-fancyboxにカスタムボタン追加)
- [HTML::TreeBuilderによるHTML整形Perlスクリプト （XHTML対応）（Web Service）](#htmltreebuilderによるhtml整形perlスクリプト-xhtml対応web-service)
- [MediaWiki拡張機能](#mediawiki拡張機能)
  - [MediaWiki運用ノウハウ](#mediawiki運用ノウハウ)
- [MovableTypeでのXML形式サイトマップファイル自動生成スクリプト](#movabletypeでのxml形式サイトマップファイル自動生成スクリプト)
- [静的サイトのXML形式サイトマップファイル自動生成スクリプト](#静的サイトのxml形式サイトマップファイル自動生成スクリプト)
- [Webファイルブラウザ（Web Service）](#webファイルブラウザweb-service)
- [証明写真作成PHPスクリプト（Web Service）](#証明写真作成phpスクリプトweb-service)
- [シンプルなメール送受信PHPスクリプト（Web Service）](#シンプルなメール送受信phpスクリプトweb-service)
- [mp3ストリーミングのプレイリスト(PLS)管理Perlスクリプト（Web Service）](#mp3ストリーミングのプレイリストpls管理perlスクリプトweb-service)
- [RSS巡回表示（Web Service）](#rss巡回表示web-service)
- [Webファイルアップローダー（Web Service）](#webファイルアップローダーweb-service)
- [WebSVN Administrator - Subversionリポジトリ管理（Web Service）](#websvn-administrator---subversionリポジトリ管理web-service)


<br />
<br />

## jQuery lightbox "FancyBox"にカスタムボタン追加

画像表示のjQueryライブラリ[FancyBox](https://fancyapps.com/fancybox/)に、フルスクリーン表示ボタン、スライドショー開始・終了のカスタムボタンを追加する機能拡張を行った

[配布ディレクトリ fancybox_custombutton](fancybox_custombutton/README.md) (2016/02/13)

<br />
<br />

## HTML::TreeBuilderによるHTML整形Perlスクリプト （XHTML対応）（Web Service）

HTMLファイルのソースコードのインデントを整え、HTML/XHTML規格に準拠していない属性値を修正して画面表示します。ユーザは任意のローカルファイルをアップロードして整形対象にすることが出来ます。

[配布ディレクトリ html-treebuilder](html-treebuilder/README.md) (2022/03/24)

<br />
<br />

## MediaWiki拡張機能

MediaWikiで個人Webページを運用するときに、訪問者に書き込みやシステム運用関連ページなどの不必要な機能を見せない・使わせないために必要になる拡張機能や設定。

- previouspage restrict拡張機能
- specialpage restrict拡張機能
- protect page拡張機能

[配布ディレクトリ mediawiki_extention](mediawiki_extention/README.md) (2012/01/20)

<br />
<br />

### MediaWiki運用ノウハウ

- バックアップとリストアの方法
- sitemap作成やキャッシュ破棄などを行うメンテナンス用スクリプト例
- InterWikiのキーワードを追加する方法
- セットアップ時のシステム設定調整
- 利用頻度の多いグローバル変数

[ドキュメント格納場所 mediawiki_extention/readme_documents](mediawiki_extention/readme_documents/README.md)

<br />
<br />

## MovableTypeでのXML形式サイトマップファイル自動生成スクリプト

Movable Typeで記事追加時に、自動的にサイトマップファイルを作成させるためのインデックス・テンプレート

[配布ディレクトリ sitemap-movabletype](sitemap-movabletype/README.md) (2010/04/07)

<br />
<br />

## 静的サイトのXML形式サイトマップファイル自動生成スクリプト

UNIXまたはLinuxのwebサーバで、cronなどを用いて自動的にXML形式サイトマップファイル（sitemap.xml）を自動生成するためのスクリプト

[配布ディレクトリ sitemap-txt2xml](sitemap-txt2xml/README.md) (2023/09/21)

<br />
<br />

## Webファイルブラウザ（Web Service）

Webサーバ内のディレクトリやファイルの一覧を表示したり、HTMLファイル記述時用に画像やデータファイルなどへのリンク（HTMLソースコード）を自動作成することができます。

[配布ディレクトリ web-file-browser](web-file-browser/README.md) (2013/12/05)

<br />
<br />

## 証明写真作成PHPスクリプト（Web Service）

コンビニ・家電量販店・スーパー等に設置されている「写真プリント機」や「マルチコピー機」で、運転免許やパスポートなどに使う証明写真を出力させるための画像ファイルを作成するためのスクリプトです。

[配布ディレクトリ web-idpassport-photo](web-idpassport-photo/README.md) (2022/03/31)

<br />
<br />

## シンプルなメール送受信PHPスクリプト（Web Service）

PHPスクリプトでメールを送受信するサンプルWebサービス。imapまたはsmtpでのメール送信、imapまたはpop3でのメール受信が組み込まれている。

[配布ディレクトリ web-mail-sendview](web-mail-sendview/README.md) (2021/02/27)

<br />
<br />

## mp3ストリーミングのプレイリスト(PLS)管理Perlスクリプト（Web Service）

ストリーミング ミュージック再生アプリからバックアップ取得したプレイリスト（PLS）ファイルを、web上で管理するためのスクリプト。web上でストリーミングサイトを新規追加・編集・再生することも出来る。

[配布ディレクトリ web-playlist-backup](web-playlist-backup/README.md) (2016/01/03)

<br />
<br />

## RSS巡回表示（Web Service）

シンプルなRSSブラウザ。表示するRSSサイトはWeb上で編集可能。

[配布ディレクトリ websvn-admin](web-rss-receive/README.md) (2014/09/16)

<br />
<br />

## Webファイルアップローダー（Web Service）

任意のファイルをWebサーバにアップロードするWeb Serviceスクリプト。アップロードするファイルが画像ファイルの場合は、[fancybox](http://fancybox.net/) (jQuery lightbox) に対応したタグを含めたHTMLタグを結果表示する。

[配布ディレクトリ web-uploader](web-uploader/README.md) (2015/01/17)

<br />
<br />

## WebSVN Administrator - Subversionリポジトリ管理（Web Service）

Webサーバ上で稼動する、ローカル（Webサーバ内の）Subversionのリポジトリ管理。sshで接続してsvnadminコマンドを用いることなく、Web経由でリポジトリの作成や削除などの操作が可能です。

[配布ディレクトリ websvn-admin](websvn-admin/README.md) (2011/01/22)

<br />
<br />

