<?php
if (!defined('MEDIAWIKI')) die("MediaWiki extensions cannot be run directly.");
/*
* specialpage_restrict.php    for MediaWiki extention
* (C) inoue-hiro
* created 9 Feb 2009
* tested on MediaWiki 1.13.3
*
* install : add script on LocalSettings.php
*   require_once("$IP/extensions/specialpage_restrict.php");
*/
$wgExtensionCredits['other'][] = array(
    'name' => "specialpage_restrict_extention",
    'author' => "inoue-hiro",
    'description' => "prohibit to open special/user pages fo non-logon user",
    'url' => "http://www.mediawiki.org/",
);
 
$obj_specialpage_restrict = new specialpage_restrict();

$wgHooks['BeforePageDisplay'][] = array($obj_specialpage_restrict, 'wfMainHookFunction');

class specialpage_restrict {

    # class constructor
    function specialpage_restrict() {
    }

    function wfMainHookFunction(&$page) {

        global $wgOut, $wgUser, $wgTitle;       # use global object
 
        ### for debug
        # echo "<pre>\n";
        # print_r($wgUser);
        # print_r($wgTitle);
        # echo "\n</pre>\n";
        ### for debug
 
        # if Loggedin, do nothing (return)
        if($wgUser->isLoggedIn()) {
            # isLoggedin() is defined includes/User.php
            return true;
        }

        # if not Special:, do nothing (return)
        if($wgTitle->mNamespace != NS_SPECIAL && $wgTitle->mNamespace != NS_USER) {
            # NS_SPECIAL is defined at includes/Defines.php
            return true;
        }

        $bAllowed = false;  # this is set true, if matched to AllowTitles
        
        $arrAllowTitle = array(SpecialPage::getTitleFor( 'Userlogin' ), SpecialPage::getTitleFor( 'Userlogout' ));  # array of AllowedTitles
        
        # check Allowed Titles
        foreach($arrAllowTitle as $sAllowTitle) {
#           if($wgTitle->mTextform == $sAllowTitle) {   # mw 1.13
            if($wgTitle->mPrefixedText == $sAllowTitle) {
                # Allowed Title
                $bAllowed = true;
            }
        }
        
        # if prohibited Special: page, display error message insted of Wiki article
        if($bAllowed == false) {
            # show error screen, message is defined at languages/messages/MessagesXX.php
            $wgOut->showErrorPage( 'notloggedin', 'prefsnologintext' );
        }

        return true;
        
    } # function wfRestrictSpecialPage
} # class obj_ipuser_restrict

## eof
