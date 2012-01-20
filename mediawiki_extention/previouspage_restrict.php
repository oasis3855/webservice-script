<?php
if (!defined('MEDIAWIKI')) die("MediaWiki extensions cannot be run directly.");
/*
* previouspage_restrict.php    for MediaWiki extention
* (C) inoue-hiro
* created 9 Feb 2009
* tested on MediaWiki 1.13.3
*
* install : add script on LocalSettings.php
*   require_once("$IP/extensions/previouspage_restrict.php");
*/
$wgExtensionCredits['other'][] = array(
    'name' => "previouspage_restrict_extention",
    'author' => "inoue-hiro",
    'description' => "prohibit to open previous pages fo non-logon user",
    'url' => "http://www.mediawiki.org/",
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
        # echo "<pre>\n";
        # print_r($wgUser);
        # print_r($wgTitle);
        # print_r($wgRequest);
        # echo "\n</pre>\n";
        ### for debug
 
        # if Loggedin, do nothing (return)
        if($wgUser->isLoggedIn()) {
            # isLoggedin() is defined includes/User.php
            return true;
        }

        # if not defined oldid(previous page) and not history mode, do nothing (return)
#       if(empty($wgRequest->data['oldid']) && $wgRequest->data['action'] != 'history') {
#           return true;
#       }
        if(empty($wgRequest->data['oldid'])) {
            if(empty($wgRequest->data['action'])) {
                return true;
            }
            else if($wgRequest->data['action'] != 'history'){
                return true;
            }
        }
        
        # if prohibited previous page, display error message insted of Wiki article
        # show error screen, message is defined at languages/messages/MessagesXX.php
        $wgOut->showErrorPage( 'notloggedin', 'prefsnologintext' );
        # set contentSub
        $wgOut->setSubtitle( 'topic history is not available' );
    
    
        return true;
        
    } # function wfMainHookFunction
} # class previouspage_restrict

## eof
