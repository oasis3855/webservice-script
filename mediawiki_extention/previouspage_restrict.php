<?php
 
if (!defined('MEDIAWIKI')) die("MediaWiki extensions cannot be run directly.");
 
/*
* previouspage_restrict.php    for MediaWiki extention
* (C) INOUE. Hirokazu
*
* install : add script on LocalSettings.php
*   require_once("$IP/extensions/previouspage_restrict.php");
*/
$wgExtensionCredits['other'][] = array(
    'name' => "previouspage_restrict_extention",
    'author' => "INOUE. Hirokazu",
    'version' => "1.2 (2012/Jan/20) for mw 1.18",
    'description' => "prohibit to open previous pages fo non-logon user",
    'url' => "http://oasis.halfmoon.jp/mw/index.php?title=Soft-MediaWiki-PreviouspageRestrict-Ext",
);
 
$obj_previouspage_restrict = new previouspage_restrict();
 
$wgHooks['BeforePageDisplay'][] = array($obj_previouspage_restrict, 'wfMainHookFunction');
 
class previouspage_restrict {
 
    # class constructor
    function previouspage_restrict() {
    }
 
    function wfMainHookFunction(&$page) {
 
        global $wgOut, $wgUser, $wgTitle, $wgRequest;       # use global object

        ### for debug
        # echo "<!-- <pre>\n";
        # print_r($wgUser);
        # print_r($wgTitle);
        # print_r($wgRequest);
        # echo "\n</pre> -->\n";
        ### for debug
 
        # if Loggedin, do nothing (return)
        if($wgUser->isLoggedIn()) {
            # isLoggedin() is defined includes/User.php
            return true;
        }
 
        # if not defined oldid(previous page) and not history mode, do nothing (return)
        # 履歴モード以外の場合は、このプログラムを抜ける 
        if($wgRequest->getVal('oldid') == NULL) {
            if($wgRequest->getVal('action') == NULL) {
                return true;    # 履歴モードで無い場合
            }
            else if($wgRequest->getVal('action') != 'history' && $wgRequest->getVal('action') != 'edit'){
                return true;    # 履歴一覧または編集画面で無い場合
            }
        }

        # if prohibited Special: page, display error message insted of Wiki article
        # (制限ページに合致した場合、エラーメッセージを表示する)
        $wgOut->showErrorPage( 'errorpagetitle', 'notloggedin' );
        # set contentSub
        $wgOut->setSubtitle( 'topic history is not available' );
 
        return true;

    } # function wfMainHookFunction
 
} # class previouspage_restrict
