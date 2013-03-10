<?php
// utf8, php script
//////////////////////////////
// get mail account (server, user, password) from CSV file
//
//
// CSV structure (begin with '#' line is comment)
// # nickname,mail_server,user_name,password,service,port-imap,port-pop3,port-smtp
// my_daily,mail.example.com,dummy@example.com,mypass,imap/pop3/smtp,993,110,587
//
//////////////////////////////

function GetMailAccount($strNickname, $service = 'imap')
{
    // make CSV file full path
    $info=posix_getpwuid(posix_geteuid());      // get user HOME dir
    $strDataFileName = $info['dir'].'/auth/data/mailaccount.csv';
    // return data (mail account data)
    $aryAccount = array('protocol'=>'', 'server'=>'', 'port'=>'', 'user'=>'', 'password'=>'');

    $hFile = fopen($strDataFileName, 'r');
    if($hFile == FALSE)
    {
        // open error, return blank array
        return($aryAccount);
    }

    while(!feof($hFile))
    {
        $strTmp = rtrim(fgets($hFile));
        if(strlen($strTmp) < 1) continue;

        if(!strcmp(substr($strTmp[0],0,1),'#'))
        {   // comment line (begin with '#')
            continue;
        }

        $aryData = explode(',', $strTmp);
        if(count($aryData) != 8)
        {   // vaild data line is 8 column
            continue;
        }

        // find first nickname matched line
        if(!strcmp($aryData[0],$strNickname) && stripos($aryData[4], $service) !== false)
        {
            $aryAccount['server'] = $aryData[1];
            $aryAccount['user'] = $aryData[2];
            $aryAccount['password'] = $aryData[3];
            $aryAccount['protocol'] = strtolower($service);
            switch($service) {
                case 'imap' : $aryAccount['port'] = $aryData[5]; break;
                case 'pop3' : $aryAccount['port'] = $aryData[6]; break;
                case 'smtp' : $aryAccount['port'] = $aryData[7]; break;
                default : $aryAccount['port'] = ''; break;
            }
            break;
        }
    }

    fclose($hFile);

    // if port_no is blank, reset all value of array
    if(strlen($aryAccount['port']) <= 0) {
        $aryAccount = array('protocol'=>'', 'server'=>'', 'port'=>'', 'user'=>'', 'password'=>'');
    }

    return($aryAccount);
}

?>

