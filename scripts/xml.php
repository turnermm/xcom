<?php
define('DOKU_INC', realpath(dirname(__FILE__)) . '/../../../../');
require_once(DOKU_INC.'inc/init.php');
session_write_close();
$helper =  plugin_load('helper', 'xcom');
$credentials = json_decode($_REQUEST['credentials']);
$url = rtrim ($credentials->url,'/') . '/';



$params = json_decode($_REQUEST['params']);
$client = xcom_connect($url,$credentials->user,$credentials->pwd ,0);

$secs = 15;
$fn = $params[0] ;
    
if($client)
{

   if($fn =='wiki.putPage' || $fn=='dokuwiki.appendPage') {
        if(!xcom_lock($params[1], true, $client)) {
           echo "Lock failed\n";
           exit;
        }
    }    

   $array_types = array('dokuwiki.getPagelist','wiki.getPageVersions','wiki.getPageInfo','wiki.getAllPages', 'wiki.getAttachments','dokuwiki.search','plugin.xcom.getMedia');
   $time_start = time();   
   $resp = "";
   
    while(!($resp = call_user_func_array(array($client,"query"),$params))){       
        if((time() - $time_start ) > $secs ) {        
        break;
        }
       usleep(50);
   } 
  
 
   $retv = $client->getResponse();
       if($fn =='wiki.putPage' || $fn=='dokuwiki.appendPage') {
         $retv = "retv: $retv resp: $resp";
       }
   if($fn =='wiki.putPage' || $fn=='dokuwiki.appendPage') {
        xcom_lock($params[1], false, $client);
   }   
   
   if(!$retv) {
     $retv = $helper->getLang('timedout');
   }
   elseif(is_array($retv)) { 
      if(in_array($fn,$array_types) && !$retv['faultCode'] && !$retv['faultString']) {  
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

    echo  $helper->getLang('noconnection') . "\n";
}

function xcom_connect($url,$user,$pwd, $debug=false) {
    $url = rtrim($url,'/') . '/lib/exe/xmlrpc.php';
    $client = new IXR_Client($url);
    $client->debug = $debug; // enable for debugging
    $client->query('dokuwiki.login',$user,$pwd);
    $ok = $client->getResponse();
    if($ok) return $client;
    return false;

}

function xcom_lock($page, $lock, $client) { 
 global $secs;

 $locks = array('lock'=>array(), 'unlock'=>array()) ;
 if($lock) {
   $locks['lock'][] = $page;
   echo "locking $page\n";
 }
 else {
 echo "unlocking\n";
       $locks['unlock'][] = $page;
 }
   $time_start = time();   
   while(!$client->query('dokuwiki.setLocks',$locks)) {
      if((time() - $time_start ) > $secs ) {        
        break;
       }
      usleep(50);
   }   
   
  $data = $client->getResponse();  
  
  if(in_array($page,$data['locked'])) {   
     return true;  
   } 
  if(in_array($page,$data['unlocked'])) { 
     return true;  
   } 

   return false;

}