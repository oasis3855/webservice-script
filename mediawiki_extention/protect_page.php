<?php
if (!defined('MEDIAWIKI')) die("MediaWiki extensions cannot be run directly.");

$wgExtensionCredits['other'][] = array(
    'name' => "protectpage_extention",
    'author' => "INOUE. Hirokazu",
    'version' => "1.0 (2012/Jan/21) for mw 1.18",
    'description' => "prohibit to open page with protect tag for non-logon user",
    'url' => "http://oasis.halfmoon.jp/mw/index.php?title=Soft-MediaWiki-Protectpage-Ext",
);

$flag_protect = false;

$wgExtensionFunctions[] = "wfProtectpage_setup";

function wfProtectpage_setup() {
    global $wgMessageCache, $wgParser, $wgHooks;
    global $pCrudeProtection_Messages;

    // set hook
    $wgHooks['BeforePageDisplay'][]='wfBeforeDisplay';

    // set hook function for 'protect' tag
    $wgParser->setHook( "protect", "wfFound_tag_hook" );
}

// hook function, if detect <protect></protect>
// <protect></protect> タグが検出された場合のフック関数
function wfFound_tag_hook($Input, $Args) {
    global $wgOut, $wgUser, $wgParser;
    global $flag_protect;

    // disable cache (キャッシュを破棄する)
    $wgParser->disableCache();

    // default : protect page (デフォルトで、保護フラグを立てる)
    $flag_protect = true;
    # if Loggedin, unprotect page (ログインしている場合、保護フラグを消す)
    if($wgUser->isLoggedIn()) {
        $flag_protect = false;
        return '';
    }
    return '';
}

function wfBeforeDisplay(&$wgOut) {
    global $wgRequest, $flag_protect;
    if(isset($flag_protect) && $flag_protect == true)
    {
        # show error(protected message) page (エラーメッセージのページを表示する)
        $wgOut->showErrorPage( 'errorpagetitle', 'notloggedin' );
        $wgOut->setSubtitle( 'this topic is not allowed to view for non login user' );
    }
    return true;
}
