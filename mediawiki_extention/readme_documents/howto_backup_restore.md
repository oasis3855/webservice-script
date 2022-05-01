## ![icon](../readme_pics/softdown-ico-MediaWiki.png) (MediaWiki) InterWikiのキーワードを追加する<!-- omit in toc -->

[Home](https://oasis3855.github.io/webpage/) > [Software](https://oasis3855.github.io/webpage/software/index.html) > [Software Download](https://oasis3855.github.io/webpage/software/software-download.html) > [webservice-scripts](../../README.md) > [mediawiki_extention](../README.md) > ***howto_backup_restore*** (this page)

<br />
<br />

Last Updated : Jan. 2012 -- ***this is discontinued document 開発終了***

- [MySQLデータベースのバックアップ](#mysqlデータベースのバックアップ)
  - [cronを使って定期的に自動でバックアップするためのスクリプト](#cronを使って定期的に自動でバックアップするためのスクリプト)
- [MySQLデータベースのリストア](#mysqlデータベースのリストア)
  - [既存テーブルの削除](#既存テーブルの削除)
  - [リストア](#リストア)
- [dumpBackup.phpツールで記事データのバックアップ](#dumpbackupphpツールで記事データのバックアップ)
  - [事前準備：MediaWikiディレクトリにAdminSettings.phpファイルを作成](#事前準備mediawikiディレクトリにadminsettingsphpファイルを作成)
  - [AdminSettings.phpを削除する](#adminsettingsphpを削除する)
- [cronを使って定期的に自動でバックアップするためのスクリプト](#cronを使って定期的に自動でバックアップするためのスクリプト-1)
- [記事データのリストア](#記事データのリストア)
  - [Xml2sqlを使ってXMLデータをSQL命令に変換する](#xml2sqlを使ってxmlデータをsql命令に変換する)
  - [MySQLデータベースのテーブル名にprefixを付けている場合はsqlファイルを修正](#mysqlデータベースのテーブル名にprefixを付けている場合はsqlファイルを修正)
  - [MySQLコマンドラインを使って、テーブルを空にし、データを流し込む](#mysqlコマンドラインを使ってテーブルを空にしデータを流し込む)
- [統計情報の再計算](#統計情報の再計算)

<br />
<br />

MediaWikiテキストデータのバックアップとリストア方法。MySQLデータベースをフルバックアップする方法と、dumpBackup.phpツールでテキスト部分のみバックアップする方法の2種類を紹介する

なお、画像や音声などのファイルデータは、この方法ではバックアップ/リストアできない 

 MediaWiki公式ページの情報

公式ページも必ず目を通してください

- [Manual:Backing up a wiki/ja](https://www.mediawiki.org/wiki/Manual:Backing_up_a_wiki/ja)
- [Manual:Moving a wiki/ja](https://www.mediawiki.org/wiki/Manual:Moving_a_wiki/ja)


## MySQLデータベースのバックアップ

データベースをフルバックアップする方法の説明。同一バージョン、文字コードなどが同一設定のMySQLデータベースにしかリストア出来ない可能性が高い。また、違うバージョンのMediaWikiに対してリストア出来ない。

コマンドラインから[mysqldump](https://dev.mysql.com/doc/refman/8.0/ja/mysqldump.html)コマンドを使い手動でバックアップする。

特殊な文字が存在する場合の欠落を防ぐため、--hex-blobオプションでバイナリデータを16進数表記文字列にコンバートして出力する。

```bash
mysqldump -Q --host=$MYSQL_SERVER --user=$MYSQL_USER --password=$MYSQL_PASSWD --default-character-set=binary --hex-blob $MYSQL_DB_NAME > backup.sql
```

### cronを使って定期的に自動でバックアップするためのスクリプト

FreeBSD対応のシェルスクリプト

```bash
#!/bin/sh

PATH=/bin:/usr/bin:/sbin:/usr/sbin:/usr/local/bin

TODAY=`date +'%y%m%d'`
DBDUMP_FILE=data/mysql_$TODAY
HOME_DIR=/home/user
BACKUP_DIR=$HOME_DIR/tools/mysql
MEDIAWIKI_DIR=~/www/mw

MYSQL_SERVER=mysql.example.com
MYSQL_USER=tarou_suzuki
MYSQL_PASSWD=qwerty
MYSQL_DB_NAME=mysql-mwdb

cd $BACKUP_DIR

mysqldump -Q --host=$MYSQL_SERVER --user=$MYSQL_USER --password=$MYSQL_PASSWD --default-character-set=binary $MYSQL_DB_NAME > $DBDUMP_FILE.sql 2> /dev/null

gzip --force $DBDUMP_FILE.sql 2> /dev/null

mysqldump -Q --host=$MYSQL_SERVER --user=$MYSQL_USER --password=$MYSQL_PASSWD --default-character-set=binary --hex-blob $MYSQL_DB_NAME > $DBDUMP_FILE.hexblob.sql 2> /dev/null

gzip --force $DBDUMP_FILE.hexblob.sql 2> /dev/null
```

## MySQLデータベースのリストア

前出のセクションでバックアップしたデータを、既存のMySQLデータベースにフルリストアする方法の説明。

### 既存テーブルの削除

コマンドライン版MySQLを使って、show tables;命令で全テーブル名をリストアップし、それをエディタなどで加工してdrop table;命令で一気にテーブルを削除する。

```sh
$ mysql -h $MYSQL_SERVER -u $MYSQL_USER -p$MYSQL_PASSWD

mysql> use $MYSQL_DB_NAME;

mysql> show tables;
Database changed

mysql> show tables;
+-----------------------------+
| Tables_in_$MYSQL_DB_NAME    |
+-----------------------------+
| archive                     |
| category                    |
| categorylinks               |
〜 中略 〜
| valid_tag                   |
| watchlist                   |
+-----------------------------+
50 rows in set (0.01 sec)

mysql> drop table archive,category, 〜中略〜 ,watchlist;
```

### リストア

```bash
mysql -h $MYSQL_SERVER -u $MYSQL_USER -p$MYSQL_PASSWD $MYSQL_DB_NAME < backup.sql
```

## dumpBackup.phpツールで記事データのバックアップ

MediaWikiメンテナンスツール[dumpBackup.php](https://www.mediawiki.org/wiki/Manual:DumpBackup.php)を使って、記事データ（テキストとその編集履歴全て）をバックアップする方法。データベースの種類（MySQLか、PostgreSQLかの違い）やMediaWikiバージョンの違いにかかわらずリストア出来る可能性が最も高い方法。ただし、ページ統計などは移行されない。

### 事前準備：MediaWikiディレクトリに[AdminSettings.php](https://www.mediawiki.org/wiki/Manual:AdminSettings.php/ja)ファイルを作成

AdminSettings.phpファイルの例

```PHP
$wgDBadminuser      = '$MYSQL_USER';
$wgDBadminpassword  = '$MYSQL_PASSWD';
```

コマンドラインから直接入力してバックアップする

```bash
cd /home/user/www/mw
php maintenance/dumpBackup.php --full > backup.xml
```

### AdminSettings.phpを削除する

```bash
rm AdminSettings.php
```

## cronを使って定期的に自動でバックアップするためのスクリプト

```bash
#!/bin/sh

PATH=/bin:/usr/bin:/sbin:/usr/sbin:/usr/local/bin

TODAY=`date +'%y%m%d'`
DBDUMP_FILE=data/mysql_$TODAY
HOME_DIR=/home/user
BACKUP_DIR=$HOME_DIR/tools/mysql
MEDIAWIKI_DIR=~/www/mw

cd $BACKUP_DIR

cp $MEDIAWIKI_DIR/hidden/AdminSettings.php $MEDIAWIKI_DIR > /dev/null

php $MEDIAWIKI_DIR/maintenance/dumpBackup.php --full > $DBDUMP_FILE.dumpbackup.xml 2> /dev/null

gzip --force $DBDUMP_FILE.dumpbackup.xml > /dev/null

rm $MEDIAWIKI_DIR/AdminSettings.php > /dev/null
```

※\$MEDIAWIKI_DIR/hiddenディレクトリはWebからアクセスできないように.htaccess等で制限を掛けること 

## 記事データのリストア

前出のセクションでバックアップしたXML形式の記事データを、[Xml2sql](https://meta.wikimedia.org/wiki/Data_dumps/xml2sql)を使ってsql命令に変換して、MySQLに直接入力してリストアする方法

### Xml2sqlを使ってXMLデータをSQL命令に変換する

Wikimedia Metawikiの公式ページXml2sqlから、ソースコードとパッチをダウンロードして、ビルドする

```bash
$ wget http://ftp.tietew.jp/pub/wikipedia/xml2sql-0.5.tar.gz
$ tar xvf xml2sql-0.5.tar.gz
$ cd xml2sql-0.5
```

パッチファイルを適用する。patchコマンドで失敗する場合は、xml2sql.cに対して手動でつぎのように741行目辺りに2行追加する。 

```PHP
putcolumnf(&rev_tbl, "%d", revision.minor);
/* rev_deleted */
putcolumn(&rev_tbl, "0", 0);
putcolumn(&rev_tbl, "NULL", 0);
putcolumn(&rev_tbl, "NULL", 0);
finrecord(&rev_tbl);
```

そして、ビルドの残り

```bash
$ ./configure
$ make
```

前出のセクションでバックアップしたXMLファイルをsqlファイルにコンバート

```bash
$ xml2sql backup.xml
```

3つのファイル「page.sql」「revision.sql」「text.sql」が生成される。

※ テキストファイル（page.txt等）が生成された場合、明示的に--mysqlというスイッチをつける必要がある。（``` xml2sql --mysql backup.xml ```）

※ MediaWiki 1.18で確認されたエラーで、変換途中に\<redirect /\>等の新しく定義されたタグがあると```unexpected element <redirect> xml2sql: parsing aborted at line 4085 pos 16.```のようなエラーが表示され停止してしまう。解決方法は、該当タグを削除するという方法（参考にしたのは[ここ](https://code.google.com/archive/p/wikokit/wikis/MySQL_import.wiki)）。 

### MySQLデータベースのテーブル名にprefixを付けている場合はsqlファイルを修正

バイナリデータを破壊しないエディタ（例：emacs）を用いて、ファイルを先頭から検索して全て修正していく。

```sql
-- xml2sql - MediaWiki XML to SQL converter
-- Table page for MySQL

/*!40000 ALTER TABLE `prefix_page` DISABLE KEYS */;
LOCK TABLES `prefix_page` WRITE;
INSERT INTO `prefix_page` VALUES (1,0,'メインページ',,0,
```

### MySQLコマンドラインを使って、テーブルを空にし、データを流し込む

```bash
$ mysql -h $MYSQL_SERVER -u $MYSQL_USER -p$MYSQL_PASSWD $MYSQL_DB_NAME

mysql> truncate table prefix_page;
mysql> truncate table prefix_revision;
mysql> truncate table prefix_text;
mysql> quit;
```

```bash
$ mysql -h $MYSQL_SERVER -u $MYSQL_USER -p$MYSQL_PASSWD $MYSQL_DB_NAME < page.sql
$ mysql -h $MYSQL_SERVER -u $MYSQL_USER -p$MYSQL_PASSWD $MYSQL_DB_NAME < revision.sql
$ mysql -h $MYSQL_SERVER -u $MYSQL_USER -p$MYSQL_PASSWD $MYSQL_DB_NAME < text.sql
```

※ MediaWiki 1.20のDBにrevision.sqlを投入するときに```ERROR 1136 (21S01) at line 6: Column count doesn't match value count at row 1```というエラーが出力された時の解決方法（参考にしたのはここ）。

バックアップを取得した側のrevisionテーブル構造と投入する側のテーブル構造を比較すると、投入側には新たに***rev_sha1***というカラムが追加されている。一旦このカラムを削除してデータを流し込み、投入後にカラムを新規作成するという方法で問題を回避可能。（テーブル構造はMediaWikiのバージョンにより変化するので、必ず***テーブル構造の違いを自ら確認***すること）

```bash
$ mysql -h $MYSQL_SERVER -u $MYSQL_USER -p$MYSQL_PASSWD $MYSQL_DB_NAME

mysql> ALTER TABLE revision DROP COLUMN rev_sha1;
mysql> source /tmp/revision.sql;
mysql> ALTER TABLE revision ADD COLUMN rev_sha1 varbinary(32) AFTER rev_parent_id;
```

## 統計情報の再計算

[[特別:統計]]ページがリセットされてしまった場合、その一部分をDB内データの再集計により正しい値に戻せる場合もあります。MediaWikiのmaintenanceディレクトリ内のinitStats.phpをコマンドラインより実行してください。 
