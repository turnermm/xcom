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
    private $temp_dir;
    private $user;
   
    function __construct($local_auth,$remote_auth,$page) {
        $err = "";
        $this->helper =  plugin_load('helper', 'xcom');
        $this->page = $page;
        $this->localClient = $this->ini_clients($local_auth,true);
        $this->remoteClient = $this->ini_clients($remote_auth);
        if(!$this->localClient) {
            $err .= "Unable to log into local server.\n";
        }
        if(!$this->remoteClient) {
            $err .= "Unable to log into remote server.\n";
        }
        if($err) {
           $err .="Please check your authorization credentials\n"; 
           exit;
        }        
        $this->temp_dir = realpath(DOKU_INC . 'data/tmp/') . '/';        
        echo "success\n";        
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
    
    function processMediaArray() {        
        if(!$this->mediaArray)  $this->getMedia();
        if(!is_array($this->mediaArray)) {
            echo $this->mediaArray . "\n";
            exit;
        }
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
                 echo "file size: " . strlen($this->data_buffer) ."\n";        
                $this->saveMediaFile($mfile);
          }
        }
    }
   

    function getPage() {
    }
    function savePage() {
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
 
           while(!($resp = call_user_func_array(array($client,"query"),$params))){
            echo "resp=$resp\n";
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
  //     echo "Auth $auth "."\n";
        if($auth < 16) return;
        
        global $conf;
        $ftmp = $conf['tmpdir'] . '/' . md5($id.clientIP());       
        // save temporary file
        @unlink($ftmp);
        io_saveFile($ftmp, $this->data_buffer);
        $this->media_save($ftmp,$id);
  }

   function media_save($file_name,$id) {
    // echo "$file_name\n";
     $file = array('name'=>$file_name);
     $ow =1; // false;
     $move='rename';
      $res = media_save($file, $id, $ow, 16, $move) ;
 }
    
    function xcom_connect($url,$user,$pwd, $debug=false) {
            $url = rtrim($url,'/') . '/lib/exe/xmlrpc.php';
            $client = new IXR_Client($url);
            $client->debug = $debug; // enable for debugging
            $ok = $client->query('dokuwiki.login',$user,$pwd);
            if($ok) return $client;
            return false;
    }
    function logoff() { 
     
        $resp =$this->xcom_get_data( 'dokuwiki.getVersion',$this->localClient,false,false);
        echo "\nLocal Dokuwiki version= $resp\n";     
        preg_match('/(\d+)-\d+-\d+/',$resp,$matches);        
        if($matches[1] >= 2014) {
             echo "Logging off: $resp\n";
             $this->xcom_get_data( 'dokuwiki.logoff',$this->localClient,false,false);
        }
        
       $resp =$this->xcom_get_data( 'dokuwiki.getVersion',$this->remoteClient,false,false);
        echo "Remote Dokuwiki version=$resp\n";     
        preg_match('/(\d+)-\d+-\d+/',$resp,$matches);        
        if($matches[1] >= 2014) {
             echo "Logging off: $resp\n";
             $this->xcom_get_data( 'dokuwiki.logoff',$this->remoteClient,false,false);
        }
    }
}

$local=array('url'=>'http://192.168.0.77/binky','user'=>'tower','pwd'=>'mike35tu'); 
$remote=array('url'=>'http://192.168.0.77/adora','user'=>'tower','pwd'=>'mike35tu'); 
$xcom=new xcom_save(json_encode($local),json_encode($remote),'start');
$xcom->getMedia() ;
$xcom->processMediaArray();
$xcom-> logoff();
echo "\n";