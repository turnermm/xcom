<?php
require_once('inc/init.php');

 $client = new IXR_Client('http://192.168.0.77/adora/lib/exe/xmlrpc.php');
 $ok = $client->query('dokuwiki.login','tower','mike35tu');
 //$client->debug = 1; 

if($ok) {  
   	while(!$client->query('wiki.getAttachment','current:jenny.png'));
    $data = $client->getResponse();
    save_img('jenny.png',$data); 
     exit;
}

  function save_img($file,$data) {
      $fp = @fopen($file,'wb');
      if($fp === false) {       
        echo "failed to open\n";
           return;
       }
      if(!fwrite($fp,$data)) {
         echo "failed to write\n";         
      }
      fclose($fp); 
  }

function _media_save() {
    $auth=255;
    $id = 'current:jenny.png';
    $file_name =DOKU_INC . 'jenny.png';
    $file = array('name'=>$file_name);
    $ow = false;
    $move='copy';
     media_save($file, $id, $ow, $auth, $move) ;
 }
 
 