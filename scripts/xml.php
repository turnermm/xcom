<?php
define('DOKU_INC', realpath(dirname(__FILE__)) . '/../../../../');
require_once(DOKU_INC.'inc/init.php');

$credentials = json_decode($_REQUEST['credentials']);
$url = rtrim ($credentials->url,'/') . '/';
//$page = array('lock'=>array('entities'), 'unlock'=>array());

$params = json_decode($_REQUEST['params']);
$client = xcom_connect($url,$credentials->user,$credentials->pwd ,0);

if($client)
{
   $time_start = time();   
    while(!call_user_func_array(array($client,"query"),$params)) {       
        if((time() - $time_start ) > 6) {        
        break;
        }
       usleep(50);
   } 
  
   $retv = $client->getResponse();
   if(!$retv) {
     $retv = "No page found\n";
   }
   elseif(is_array($retv)) { 
     $temp = print_r($retv,true);
      //file_put_contents(DOKU_INC . 'tempxmlrpc.txt',$temp);
      $retv = rawurlencode($temp);
   }
   else {
      $retv=rawurlencode($retv);
   }
   echo $retv;
  
   echo "\n";

}
else {

}

function xcom_connect($url,$user,$pwd, $debug=false) {
    $url = trim($url,'/') . '/lib/exe/xmlrpc.php';
    $client = new IXR_Client($url);
    $client->debug = $debug; // enable for debugging
    $ok = $client->query('dokuwiki.login',$user,$pwd);
    if($ok) return $client;
    return false;

}

