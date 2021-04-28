<?php

define('DOKU_INC', realpath(dirname(__FILE__)) . '/../../../../');
require_once(DOKU_INC.'inc/init.php');
session_write_close();

class xcom_save  {
    private $helper;
    private $get_media_file = 'wiki.getAttachment';
    private $mediaArray;
    private $get_page = 'wiki.getPage';
    private $save_media_file = 'wiki.putAttachment';
    private $save_page = 'wiki.putPage';    
    private $remoteClient;
    private $localClient;    
    private $page;  
    private $data_buffer;    
    private $user;
   
    function __construct($local_auth,$remote_auth,$page) {
        $err = "";
        $this->helper =  plugin_load('helper', 'xcom');
        
        $this->page = $page;
       
        if(!$this->page) {
            $this->msg('nopage');            
            exit;
        }
        $secs =  25;     
        while(!($this->localClient = $this->ini_clients($local_auth,true))) {
            if((time() - $time_start ) > $secs ) {        
            break;
            }
            usleep(50);
        }                
        if(!$this->localClient) {         
             $err .= $this->msg('nolocal',1);
        }
        $time_start = time();
        while(!($this->remoteClient = $this->ini_clients($remote_auth))) {
            if((time() - $time_start ) > $secs ) {        
            break;
            }
            usleep(50);
        }
        if(!$this->remoteClient) {           
            $err .= $this->msg('noremote',1);
        }
        if($err) {                
           $err .= $this->msg('chkauth',1);
           echo "$err\n";
           exit;
        }        
      
      $this->msg('success');
  
       /*possible to query and save a single media id instead of a page id */  
       if($this->is_media_id($page)) {
           $this->getMediaFile($page);
           $this->logoff();
           exit;
       }

    }
 
    function msg($which, $ret=false, $nl="\n") {
       if($ret) return $this->helper->getLang($which) . $nl;
       echo $this->helper->getLang($which) . $nl;
    }
    
    function ini_clients($credentials,$local=false) {
        if(is_string($credentials)) {
            $credentials = json_decode($credentials);
            if($credentials instanceof stdClass) {
                if($local) $this->user = $credentials->user;
                return $this->xcom_connect($credentials->url,$credentials->user,$credentials->pwd ,0);
            }            
       }
       if(is_array($credentials)) {
           if($local) $this->user = $credentials['user'];
           return $this->xcom_connect($credentials['url'],$credentials['user'],$credentials['pwd'] ,0);
       }
           return false;
    }
    function is_media_id($id) {     
       if(preg_match('/\.(\w{3,4})$/',$id,$matches)) {
          echo "$id ". $this->msg('ismedia',1," " . $matches[1] . "\n");
    
          
          return true;          
       }   
       return false;
       
    }
    function processMediaArray() {        
        if(!$this->mediaArray)  $this->getMedia();
        if(!is_array($this->mediaArray)) {
           $this->msg('nomedia',0, " $this->page.\n");
           return;
        }       
        $this->msg('reqmedia');
        foreach($this->mediaArray as $mfile) {
            $this->getMediaFile($mfile);
        }
    }    
    
    function getMediaFile($mfile) {
        $this->data_buffer = "";
        $this->xcom_get_data( 'wiki.getAttachment',$this->remoteClient,true, array($mfile));      
        if($this->data_buffer) { 
          if(is_array($this->data_buffer)) {
              echo print_r($this->data_buffer,true);
            }          
           else {              
                echo "$mfile " . $this->msg('fsize',true,"") . " " . strlen($this->data_buffer) ."\n";        
                $this->saveMediaFile($mfile);
          }
        }
        echo "\n";
    }
   

    function getPage() {        
        $auth = $this->xcom_get_data( 'wiki.aclCheck',$this->localClient,false,array($this->page));    
        
        if($auth < 4) {
            $this->msg('noperm',false," $this->page\n");        
            $this->logoff();
            exit;
        }
        $info = $this->xcom_get_data( 'plugin.xcom.getPageInfo',$this->remoteClient,false,array($this->page));    
           
        if(!is_array($info)) {         
            $this->msg('notonremote',false," $this->page\n");
            $this->logoff();
             exit;
        }
          
        $this->data_buffer = "";
        $this->xcom_get_data( 'wiki.getPage',$this->remoteClient,true, array($this->page));              
         if(is_array($this->data_buffer)) {
              echo print_r($this->data_buffer,true);
              exit; 
         }       
         usleep(100);
        $this->savePage();
    }
    function savePage() {
        $resp = $this->xcom_get_data( 'wiki.putPage',$this->localClient,false, array($this->page,$this->data_buffer,array('sum'=>'imported')));
 
       if(!$resp) {
            $this->msg('noimport',false," $this->page\n");        
            $this->logoff();
            exit;
        }
        $this->msg('imported',false," $this->page\n");        
        
    }
    
    function getMedia() {
       $this->mediaArray=$this->xcom_get_data( 'plugin.xcom.getMedia',$this->remoteClient);        
    }
    
    private  function xcom_get_data($task,$client,$use_buffer=false, $params="") {     
 
         if(!$params) {
              if($params === false) {
                  $params = array($task);  
              }
              else $params = array($task,$this->page);                
         }
         else {
           array_unshift($params,$task);
         }
         
           $secs =  5; 
           $time_start = time();
           while(!($resp = call_user_func_array(array($client,"query"),$params))){
           if($resp)  echo "resp=$resp\n";
            if((time() - $time_start ) > $secs ) {        
            break;
            }
           usleep(50);
        }
  
            if($use_buffer) {
                $this->data_buffer = $client->getResponse();
                return;
             }

            return $client->getResponse();
     }  
        
    function saveMediaFile($id) {    
       $auth = $this->xcom_get_data( 'wiki.aclCheck',$this->localClient,false,array($id));    
       
        if($auth < 8) {    
            $this->msg('uploadperm',false," $id\n");  
            return;
        }
        
        global $conf;
        $ftmp = $conf['tmpdir'] . '/' . md5($id.clientIP());       
        // save temporary file
        @unlink($ftmp);
        io_saveFile($ftmp, $this->data_buffer);
        $this->media_save($ftmp,$id,$auth);
  }

   function media_save($file_name,$id,$auth=255) {    
       $file = array('name'=>$file_name);
       $ow = false;
       $move='rename';
      
       $res = media_save($file, $id, $ow, $auth, $move) ;
       if(is_array($res)) {
         print_r($res);
       }
       else  $this->msg('msave',false," $id\n");
 }
    
    function xcom_connect($url,$user,$pwd, $debug=false) {
            $url = rtrim($url,'/') . '/lib/exe/xmlrpc.php';
            $client = new IXR_Client($url);
            $client->debug = $debug; // enable for debugging
            
            $resp = $client->query('dokuwiki.login',$user,$pwd);            
            $ok = $client->getResponse();
             
            if($ok) return $client;
            return false;
    }
    function logoff() { 

        $this->msg('logoff');
        $resp =$this->xcom_get_data( 'dokuwiki.getVersion',$this->localClient,false,false);   
        $this->msg('localdw', false," $resp\n");
        preg_match('/(\d+)-\d+-\d+/',$resp,$matches);        
        if($matches[1] >= 2014) {             
             $this->msg('logoff',false, " $resp\n");
             $this->xcom_get_data( 'dokuwiki.logoff',$this->localClient,false,false);
        }
        else  $this->msg('nologoff');     
        
       $resp =$this->xcom_get_data( 'dokuwiki.getVersion',$this->remoteClient,false,false);
       $this->msg('remotedw', false," $resp\n");        
        preg_match('/(\d+)-\d+-\d+/',$resp,$matches);        
        if($matches[1] >= 2014) {             
             $this->msg('logoff',false, " $resp\n");
             $this->xcom_get_data( 'dokuwiki.logoff',$this->remoteClient,false,false);
        }
        else $this->msg('nologoff');
    }
}
global $INPUT;

$xcom=new xcom_save($INPUT->post->str('local'),$INPUT->post->str('remote'),$INPUT->post->str('id'));
$xcom->getPage();
$xcom->getMedia() ;
$xcom->processMediaArray();
$xcom->logoff();
echo "\n";
flush();
