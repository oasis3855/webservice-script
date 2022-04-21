## ![icon](../readme_pics/softdown-ico-MediaWiki.png) (MediaWiki) InterWikiのキーワードを追加する<!-- omit in toc -->

[Home](https://oasis3855.github.io/webpage/) > [Software](https://oasis3855.github.io/webpage/software/index.html) > [Software Download](https://oasis3855.github.io/webpage/software/software-download.html) > [webservice-scripts](../../README.md) > [mediawiki_extention](../README.md) > ***howto_add_interwiki*** (this page)

<br />
<br />

Last Updated : Jan. 2012 -- ***this is discontinued document 開発終了***

- [他サーバのMediaWikiデータを移行する場合](#他サーバのmediawikiデータを移行する場合)
- [LocalSettings.phpの設定](#localsettingsphpの設定)
  - [未ログオン・ユーザの編集禁止（設定文を追加）](#未ログオンユーザの編集禁止設定文を追加)
  - [標準のタイムゾーンの設定（設定文を追加）](#標準のタイムゾーンの設定設定文を追加)
  - [ファイルアップロードの許可（設定文を編集）](#ファイルアップロードの許可設定文を編集)
  - [外部サイトの画像ファイル表示を許可する](#外部サイトの画像ファイル表示を許可する)
  - [\<img\>画像タグの利用を許可する](#img画像タグの利用を許可する)
  - [ページ名を任意の文字列に変更できるようにする](#ページ名を任意の文字列に変更できるようにする)
  - [デフォルトのスキンファイルの指定](#デフォルトのスキンファイルの指定)
  - [ファイルキャッシュの有効化](#ファイルキャッシュの有効化)
  - [キャッシュの有効期限を最大LocalSettings.phpの更新時刻とする](#キャッシュの有効期限を最大localsettingsphpの更新時刻とする)
- [システムディレクトリのアクセス制限](#システムディレクトリのアクセス制限)
- [InterWikiキーワードの設定](#interwikiキーワードの設定)
- [RSSフィード出力の記事リンクを履歴ではなく記事自体にする](#rssフィード出力の記事リンクを履歴ではなく記事自体にする)

<br />
<br />

## 他サーバのMediaWikiデータを移行する場合

[(MediaWiki)バックアップとリストア](howto_backup_restore.md) のdumpBackup.phpツールを使って、移行元サーバから記事のテキストデータをバックアップし、移行先サーバにリストアすればよい。 

この方法では、画像などの「ファイル空間」のデータは移行されない。MediaWikiのバージョンが同じであればimagesディレクトリを丸ごとコピーすれば良いようだが、そうでない場合はファイルを1個ずつ再登録していく必要がある。 

## LocalSettings.phpの設定

\$IP/includes/DefaultSettings.php の設定をオーバーライドする。

### 未ログオン・ユーザの編集禁止（設定文を追加）

```PHP
## permissions override
$wgGroupPermissions['*'    ]['createaccount']   = false;
#$wgGroupPermissions['*'    ]['read']            = true;
$wgGroupPermissions['*'    ]['edit']            = false;
$wgGroupPermissions['*'    ]['createpage']      = false;
$wgGroupPermissions['*'    ]['createtalk']      = false;
```

詳細は[mw:Manual:\$wgGroupPermissions/ja](https://www.mediawiki.org/wiki/Manual:$wgGroupPermissions/ja) および[mw:Manual:User rights/ja](https://www.mediawiki.org/wiki/Manual:User_rights/ja)参照。

### 標準のタイムゾーンの設定（設定文を追加）

```PHP
## default timezone
$wgLocaltimezone = "Asia/Tokyo";
$wgLocalTZoffset = date("Z") / 60;
```

Googleで検索すると、3600で割るという例も書かれているが、MediaWikiの1.7以降では秒ではなく分単位となっているため、60で割ればよい。[mw:Manual:\$wgLocalTZoffset/ja](https://www.mediawiki.org/wiki/Manual:$wgLocalTZoffset/ja)参照。 

### ファイルアップロードの許可（設定文を編集）

```PHP
## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads       = true;
$wgFileExtensions = array( 'png', 'gif', 'jpg', 'jpeg' );
```

アップロードできるファイルの拡張子は、デフォルトではここに示したようなものになっている。zip,lzh,gz,cabなどの圧縮ファイルをアップロードしたい場合は、ここに追加すればよい。なお、アップロードが禁止されたファイル拡張子の配列 \$wgFileBlacklist や、MIMEタイプの配列 $wgMimeTypeBlacklist についてもDefaultSettings.phpでの指定を確認すること。（基本的に、htmlファイル、php,cgi等のサーバ実行ファイル、exeなどのWindows実行ファイル等が禁止リストに列挙されている）

詳細は[mw:Manual:\$wgEnableUploads/ja](https://www.mediawiki.org/wiki/Manual:$wgEnableUploads/ja)や[mw:Manual:\$wgFileExtensions/ja](https://www.mediawiki.org/wiki/Manual:$wgFileExtensions/ja)などを参照。 

### 外部サイトの画像ファイル表示を許可する

```PHP
$wgAllowExternalImages = true;
```

### \<img\>画像タグの利用を許可する

```PHP
$wgAllowImageTag = true;
```

### ページ名を任意の文字列に変更できるようにする

```PHP
$wgAllowDisplayTitle = true;
$wgRestrictDisplayTitle = false;
```

### デフォルトのスキンファイルの指定

```
$wgDefaultSkin = 'my_skin';
```

スキンの定義ファイルで、\$this-\>skinname = 'My_Skin'; と定義されているところのスキン名を全て小文字にして指定する。詳細は[mw:Manual:\$wgDefaultSkin/ja](https://www.mediawiki.org/wiki/Manual:$wgDefaultSkin/ja)参照。 

### ファイルキャッシュの有効化

```PHP
$wgUseFileCache = true;
$wgFileCacheDirectory = "$IP/cache";
$wgShowIPinHeader = false;
```

この設定のように、同時に非ログオンユーザの「IPアドレス表示」を無効にする必要がある。

### キャッシュの有効期限を最大LocalSettings.phpの更新時刻とする

```PHP
$wgCacheEpoch = max( $wgCacheEpoch, gmdate( 'YmdHis', @filemtime( __FILE__ ) ) );
```

こうすることで、LocalSettings.phpを書き換えた場合に強制的なキャッシュクリアを忘れても自動的にキャッシュがクリアされるようになる 

## システムディレクトリのアクセス制限

bin, cache, docs, extentions, includes, languages, maintenanceなど、外部からアクセスする必要の無いディレクトリのother,groupアクセス権限を削除する。 

## InterWikiキーワードの設定

他のサーバのMediaWikiを移行してくる場合は、InterWikiキーワードのDBをコピーする。

[(MediaWiki)_InterWikiのキーワードを追加する](howto_add_interwiki.md)

## RSSフィード出力の記事リンクを履歴ではなく記事自体にする

RSSフィードの記事リンクは標準で次のような形式になっている

```PHP
<entry>
  <id>http://www.example.com/index.php?title=Topic-Title&diff=562&oldid=174</id>
  <title>Topic-Title</title>
  <link rel="alternate" type="text/html" href="http://www.example.com/index.php?title=Topic-Title&diff=562&oldid=174"/>
  <updated>2012-01-21T11:44:22Z</updated>
</entry>
```

diff=nnnとoldid=nnnを消すため、\$IP/include/ChangesFeed.phpの中の1箇所を修正する。

```PHP
if ( $obj->rc_this_oldid ) {
  $url = $title->getFullURL(
//   'diff=' . $obj->rc_this_oldid .
//   '&oldid=' . $obj->rc_last_oldid
  );
} else {
  $url = $title->getFullURL();
}
```

なお、変更後に結果が反映されるまで、最大24時間掛る。これは、global \$messageMemc;のキャッシュが$expire = 3600 * 24;の期間だけ持続するからだ。一時的にキャッシュを無効にして結果を確認するには、（MediaWiki1.18では）129行目のreturn \$messageMemc->get( $key );を阻止すればよい。 

##「NewPP limit report」の出力抑制

標準設定では本文とカテゴリの間に、次のようなコメントが自動的に挿入されている。

```PHP
<!-- 
NewPP limit report
Preprocessor node count: 2/1000000
Post-expand include size: 6/2097152 bytes
Template argument size: 0/2097152 bytes
Expensive parser function count: 0/100
-->

<!-- Saved in parser cache with key server_:pcache:idhash:1-0!1!0!!ja!2 and timestamp 20090212105643 -->
```

このデバッグ用コメントを消すために、次の２箇所を変更する。

\$IP/includes/parser/Parser.php の一部分をコメントアウト。 

```PHP
$limitReport =
	"NewPP limit report\n" .
	"Preprocessor node count: {$this->mPPNodeCount}/{$this->mOptions->mMaxPPNodeCount}\n" .
	"Post-expand include size: {$this->mIncludeSizes['post-expand']}/$max bytes\n" .
	"Template argument size: {$this->mIncludeSizes['arg']}/$max bytes\n".
	$PFreport;
wfRunHooks( 'ParserLimitReport', array( $this, &$limitReport ) );
# $text .= "\n<!-- \n$limitReport-->\n";
```

\$IP/includes/parser/ParserCache.php の一部分をコメントアウト。

```PHP
$parserOutput->mTimestamp = $article->getTimestamp();
 
# $parserOutput->mText .= "\n<!-- Saved in parser cache with key $key and timestamp $now -->\n";
wfDebug( "Saved in parser cache with key $key and timestamp $now\n" );
```

