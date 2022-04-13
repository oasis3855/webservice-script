## ![icon](../readme_pics/softdown-ico-MediaWiki.png) (MediaWiki) previouspage restrict拡張機能<!-- omit in toc -->

[Home](https://oasis3855.github.io/webpage/) > [Software](https://oasis3855.github.io/webpage/software/index.html) > [Software Download](https://oasis3855.github.io/webpage/software/software-download.html) > [webservice-scripts](../../README.md) > [mediawiki_extention](../README.md) > ***previouspage_restrict*** (this page)

<br />
<br />

Last Updated : Jan. 2012 -- ***this is discontinued software 開発終了***

- [ソフトウエアのダウンロード](#ソフトウエアのダウンロード)
- [概要](#概要)
- [動作確認済み](#動作確認済み)
- [インストール方法](#インストール方法)
  - [specialpage restrict拡張機能との同時利用](#specialpage-restrict拡張機能との同時利用)
- [プログラムの解説](#プログラムの解説)
  - [フック](#フック)
  - [ログオン判定](#ログオン判定)
  - [履歴ページ判定](#履歴ページ判定)
  - [制限ページの場合、エラーメッセージの表示](#制限ページの場合エラーメッセージの表示)
- [バージョン情報](#バージョン情報)
- [ライセンス](#ライセンス)

<br />
<br />

## ソフトウエアのダウンロード

- ![download icon](../../readme_pics/soft-ico-download-darkmode.gif)   [このGitHubリポジトリを参照する（ソースコード）](../../mediawiki_extention/) 

## 概要

previouspage restrict拡張機能（previouspage restrict extention）は、MediaWikiでページの履歴等を未ログオンユーザが閲覧することを阻止する拡張機能です。 たとえば、記事で「履歴」や「この版への固定リンク」を実行しても、未ログオンユーザは閲覧できなくなります。

この拡張機能は、MediaWikiフック機能のBeforePageDisplayを利用して、コンテンツの出力前にSpecial（特別）ページかどうか判別して、エラーページを出力しています。

閲覧阻止できるページ

- 履歴一覧（action=history）
- 特定履歴版の記事表示（oldid=nnn）
- 編集画面（action=edit） 

## 動作確認済み

- MediaWiki 1.18 

## インストール方法

LocalSettings.phpに次の行を追加する。

```PHP
require_once("$IP/extensions/previouspage_restrict.php");
```

### specialpage restrict拡張機能との同時利用

[specialpage restrict拡張機能](readme_documents/specialpage_restrict.md)と同時利用する場合、SVNに登録されている（2つの拡張機能を合体したスクリプト）prevpage_specialpage_restrict.phpを利用すると、少しだけオーバーヘッドを減らすことが出来るかもしれません。 

## プログラムの解説

### フック

```PHP
$wgHooks['BeforePageDisplay'][] = array($obj_specialpage_restrict, 'wfMainHookFunction');
```

ページが出力される寸前に、このフック機能によりwfMainHookFunction関数が呼び出される。詳細はmw:Manual:Hooks/BeforePageDisplayを参照。

### ログオン判定

```PHP
if($wgUser->isLoggedIn()) {
    return true;
}
```

includes/User.phpによれば、『```$this->getID() != 0```』の判定が返るだけである。

### 履歴ページ判定

```PHP
if($wgRequest->getVal('oldid') == NULL) {
   if($wgRequest->getVal('action') == NULL) {
        return true;    # 履歴モードで無い場合
   }
    else if($wgRequest->getVal('action') != 'history' && $wgRequest->getVal('action') != 'edit'){
        return true;    # 履歴一覧または編集画面で無い場合
   }
}
```

「この版の固定リンク」の版数が```$wgRequest->data['oldid']```に代入されている。また、```$wgRequest->data['action']```にhistoryがセットされているときは履歴参照ページである。これらを検出する。 MediaWiki1.18では```$wgRequest->dataがprotected```メンバ変数に変更されたため、```$wgRequest->getVal()```関数を用いて値を取り出している。


### 制限ページの場合、エラーメッセージの表示

```PHP
$wgOut->showErrorPage( 'errorpagetitle', 'notloggedin' );
```

メッセージのH1セクションと、メッセージ本文はlanguages/messages/MessagesXX.phpに規定されているメッセージリストの中からそれらしきものを選択した。任意のメッセージを表示させるように、新たなメッセージマップを作成することも出来る。 

## バージョン情報

- Version 1.0 2009/02/09

  - 当初バージョン MediaWiki 1.13対応 

- Version 1.2 2012/01/20

  - MediaWiki 1.18対応 
  - 未ログオンユーザの編集ページ閲覧制限を追加 

## ライセンス

このスクリプトは [GNU General Public License v3ライセンスで公開する](https://www.gnu.org/licenses/gpl-3.0.html) フリーソフトウエア
