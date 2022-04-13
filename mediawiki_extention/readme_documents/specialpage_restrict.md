## ![icon](../readme_pics/softdown-ico-MediaWiki.png) (MediaWiki) specialpage restrict拡張機能<!-- omit in toc -->

[Home](https://oasis3855.github.io/webpage/) > [Software](https://oasis3855.github.io/webpage/software/index.html) > [Software Download](https://oasis3855.github.io/webpage/software/software-download.html) > [webservice-scripts](../../README.md) > [mediawiki_extention](../README.md) > ***specialpage_restrict*** (this page)

<br />
<br />

Last Updated : Jan. 2012 -- ***this is discontinued software 開発終了***

- [ソフトウエアのダウンロード](#ソフトウエアのダウンロード)
- [概要](#概要)
- [動作確認済み](#動作確認済み)
- [インストール方法](#インストール方法)
  - [previouspage restrict拡張機能との同時利用](#previouspage-restrict拡張機能との同時利用)
- [プログラムの解説](#プログラムの解説)
  - [フック](#フック)
  - [ログオン判定](#ログオン判定)
  - [特別ページ判定](#特別ページ判定)
  - [ログオン・ログアウト等は許可する](#ログオンログアウト等は許可する)
  - [制限ページの場合、エラーメッセージの表示](#制限ページの場合エラーメッセージの表示)
- [バージョン情報](#バージョン情報)
- [ライセンス](#ライセンス)

<br />
<br />

## ソフトウエアのダウンロード

- ![download icon](../../readme_pics/soft-ico-download-darkmode.gif)   [このGitHubリポジトリを参照する（ソースコード）](../../mediawiki_extention/) 

## 概要

specialpage restrict拡張機能（specialpage restrict extention）は、MediaWikiで『特別ページ』を未ログオンユーザが閲覧することを阻止する拡張機能です。 たとえば、\[\[特別:Search\]\]、\[\[特別:RecentChanges\]\]、\[\[特別:Upload\]\]、\[\[特別:Contributions\]\]、\[\[特別:Version\]\]などのページを未ログオンユーザが閲覧できなくなります。

この拡張機能は、MediaWikiフック機能のBeforePageDisplayを利用して、コンテンツの出力前にSpecial（特別）ページかどうか判別して、エラーページを出力しています。

閲覧阻止できるページ

- 名前空間が「特別（Special）」のページ。例：\[\[特別:Search\]\]、\[\[特別:RecentChanges\]\]
- 名前空間が「ユーザー（User）」のページ。例：\[\[利用者:user_name\]\]

## 動作確認済み

- MediaWiki 1.18 

## インストール方法

LocalSettings.phpに次の行を追加する。

```PHP
require_once("$IP/extensions/specialpage_restrict.php");
```

### previouspage restrict拡張機能との同時利用

[previouspage restrict拡張機能](readme_documents/previouspage_restrict.md)と同時利用する場合、SVNに登録されている（2つの拡張機能を合体したスクリプト）prevpage_specialpage_restrict.phpを利用すると、少しだけオーバーヘッドを減らすことが出来るかもしれません。 


## プログラムの解説

### フック

```PHP
$wgHooks['BeforePageDisplay'][] = array($obj_specialpage_restrict, 'wfMainHookFunction');
```

ページが出力される寸前に、このフック機能によりwfMainHookFunction関数が呼び出される。詳細は[mw:Manual:Hooks/BeforePageDisplay](https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay)を参照。

### ログオン判定

```PHP
if($wgUser->isLoggedIn()) {
	# isLoggedin() is defined includes/User.php
	return true;
}
```

includes/User.phpによれば、『```$this->getID() != 0```』の判定が返るだけである。 

### 特別ページ判定

```PHP
if($wgTitle->mNamespace != NS_SPECIAL && $wgTitle->mNamespace != NS_USER) {
    # NS_SPECIAL, NS_USER is defined at includes/Defines.php
    return true;
}
```

ノート・ページは、別の名前空間となり、NS_TALKやNS_USER_TALKなどを判定すればよい。 

### ログオン・ログアウト等は許可する

特別ページのうち、「ログオン」・「ログアウト」・「最近の更新（rss）」は表示を許可する。

```PHP
$arrAllowTitle = array(SpecialPage::getTitleFor( 'Userlogin' ), SpecialPage::getTitleFor( 'Userlogout' ), SpecialPage::getTitleFor( 'RecentChanges' ));  # array of AllowedTitles

# check Allowed Titles (許可されたページかどうか判別する)
foreach($arrAllowTitle as $sAllowTitle) {
    if($wgTitle->mPrefixedText == $sAllowTitle) {
         # Allowed Title
         $bAllowed = true;
    }
}
```

### 制限ページの場合、エラーメッセージの表示

```PHP
$wgOut->showErrorPage( 'errorpagetitle', 'notloggedin' );
```

メッセージのH1セクションと、メッセージ本文はlanguages/messages/MessagesXX.phpに規定されているメッセージリストの中からそれらしきものを選択した。任意のメッセージを表示させるように、新たなメッセージマップを作成することも出来る。 

## バージョン情報

- Version 1.0 2009/02/09

  -  当初バージョン MediaWiki 1.13対応 

- Version 1.0.1 2009/02/24

  -  MediaWiki 1.14対応 

- Version 1.2 2012/01/20

  -  MediaWiki 1.18対応 

- Version 1.3 2012/01/29

  -  NS_MAIN,NS_FILE,NS_CATEGORY以外は制限するよう拒否レベルを上げる 


## ライセンス

このスクリプトは [GNU General Public License v3ライセンスで公開する](https://www.gnu.org/licenses/gpl-3.0.html) フリーソフトウエア
