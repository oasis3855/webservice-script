## ![icon](../readme_pics/softdown-ico-MediaWiki.png) (MediaWiki) 利用頻度の多いグローバル変数<!-- omit in toc -->

[Home](https://oasis3855.github.io/webpage/) > [Software](https://oasis3855.github.io/webpage/software/index.html) > [Software Download](https://oasis3855.github.io/webpage/software/software-download.html) > [webservice-scripts](../../README.md) > [mediawiki_extention](../README.md) > ***howto_global_variables*** (this page)

<br />
<br />

Last Updated : Jan. 2012 -- ***this is discontinued document 開発終了***

<br />
<br />

## 拡張機能で用いるグローバル・クラス

### class User

$IP/includes/User.phpで定義されている。 

- ```$wgUser->getName()``` : ログインしているときはユーザ名、未ログインのときはIPアドレス
- ```$wgUser->isAllowed('権限')``` : 現在のユーザに権限があるとき true。権限は```$wgUser->mRights```に規定されている値で、read, edit, move, createpage, upload 等
- ```$wgUser->isAnon()``` : isLoggedIn()の反転結果。内部でisLoggedIn()を呼び出している
- ```$wgUser->isLoggedIn()``` : ログインしているとき true、未ログインのとき false
- ```$wgUser->mName``` : 基本的にはログインしているときはユーザ名、未ログインのときはIPアドレス。この変数ではなく、getName()を使うこと
- ```$wgUser->mOptions['skin']``` : **適用されているスキン名（小文字）** 

 class Title
###
$IP/includes/Title.phpで定義されている。 

-    ```$wgTitle->escapeFullURL()``` : $wgTitle->getFullURL()を画面表示するためにhtmlspecialcharsを通した結果
-    ```$wgTitle->escapeLocalURL()``` : $wgTitle->getLocalURL()を画面表示するためにhtmlspecialcharsを通した結果
-    ```$wgTitle->getFullURL()``` : この記事へのURL（URLエンコードされた、```http://～``` のフルURL）
-    ```$wgTitle->getLocalURL()``` : この記事へのURL（URLエンコードされた、ドメインを除いたフルURL）
-    ```$wgTitle->getPrefixedURL()``` : この記事へのURL（$wgTitle->mPrefixedTextをURLエンコードしたもの）
-    ```$wgTitle->mDbkeyform``` : 記事名（空白文字はアンダースコアに変換されたもの）
-    ```$wgTitle->mNamespace``` : ネームスペースに対応した数値。例：特別ページは NS_SPECIAL、ユーザページはNS_USER等。数値はincludes/Defines.phpで定義されている
-    ```$wgTitle->mPrefixedText``` : 記事名にネームスペースを付加したもの（例 : 特別:Contributions）
-    ```$wgTitle->mTextform``` : 記事名
-    ```$wgTitle->mUrlform``` : 記事名をURLエンコードした文字列 

### class Request

$IP/includes/WebRequest.phpで定義されている。 

-    ```$wgRequest->data['action']``` : 記事を操作しているときにアクション名が代入。例えば、編集時には'edit'、履歴表示時には'history'などがセットされる
-    ```$wgRequest->data['oldid']``` : 記事の過去のバージョンを閲覧しているときに管理番号が代入
-    ```$wgRequest->data['title']``` : 記事名 


### class Article

$IP/includes/Article.phpで定義されている。特別ページやユーザページなど、一部の名前空間ではセットされていない変数もある。 

-    ```$wgArticle->getCount()``` : 記事のアクセス数を返す
-    ```$wgArticle->getUserText()``` : 最終更新者を返す
-    ```$wgArticle->getTimestamp()``` : 記事の最終変更日を返す
-    ```$wgArticle->isCurrent()``` : 記事が最新版（履歴では無い）の場合trueが返される
-    ```$wgArticle->isRedirect()``` : リダイレクトが行われるページの場合 1が返される
-    ```$wgArticle->mContent``` : 記事本文（Wiki構文のまま）
-    ```$wgArticle->mCounter``` : 記事のアクセス数
-    ```$wgArticle->mIsRedirect``` : リダイレクトが行われるページの場合 1がセットされている
-    ```$wgArticle->mTimestamp``` : 現在表示中の記事（版）の変更日
-    ```$wgArticle->mUserText``` : 現在表示中の記事（版）の更新者 

### class Parser

$IP/includes/parser/Parser.phpで定義されている。 

-    ```$wgParser->mOutput->mText``` : 出力される記事
-    ```$wgParser->parse(...)``` : WikiテキストをHTMLに変換する。

例
```php
$parserOutput = $wgParser->parse('[[Special:Version]]', $wgTitle, $wgOut->parserOptions(), false );
echo $parserOutput->getText();
```


### class OutputPage

$IP/includes/OutputPage.phpで定義されている。 

-    ```$wgOut->addHTML('string')``` : 表示しようとしている記事本文の後ろにHTML文字列を追加。内部でmBodytextに文字を結合している
-    ```$wgOut->addWikiText('string')``` : 表示しようとしている記事本文の後ろにWiki構文の文字列を追加
-    ```$wgOut->clearHTML()``` : 表示しようとしている記事本文を削除。内部でmBodytextに文字列をコピーしている
-    ```$wgOut->getHTMLTitle()``` : HTMLのタイトル文字列を返す。内部でmHTMLtitleを返す
-    ```$wgOut->getPageTitle()``` : 記事名を返す。内部でmPageTitleを返す
-    ```$wgOut->mBodytext``` : 表示しようとしている記事本文
-    ```$wgOut->mHTMLTitle``` : HTMLのタイトル文字列
-    ```$wgOut->mPageTitle``` : 記事名
-    ```$wgOut->mRevisionId``` : 改定番号（記事履歴を管理するための管理番号）
-    ```$wgOut->mSubtitle``` : サブタイトル文字列
-    ```$wgOut->parse('string')``` : Wikiテキストを処理して、HTML文字列を返す
-    ```$wgOut->setSubtitle('string')``` : サブタイトルを設定する。内部でmSubtitleに文字列をコピーしている
-    ```$wgOut->setPageTitle('string')``` : 記事名を設定する。内部でmPageTitleに文字列をコピーしている 

### その他の変数

-    ```$action``` : 'view', 'edit', 'submit' などの現在の閲覧モードを示す文字列 

