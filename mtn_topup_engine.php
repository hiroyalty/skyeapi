<?php

require_once 'lib/oracle_dbase.php';
require_once 'lib/f.php';
##################### the variables

$origmsisdn = isset($_REQUEST['origmsisdn']) ? $_REQUEST['origmsisdn'] : "2348032006048";
$recvtim = isset($_REQUEST['recvtime']) ? $_REQUEST['recvtime'] : date('Y-m-d h:i:s'); #"2012-07-01 23:59:59";
$operator = isset($_REQUEST['operator']) ? $_REQUEST['operator'] : "mtn";
$destmsisdn = isset($_REQUEST['destmsisdn']) ? $_REQUEST['destmsisdn'] : "2347069900008";
$amount = isset($_REQUEST['amount']) ? urlencode($_REQUEST['amount']) : "40";
$tarifftypeid = isset($_REQUEST['Tarifftypeid']) ? urlencode($_REQUEST['Tarifftypeid']) : 1; //niyi number
$serviceproviderid = isset($_REQUEST['Serviceproviderid']) ? urlencode($_REQUEST['Serviceproviderid']) : "1"; 
$sdesc = isset($_REQUEST['sdesc']) ? $_REQUEST['sdesc'] : "dreamcall";
$recvtime = urlencode($recvtim);
$url = "http://41.206.4.75:8083/axis2/services/HostIFService";
$host="41.206.4.75";    
$seqid = "SELECT transid_seq.nextval FROM dual";
$sqvalue = db_query($seqid);
foreach ($sqvalue as $key => $value) {
    $sequenceid = $value->NEXTVAL;
    #echo $sequenceid.'<br/>';
}

$xml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://hostif.vtm.prism.co.za/xsd">
   <soapenv:Header/>
   <soapenv:Body>
      <xsd:vend>
         <xsd:sequence>'.$sequenceid.'</xsd:sequence>
         <xsd:origMsisdn>'.$origmsisdn.'</xsd:origMsisdn>
         <xsd:destMsisdn>'.$destmsisdn.'</xsd:destMsisdn>
         <xsd:amount>'.$amount.'</xsd:amount>
         <xsd:tariffTypeId>'.$tarifftypeid.'</xsd:tariffTypeId>
 <xsd:serviceProviderId>'.$serviceproviderid.'</xsd:serviceProviderId>
      </xsd:vend>
   </soapenv:Body>
</soapenv:Envelope>';

$headers = array(
    "POST  HTTP/1.1",
    "Host: $host",
    "Content-type: text/xml; charset=\"utf-8\"",
    "SOAPAction: \"\"",
    "Content-length: " . strlen($xml)
);

$soap_do = curl_init();
curl_setopt($soap_do, CURLOPT_URL, $url);
curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);
#curl_setopt($soap_do, CURLOPT_CUSTOMREQUEST, "POST");
#curl_setopt($soap_do, CURLOPT_POST, true);
curl_setopt($soap_do, CURLOPT_POSTFIELDS, $xml);
curl_setopt($soap_do, CURLOPT_HEADER, false);
curl_setopt($soap_do, CURLOPT_HTTPHEADER, $headers);

//curl_setopt($soap_do, CURLOPT_USERPWD, $username . ":" . $password);

$result = curl_exec($soap_do);
$err = curl_error($soap_do);
//close connection
curl_close($soap_do);
//log_action($result, 'messages.log');
#print_r($result);

$fileContents = $result;

$fileContents = str_replace(array("\n", "\r", "\t"), '', $fileContents);
$fileContents = trim(str_replace('"', "'", $fileContents));
$fileContents = str_replace("<?xml version='1.0' encoding='utf-8'?>", '<?xml version="1.0" encoding="UTF-8"?>', $fileContents);
$fileContents = str_replace("<soapenv:Envelope xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/'>",'',$fileContents);
$fileContents = trim(str_replace('<soapenv:Body>', '', $fileContents));
$fileContents = trim(str_replace("<vendResponse xmlns='http://hostif.vtm.prism.co.za/xsd'>", '<vendResponse>', $fileContents));
$fileContents = trim(str_replace('</soapenv:Body></soapenv:Envelope>', '', $fileContents));
$fileContents = str_replace('<![CDATA[','',$fileContents);
$fileContents = str_replace(']]>','',$fileContents);

#echo $fileContents;
$soa = simplexml_load_string($fileContents);
$json = json_encode($soa);
#echo $json;
$array = json_decode($json,TRUE);
#print_r($array);

extract($array, EXTR_PREFIX_SAME, "wddx");

#echo "$sequence,$statusId,$txRefId,$seqstatus,$seqtxRefdId,$lasseq,$origBalance,$destBalance,$dateTime,$voucherPIN,$voucherSerial,$origMsisdn,$destMsisdn,$responseCode,$responseMessage";

$sequence = process_values($sequence);
$statusId = process_values($statusId);
$txRefId = process_values($txRefId);
$seqstatus = process_values($seqstatus);
$seqtxRefdId = process_values($seqtxRefdId);
$lasseq = process_values($lasseq);
$origBalance = process_values($origBalance);
$destBalance = process_values($destBalance);
$dateTime = process_values($dateTime);
$voucherPIN = process_values($voucherPIN);
$voucherSerial = process_values($voucherSerial);
$origMsisdn = process_values($origMsisdn);
$destMsisdn = process_values($destMsisdn);
$responseCode = process_values($responseCode);
$responseMessage = process_values($responseMessage);

// log top up request response
$retval = make_topup_request_mtn($origmsisdn,$destmsisdn,$sequenceid,$amount,$tarifftypeid,$serviceproviderid,$sdesc,$operator,$statusId,$txRefId,$seqstatus,$seqtxRefdId,$lasseq,$origBalance,$destBalance,$voucherPIN,$voucherSerial,$responseCode,$responseMessage);

#print_r($retval);
$responsed = array(
            'statusId' => $statusId,
            'responsecode' => $responseCode,
            'responsemessage' => $responseMessage,
            'sequencevalue' => $sequence,
            'origmsisdn' => $origmsisdn,
            'destmsisdn' => $destmsisdn,
            'amount' => $amount,
            'vourcherPIN' => $voucherPIN,
            'vourcherSerial' => $voucherSerial,
            'transactionrefid' => $txRefId
             );
#print_r($responsed);
#return $responsed;
echo json_encode($responsed);
/*

        /*$sequence = $value->sequence;
        $statusid = $value->statusId;
        $txrefid = $value->txRefId;
        $seqstatus = $value->seqstatus;
        $seqtxrefdid = $value->seqtxRefdId;
        $lasseq = $value->lasseq;
        $origbalance = $value->origBalance;
        echo $origbalance.'<br/>';
        $destbalance = $value->destBalance;
        echo $destbalance.'<br/>';
        $datetime = $value->dateTime;
        $voucherpin = $value->voucherPIN;
        $voucherserial = $value->voucherSerial;
        $origmsisdn = $value->origMsisdn;
        $destmsisdn = $value->destMsisdn;
        $responsecode = $value->responseCode;
        $responsemessage = $value->responseMessage; 
}*/


?>
