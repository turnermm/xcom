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
   $array_types = array('dokuwiki.getPagelist','wiki.getPageVersions');
   $time_start = time();   
    while(!call_user_func_array(array($client,"query"),$params)) {       
        if((time() - $time_start ) > 20 ) {        
        break;
        }
       usleep(50);
   } 
  
   $retv = $client->getResponse();
   $fn = $params[0] ;
   if(!$retv) {
     $retv = "Query timed out\n";
   }
   elseif(is_array($retv)) { 
    //if($fn == 'dokuwiki.getPagelist' ||  $fn == 'wiki.getPageVersions') {
      if(in_array($fn,$array_types)) {  
       $retv = json_encode($retv);
       echo $retv;
       exit;
    }
    else  {
     $temp = print_r($retv,true);
      //file_put_contents(DOKU_INC . 'tempxmlrpc.txt',$temp);
      $retv = rawurlencode($temp);
      }
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

