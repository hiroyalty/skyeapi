<?php


/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 *
$jso = '{"response": [{"transid":"5","status":"Success"}]}';
$json = json_decode($jso);
#$all = array();
foreach($json as $key => $value) {
    foreach ($value as $val)    {
        #echo $val->status;
}
} */
session_start();
require_once 'lib/oracle_dbase.php';
require_once 'lib/f.php';
$msisdn = isset($_GET['msisdn']) ? $_GET['msisdn'] : '2347066192100';
$sessionid = isset($_GET['sessionid']) ? $_GET['sessionid'] : time();
$userdata = isset($_GET['userdata']) ? $_GET['userdata'] : "*366*9#";

$logFile = "transaction.log";
log_action("Logging USSD Request@" . date('Y-m-d h:i:s') . ">> Session id ($sessionid), MSISDN ($msisdn),"
        . "UserData ($userdata)\n", $logFile);

$last_request = 4;
$response = null;
switch ($userdata) {
    case "*366*9#":
        # call skye bank API to confirm if the number
        $response = array(
            'userdata' => 'Enter \n1. Credit your phone Number\n2. Credit another phone Number',
            'endofsession' => 'false', # can be true or false
            'msisdn' => $msisdn,
            'sessionid' => $sessionid
        );
        break;
    case "1":
        
        $response = array(
            'userdata' => 'Enter the Amount:\n1',
            'endofsession' => 'false', # can be true or false
            'msisdn' => $msisdn,
            'sessionid' => $sessionid
        );
        break;
    case "2":
        $last_request = 5;
        $response = array(
            'userdata' => 'Enter Phone Number to Credit:\n1.',
            'endofsession' => 'false', # can be true or false
            'msisdn' => $msisdn,
            'sessionid' => $sessionid
        );
        break;
    //case (preg_match('/*366*9.*/', $userdata) ? true : false) :        
       // $response = array(
        //  'userdata' => 'Enter the last four digits of the Card:\n1.',
        //  'endofsession' => 'false', # can be true or false
        //   'msisdn' => $msisdn,
        //   'sessionid' => $sessionid
       // );
       // break;
    default:
        # what was the last request
        switch ($last_request) {
            case "4":
                # user was asking for balance
                #$bal = get_balance_by_pin($userdata);
                $response = array(
                    'userdata' => "Transaction Completed!",
                    'endofsession' => 'true', # can be true or false
                    'msisdn' => $msisdn,
                    'sessionid' => $sessionid);
                break;
            case "5":
                $last_request = 4;
                $response = array(
                    'userdata' => 'Enter the last four digits of your Card:\n1.',
                    'endofsession' => 'false', # can be true or false
                    'msisdn' => $msisdn,
                    'sessionid' => $sessionid);
                break;
            
            default:
                break;
        }

        break;
}
header('Content-Type: application/json');
echo json_encode($response);

/*    $stat_id = 1;
    $length = 15;
    $transId = getToken($length);
    if($transId)    {
        $response = array(
            'userdata' => 'Transaction Successfull:',
            'endofsession' => 'true', # can be true or false
            'msisdn' => $msisdn );
        #echo '{"success": ' . json_encode($sql) . '}';  
    } else {
        $response = array(
            'userdata' => 'Transaction Failed:',
            'endofsession' => 'true', # can be true or false
            'msisdn' => $msisdn );
        #echo '{"failure":{"text":'. Failed .'}}';
    }
    header('Content-Type: application/json'); 
    echo json_encode($response);