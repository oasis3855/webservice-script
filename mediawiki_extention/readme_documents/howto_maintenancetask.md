## ![icon](../readme_pics/softdown-ico-MediaWiki.png) (MediaWiki) sitemap作成やキャッシュ破棄などを行うメンテナンス用スクリプト例<!-- omit in toc -->

[Home](https://oasis3855.github.io/webpage/) > [Software](https://oasis3855.github.io/webpage/software/index.html) > [Software Download](https://oasis3855.github.io/webpage/software/software-download.html) > [webservice-scripts](../../README.md) > [mediawiki_extention](../README.md) > ***howto_maintenancetask*** (this page)

<br />
<br />

Last Updated : Jan. 2012 -- ***this is discontinued document 開発終了***

- [サイトマップ（sitemap.xml）作成スクリプト](#サイトマップsitemapxml作成スクリプト)
- [キャッシュの破棄スクリプト](#キャッシュの破棄スクリプト)
- [管理者でも削除できないページを削除する方法](#管理者でも削除できないページを削除する方法)

<br />
<br />

## サイトマップ（sitemap.xml）作成スクリプト

MediaWikiメンテナンススクリプト[generateSitemap.php](https://www.mediawiki.org/wiki/Manual:GenerateSitemap.php)を使って自動作成する。なお、サイトのURLは『``` http://www.example.com/mw/index.php?title=メインページ ```』のような形式で表されるものとして、自動生成されたsitemap.xmlをsedを使って修正している。


| ~/tool/mediawiki-make-sitemap.sh |
| --- |
```bash
#!/bin/sh

PATH=/bin:/usr/bin:/sbin:/usr/sbin:/usr/local/bin

MEDIAWIKI_DIR=~/www/mw
HTTP_SERVER_URL=http://www.example.com

cd $MEDIAWIKI_DIR
mkdir sitemap-temp
php maintenance/generateSitemap.php --fspath ./sitemap-temp --server $HTTP_SERVER_URL --compress=no
cp ./sitemap-temp/*NS_0-0.xml ./sitemap-temp/sitemap.xml
sed 's/mw\/index.php\//mw\/index.php?title=/' ./sitemap-temp/sitemap.xml > ./sitemap.xml
rm -rf sitemap-temp
```


## キャッシュの破棄スクリプト

ファイルキャッシュ（cacheディレクトリ内のファイル）と、DB内キャッシュ（l10n_cacheテーブル、objectcacheテーブル）内のデータを全て削除する。 システム関連のファイルを改変した場合、これらのキャッシュの破棄が必要。

| ~/tool/mediawiki-clear-cache.sh |
| --- |
```bash
#!/bin/sh

PATH=/bin:/usr/bin:/sbin:/usr/sbin:/usr/local/bin

MEDIAWIKI_DIR=~/www/mw

MYSQL_SERVER=mysql.example.com
MYSQL_USER=tarou_suzuki
MYSQL_PASSWD=qwerty
MYSQL_DB_NAME=mysql-mwdb

rm -rf $MEDIAWIKI_DIR/cache/[0-9a-z]* 
mysql -h $MYSQL_SERVER -u $MYSQL_USER -p$MYSQL_PASSWD $MYSQL_DB_NAME -e "truncate table objectcache;truncate table l10n_cache;"
```


## 管理者でも削除できないページを削除する方法

特殊な記事名（例 「Mw:テストページ」など、名前空間と記事名をつなげる”コロン”を用いたもの）で編集も削除も出来ないようなページを消す場合、MySQLコマンドラインを用いて記事名を変更することで、再び編集可能な状態に出来る。

- [(MediaWiki)バックアップとリストアの方法](howto_backup_restore.md)でバックアップを取る。
- バックアップで取得したdumpBackup.php形式のダンプファイルより、記事名を探して、記事IDを得る 

```xml
 <page>
   <title>Mw:MediaWiki初期設定</title>
   <id>6</id>
   <redirect />
   <revision>
     <id>8</id>
     <timestamp>2009-02-01T07:14:54Z</timestamp>
     <contributor>
       <username>WikiSysop</username>
       <id>1</id>
     </contributor>
     <comment>ページ [[Mw:MediaWiki初期設定]] を [[MediaWiki初期設定]] へ移動: mw:プレフィックス誤用</comment>
      <text xml:space="preserve" bytes="">#REDIRECT [[MediaWiki初期設定]]</text>
    </revision>
 </page>
```

- MySQLデータベースで、その記事IDが正しい物か念のために確認する 

```sql
mysql> select * from page where page_id='6';
+---------+----------------+--------------------------+-------------------+--------------+------------------+-------------+------------------+----------------+-------------+----------+
| page_id | page_namespace | page_title               | page_restrictions | page_counter | page_is_redirect | page_is_new | page_random      | page_touched   | page_latest | page_len |
+---------+----------------+--------------------------+-------------------+--------------+------------------+-------------+------------------+----------------+-------------+----------+
|       6 |              0 | Mw:MediaWiki初期設定 |                   |            0 |                1 |           1 | 0.35501643945463 | 20090201071454 |           8 |       49 |
+---------+----------------+--------------------------+-------------------+--------------+------------------+-------------+------------------+----------------+-------------+----------+
1 row in set (0.01 sec)
```

- 表示したID番号で正しければ、page_titleフィールドを書き換える 

```sql
mysql> update page set page_title='DummyMwTopic' where page_id='6';
Query OK, 1 row affected (0.01 sec)
Rows matched: 1  Changed: 1  Warnings: 0
```
