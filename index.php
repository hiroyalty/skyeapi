<?php
session_start();
require_once 'lib/oracle_dbase.php';
require_once 'lib/f.php';

ini_set("display_errors",1);
require 'Slim/Slim.php';
//if($_POST)
//{
//    echo "asdfasdf";
//    exit;
//}
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->get('/hello', 'sayHello');
$app->get('/token', 'alltoken');
$app->get('/debitrequest', 'debitRequest');
$app->get('/topuprequest', 'TopUpRequest');
$app->get('/addagent', 'addAgent');
$app->get('/users/search/:query', 'findByName');
$app->put('/users/:id', 'updateUser');
$app->delete('/users/:id',    'deleteUser');


$app->run();

function sayHello() {
    global $app;
    $req = $app->request();
    $nameValue = $req->get('name');
    $idValue = $req->get('id');
    $rel = 'hello '.$nameValue. ' your student id is '.$idValue;
    echo json_encode($rel);
}

function allToken()  {
    $res = db_query("select * from token_checker");
    echo json_encode($res);
}

function debitRequest() {
    global $app;
    #http://83.138.190.170/skyeapi/debitrequest?msisdn=2347062385282&sessionid=435654257&endofsesssion=false&userdata=*336*9*750*07062385281#&op=MTNmain
    #msisdn=2348134197100&sessionid=435654257&endofsesssion=false&userdata=*336*5#
    $req = $app->request();
    #$userdata= '*336*9#';
    $userdata = $req->get('userdata');
    $use  = explode('*', $userdata);
    if($use[4]){
        $amt = $use[3];
        $destMsisdn = $use[4];
        $destMsisdn = rtrim($destMsisdn, "#");
    } else{
        $amt = $use[3];
        $amt = rtrim($amt, '#');
        $destMsisdn = $req->get('msisdn');
    }
    $actMsisdn = $req->get('msisdn');
    $msisdn = $actMsisdn;
    $sessionid = $req->get('sessionid') ? $req->get('sessionid') : time();
    #$amt = $amt * 100;
    $op = $req->get('op');
    $stat_id = 1;
    $length = 15;
    $transId = getToken($length);
    //Log action:
    $logFile = "transaction.log";
    log_action("Logging USSD Request@" . date('Y-m-d h:i:s') . ">> Session id ($sessionid), MSISDN ($actMsisdn),"
        . "UserData ($userdata)\n", $logFile);
    #$transId = db_query("select TRANSID_SEQ.NEXTVAL from dual");
    #$log_req = "insert into top_request (transaction_id,act_msisdn,dest_msisdn,status_id,amt,operator) values (TRANSID_SEQ.NEXTVAL,'$actMsisdn','$destMsisdn','$amt','$op' )";
    $log_req = "insert into top_request (transaction_id,act_msisdn,dest_msisdn,status_id,amt,operator,sessionid) values ('$transId','$actMsisdn','$destMsisdn',$stat_id,'$amt','$op','$sessionid' )";
    
    $sql = db_execute($log_req);
    if($sql)    {
        $response = array(
            'response' => 'Transaction Successfull:',
            'endofsession' => 'true', # can be true or false
            'msisdn' => $msisdn );
        #echo '{"success": ' . json_encode($sql) . '}';  
    } else {
        $response = array(
            'response' => 'Registration Failed:',
            'endofsession' => 'true', # can be true or false
            'msisdn' => $msisdn );
        #echo '{"failure":{"text":'. Failed .'}}';
    }
    header('Content-Type: application/json'); 
    echo json_encode($response);
    
}

function TopUpRequest() {
    global $app;
    $req = $app->request(); // Getting parameter with names
    $paramDestMsisdn = $req->get('destmsisdn'); // Getting parameter with names
    $paramAmount = $req->get('amount'); // Getting parameter with names
    $paramUserName = $req->get('username');
    $paramPassword = $req->get('password');
    $paramDescription = $req->get('sdescription');
    
    $top_agent = "SELECT CASE WHEN MAX(ID) IS NULL THEN 'NO' ELSE 'YES' END User_exists FROM TOPUP_AGENT
                    WHERE USERNAME = '$paramUserName' and PASSWORD = '$paramPassword' and SERVICE_DESCRIPTION = '$paramDescription'";
    $sqlx = db_query($top_agent);
    #var_dump($sqlx);
    foreach($sqlx as $key => $val)  { 
        $sqlc = $val->USER_EXISTS;
        #echo $sqlc;
    }
    if($sqlc == 'YES')   {
        #$log_req = "insert into topup_agent (username,email,password,service_description) values ('$paramUserName','$paramEmail','$paramPassword','$paramDescription' )";
        #$res = callUrl("http://83.138.190.170/skyeapi/mtn_topup_engine.php?destmsisdn=$paramDestMsisdn&amount=$paramAmount&sdesc=$paramDescription");
        $res = file_get_contents("http://localhost/skyeapi/mtn_topup_engine.php?destmsisdn=$paramDestMsisdn&amount=$paramAmount&sdesc=$paramDescription");
        #$sqlu = db_execute($res);        
        header('Content-Type: application/json'); 
        #echo json_encode($res);
        echo $res;
    } else {
        $response = array(
            'response' => 'Wrong Username/password Combination:',
            'report' => 'try again with the correct combination' );
        header('Content-Type: application/json'); 
        echo json_encode($response);    
    }
        
}

function addAgent() {
    global $app;
    $req = $app->request(); // Getting parameter with names
    $paramUserName = $req->get('username'); // Getting parameter with names
    $paramEmail = $req->get('email'); // Getting parameter with names
    $paramPassword = $req->get('password');
    $paramDescription = $req->get('sdescription');
    
    $log_req = "insert into topup_agent (username,email,password,service_description) values ('$paramUserName','$paramEmail','$paramPassword','$paramDescription' )";
    
    $sql = db_execute($log_req);
    if($sql)    {
        $response = array(
            'userdata' => 'Account Created Successfully:',
            'username' => $paramUserName,
            'email' =>  $paramEmail,
            'password' => $paramPassword,
            'description' => $paramDescription);
        #echo '{"success": ' . json_encode($sql) . '}';  
    } else {
        $response = array(
            'userdata' => 'Account Creation Failed:',
            'report' => 'Try Again Later' );
        #echo '{"failure":{"text":'. Failed .'}}';
    }
    header('Content-Type: application/json'); 
    echo json_encode($response);    
}

function updateUser($id) {
    global $app;
    $req = $app->request();
    $paramName = $req->params('name');
    $paramEmail = $req->params('email');
    
    $sql = "UPDATE restAPI SET name=:name, email=:email WHERE id=:id";
    try {
        $dbCon = getConnection();
        $stmt = $dbCon->prepare($sql);  
        $stmt->bindParam("name", $paramName);
        $stmt->bindParam("email", $paramEmail);
        $stmt->bindParam("id", $id);
        $status->status = $stmt->execute();
        
        $dbCon = null;
        echo json_encode($status); 
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
}

function deleteUser($id) {
    $sql = "DELETE FROM restAPI WHERE id=:id";
    try {
        $dbCon = getConnection();
        $stmt = $dbCon->prepare($sql);  
        $stmt->bindParam("id", $id);
        $status->status = $stmt->execute();
        $dbCon = null;
        echo json_encode($status);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
}

function findByName($query) {       #function findByName($query) {
    $sql = "SELECT * FROM restAPI WHERE UPPER(name) LIKE :query ORDER BY name";
    #$sql = "SELECT * FROM restAPI WHERE UPPER(name) LIKE :query and email =:em ORDER BY name";
    try {
        $dbCon = getConnection();
        $stmt = $dbCon->prepare($sql);
        $query = "%".$query."%";
        #$em = "%".$em."%";
        $stmt->bindParam("query", $query);
        #$stmt->bindParam("em", $em);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_OBJ);
        $dbCon = null;
        echo '{"user": ' . json_encode($users) . '}';
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
}

function getConnection() {
    try {
        $db_username = "sandbox";
        $db_password = "sandbox";
        $conn = new PDO('mysql:host=localhost;dbname=slimapi', $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
    } catch(PDOException $e) {
        echo 'ERROR: ' . $e->getMessage();
    }
    return $conn;
}


?>