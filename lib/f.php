<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'lib/oracle_dbase.php';

function callUrl($url) {
    try {
# try pushing request to url;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPGET, 1); // Make sure GET method it used
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return the result
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
        $res = curl_exec($ch); // Run the request
    } catch (Exception $ex) {

        $res = 'Error Calling URL';
    }
    return $res;
}

function crypto_rand_secure($min, $max) {
        $range = $max - $min;
        if ($range < 0) return $min; // not so random...
        $log = log($range, 2);
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }

function getToken($length){
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        for($i=0;$i<$length;$i++){
            $token .= $codeAlphabet[crypto_rand_secure(0,strlen($codeAlphabet))];
        }
        return $token;
    }
    
function sendSMS($src, $dest, $body) {
        $id = "samson_ude@yahoo.com";
        $pw = "eut235A33"; # ensure that you use the approved password on v2nmobile.

        $url = "http://v2nmobile.co.uk/api/httpsms.php?u=" .
                urlencode($id) . "&p=" . urlencode($pw)
                . "&r=" . urlencode($dest)
                . "&s="
                . urlencode($src)
                . "&m="
                . urlencode($body) . "&t=1";
        callUrl($url);
    }
    
function log_action($msg, $logFile) {
    #$date_time = date("Y-m-d h:i:s");
    #$logpath = '/var/www/html/nsl/';
    #$logFile = "call.log";
    //$log = "$date_time >> $msg";
    $fp = fopen($logFile, 'a+');
    fputs($fp, $msg);
    fclose($fp);
    return TRUE;
}

function make_topup_request_mtn($origmsisdn,$destMsisdn,$sequenceid,$amount,$tarifftypeid,$serviceproviderid,$sdesc,$operator,$statusId,$txRefId,$seqstatus,$seqtxRefdId,$lasseq,$origBalance,$destBalance,$voucherPIN,$voucherSerial,$responseCode,$responseMessage)   {
    $length = 15;
    $transId = getToken($length);
    $log_req = "insert into top_request_main (transaction_id,origmsisdn,destmsisdn,sequence_value,amount,tarriftypeid,serviceproviderid,description,operator,status_id,txrefid,seqstatus,seqtxrefid,lasseq,origbalance,destbalance,VOUCHERPIN,VOUCHERSERIAL,responsecode,responsemessage) values ('$transId','$origmsisdn','$destMsisdn',$sequenceid,$amount,$tarifftypeid,$serviceproviderid,'$sdesc','$operator','$statusId','$txRefId','$seqstatus','$seqtxRefdId','$lasseq','$origBalance','$destBalance','$voucherPIN','$voucherSerial','$responseCode','$responseMessage')";
    $sql = db_execute($log_req);
    return $sql;
}

function process_values($vals)  {
    if(is_array($vals)){
        if(empty($vals))   {
            #return $key. '-' . '0';
            return NULL;
        } else    {
           foreach($vals as $v => $val){
               #echo $key. ' - '. $v. ' : '. $val;
               return $val;
        } 
        }
    } else  {
        #echo $key. ' - '. $value;
        return $vals;
    }
}
