## ![icon](../readme_pics/softdown-ico-MediaWiki.png) (MediaWiki) InterWikiのキーワードを追加する<!-- omit in toc -->

[Home](https://oasis3855.github.io/webpage/) > [Software](https://oasis3855.github.io/webpage/software/index.html) > [Software Download](https://oasis3855.github.io/webpage/software/software-download.html) > [webservice-scripts](../../README.md) > [mediawiki_extention](../README.md) > ***howto_add_interwiki*** (this page)

<br />
<br />

Last Updated : Jan. 2012 -- ***this is discontinued document 開発終了***

<br />
<br />

## InterWikiのキーワードとURLの格納箇所

MySQLデータベースのpfix_interwikiテーブルに格納されている。（pfixは、MwdiaWikiのセットアップ時に指定したテーブル名のプレフィックス文字列）

テーブルをCSVにエクスポートすると、次のようになっている。

```
"acronym";"http://www.acronymfinder.com/af-query.asp?String=exact&Acronym=$1";"0";"0"
"advogato";"http://www.advogato.org/$1";"0";"0"
"annotationwiki";"http://www.seedwiki.com/page.cfm?wikiid=368&doc=$1";"0";"0"
"arxiv";"http://www.arxiv.org/abs/$1";"0";"0"
"c2find";"http://c2.com/cgi/wiki?FindPage&value=$1";"0";"0"

～中略～

"wikipedia";"http://en.wikipedia.org/wiki/$1";"1";"0"
"wlug";"http://www.wlug.org.nz/$1";"0";"0"
"zwiki";"http://zwiki.org/$1";"0";"0"
"zzz wiki";"http://wiki.zzz.ee/index.php/$1";"0";"0"
"wikt";"http://en.wiktionary.org/wiki/$1";"1";"0"
```

## キーワードの追加の例
### Wikipedia日本語版

標準では英語版のWikipediaのキーワードは存在しているが…　今回日本語版のキーワードを追加する。

```SQL
INSERT INTO mw1_interwiki (iw_prefix,iw_url) VALUES ('w','http://ja.wikipedia.org/wiki/$1')
```

この設定をした後は、```[[w:記事名]]``` のように記述できる。（例：w:ヴィレム3世 (オランダ王)）

### MediaWiki

MediaWikiプロジェクトへのキーワードを追加する。

```SQL
INSERT INTO mw1_interwiki (iw_prefix,iw_url) VALUES ('mw','http://www.mediawiki.org/wiki/$1')
```

この設定をした後は、```[[mw:記事名]]``` のように記述できる。（例：mw:Help:Formatting/ja） 

