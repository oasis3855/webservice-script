<?php
 
if (!defined('MEDIAWIKI')) die("MediaWiki extensions cannot be run directly.");
 
/*
* specialpage_restrict.php    for MediaWiki extention
* (C) INOUE. Hirokazu
*
* install : add script on LocalSettings.php
*   require_once("$IP/extensions/specialpage_restrict.php");
*/
$wgExtensionCredits['other'][] = array(
    'name' => "specialpage_restrict_extention",
    'author' => "INOUE. Hirokazu",
    'version' => "1.1 (2012/Jan/20) for mw 1.13",
    'description' => "prohibit to open special pages fo non-logon user",
    'url' => "http://oasis.halfmoon.jp/mw/index.php?title=Soft-MediaWiki-SpecialpageRestrict-Ext",
);
 
 
$obj_specialpage_restrict = new specialpage_restrict();
 
# MediaWiki Hook (MediaWikiのフック機能)
$wgHooks['BeforePageDisplay'][] = array($obj_specialpage_restrict, 'wfMainHookFunction');
 
 
class specialpage_restrict {
 
    # class constructor
    function specialpage_restrict() {
    }
 
    function wfMainHookFunction(&$page) {
        global $wgOut, $wgUser, $wgTitle;       # use global object

        ### for debug
        # echo "<!-- <pre>\n";
        # print_r($wgUser);
        # print_r($wgTitle);
        # echo "\n</pre> -->\n";
        ### for debug
 
        # if Loggedin, do nothing (return) (ログイン済みの時は何もしない)
        if($wgUser->isLoggedIn()) {
            # isLoggedin() is defined includes/User.php
            return true;
        }
 
        # if not Special:, do nothing (return) (Special:ページとUser:ページ以外では何もしない)
        if($wgTitle->mNamespace != NS_SPECIAL && $wgTitle->mNamespace != NS_USER) {
            # NS_SPECIAL, NS_USER is defined at includes/Defines.php
            return true;
        }
 
        $bAllowed = false;  # this is set true, if matched to AllowTitles

        # Special:ページのうち、ログイン、ログアウトページのみは許可する
        $arrAllowTitle = array(SpecialPage::getTitleFor( 'Userlogin' ), SpecialPage::getTitleFor( 'Userlogout' ));  # array of AllowedTitles
        # check Allowed Titles (許可されたページかどうか判別する)
        foreach($arrAllowTitle as $sAllowTitle) {
#            if($wgTitle->mTextform == $sAllowTitle) {      # before MediaWiki ver 1.13
            if($wgTitle->mPrefixedText == $sAllowTitle) {
                # Allowed Title
                $bAllowed = true;
            }
        }
 
        # if prohibited Special: page, display error message insted of Wiki article
        # (制限ページに合致した場合、エラーメッセージを表示する)
        if($bAllowed == false) {
            # show error screen, message is defined at languages/messages/MessagesXX.php
            $wgOut->showErrorPage( 'notloggedin', 'prefsnologintext' );
        }
 
        return true;

    } # function wfRestrictSpecialPage
 
} # class obj_ipuser_restrict
