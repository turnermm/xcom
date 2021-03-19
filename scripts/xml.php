<?php
define('DOKU_INC', realpath(dirname(__FILE__)) . '/../../../../');
require_once(DOKU_INC.'inc/init.php');
session_write_close();
$helper =  plugin_load('helper', 'xcom');
$credentials = json_decode($_REQUEST['credentials']);
$url = rtrim ($credentials->url,'/') . '/';



$params = json_decode($_REQUEST['params']);
if($_REQUEST['debug'] == 'false'){
    $debug = 0;    
}   
elseif ($_REQUEST['debug'] == 'true') {
    $debug = 1;
}

$client = xcom_connect($url,$credentials->user,$credentials->pwd ,$debug);
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

   $array_types = array('dokuwiki.getPagelist','wiki.getPageVersions','wiki.getPageInfo','wiki.getAllPages','wiki.getAttachmentInfo','wiki.getAttachments', 'wiki.getRecentChanges', 'wiki.listLinks','dokuwiki.search','plugin.xcom.getMedia', 'plugin.xcom.listNamespaces');
   $time_start = time();   
   $resp = "";
   
   if($fn == 'plugin.xcom.listNamespaces') {
       for($p=0; $p<count($params);$p++) {      
            if(is_array($params[$p])) {
               $params[$p] = json_encode($params[$p]);       
            }
       }
   }
   
    if($fn == 'dokuwiki.getPagelist') {
       for($p=0; $p<count($params);$p++) {      
            if(is_array($params[$p])) {                   
                    $elems = $params[$p];
                    foreach($elems as $el) {
                        if(strpos($el,':') !== false) {
                            list($key,$val) = explode (':' , $el);
                            $key = trim($key); $val = trim($val);
                            $ar[$key] = ($val+=1);                             
                            $params[$p] = $ar;            
                            break;                            
                        }                                         
                    }                        
                }
            }
       }
 
   
    while(!($resp = call_user_func_array(array($client,"query"),$params))){       
        if((time() - $time_start ) > $secs ) {        
        break;
        }
       usleep(50);
   } 
  
 
   $retv = $client->getResponse();
    
       if($fn =='wiki.putPage' || $fn=='dokuwiki.appendPage') {
         $resp = print_r($resp,1);
         $_retv =print_r($retv,1);
         $retv = "retv: $_retv resp: $resp";
       }
   if($fn =='wiki.putPage' || $fn=='dokuwiki.appendPage') {
        xcom_lock($params[1], false, $client);
   }   
   
   if(!$retv) {
     $retv = $helper->getLang('timedout');
   }
   elseif(is_array($retv)) { 
      if(in_array($fn,$array_types) && !$retv['faultCode'] && !$retv['faultString']) {  
	   if($fn == 'wiki.getAttachmentInfo' && isset($params[1])) {
	      $retv = array_merge(array('id' => $params[1]), $retv); 
	  }
	  else 	if($fn == 'wiki.getPageVersions') {
          for($i=0; $i<count($retv);$i++) {             
              $retv[$i]['modified'] =  get_ixrdate($retv[$i]['modified']);	        
          }
	  }	
     elseif($fn == 'wiki.getPageInfo') {
		  $retv['lastModified'] =  get_ixrdate($retv['lastModified']);
		 // file_put_contents("debugbde.txt",print_r($retv['lastodified'],true));
	}
       $retv = json_encode($retv);
       echo $retv;
       exit;
    }
    else  {     
      $temp = print_r($retv,true);
      $retv = rawurlencode($temp);
      }
   }
   else {
      
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
function get_ixrdate($text) {
    static $i = 0;
    $i++;    
   $text = print_r($text,1); 
 
	$text = preg_replace_callback(           
    "/^([\s\S]+)\[date\]\s*=>\s*(\d{4,}[\s\d\-\.\:]+)(([\s\S]+))$/ms",
    function ($matches) {      ;
         return  $matches[2];
		},
	   $text
	);

   $text = preg_replace("/000\s*$/","",$text);
   return $text;
 }