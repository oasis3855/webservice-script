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
    'version' => "1.4 (2012/Feb/02) for mw 1.18",
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

        # if Loggedin, do nothing (return)
        if($wgUser->isLoggedIn()) {
            # isLoggedin() is defined includes/User.php
            return true;
        }
 
        # allow without action and history(oldid=)
        # 機能（action=history）や履歴（oldid=123）で無ければ表示する 
        if($wgRequest->getVal('action') == NULL &&
            $wgRequest->getVal('oldid') == NULL){
            return true;
        }
        # キャッシュクリア(action=purge)を許可する
        if($wgRequest->getVal('action') == 'purge' &&
            $wgRequest->getVal('oldid') == NULL){
            return true;
        }


        # if prohibited Special: page, display error message insted of Wiki article
        # (制限ページに合致した場合、エラーメッセージを表示する)
        $wgOut->showErrorPage( 'errorpagetitle', 'notloggedin' );
        # set contentSub
        $wgOut->setSubtitle( 'topic history is not available' );
 
        return true;

    } # function wfMainHookFunction
 
} # class previouspage_restrict
