<?php 
class remote_plugin_xcom extends DokuWiki_Remote_Plugin {
    
    public function _getMethods() {
        return array(
            'getTime' => array(
                'args' => array('int'),
                'return' => 'date'
            ),
            'getMedia' => array(
                'args' => array('string','string'),
               'return' => 'array',
               'doc' => 'returns list of media in page id named in args1, args 2 is optional namespace'
            ), 
            'listNamespaces' => array(
                'args' => array('string','array'),
                'return' => 'array',
                'doc' => 'returns list of wiki namespaces'
            ),             
        );
    }
     
     function  __construct() {
          $iswin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
          if(!defined('DIRECTORY_SEPARATOR')) {
             $iswin ? define("DIRECTORY_SEPARATOR", "\\") : define("DIREC TORY_SEPARATOR", "/");
           }
     }     
    public function getTime($a) {  
        if($a[0]) return strftime('%Y. %B %d. %A', $this->getApi()->toDate(time()));
        return $this->getApi()->toDate(time());
    }
    
    
    
         public function listNamespaces($namespace="",$mask="") {  
      global $conf;       
     
      if(!$namespace) {
        $namespace = $conf['mediadir']; 
      }
       else $namespace = $conf['mediadir'] . '/'. $namespace;
      
      $namespace = rtrim($namespace, '/');
      $folder_list = array();  
       
    $regex='';
    $mask = trim($mask);
    if($mask) {
        $mask= json_decode($mask);
        for($i=0; $i<count($mask) ;$i++) {
            $mask[$i] = preg_quote($mask[$i]);
        }
        if(count($mask) > 1) {    
           $regex =  implode('|',$mask);      
        }
         else if(is_array($mask)) {
            $regex = $mask[0];
         }
         else $regex = $mask;    
        $regex =  "($regex)\b";
   }
   
     $result =$this->find_all_files($namespace,$regex);
     
     $regex  = '#' . preg_quote($conf['mediadir']) .'#';  

    for($i=0;$i<count($result); $i++) {
          $result[$i] = preg_replace($regex,"",$result[$i]);
         $result[$i] = str_replace('/',':',$result[$i]);
          
   }
      return $result;

    
    }    
    
  /**
    /*    Based on  find_all_files() by kodlee at kodleeshare dot net 
    /*         at  http://ca3.php.net/scandir: 
   */
  function find_all_files($dir,$regex="")
  {
     global $conf; 
    $root = scandir($dir);
  
    
   foreach($root as $value)
    {
        if($value === '.' || $value === '..') {continue;}
         if($regex)  if(preg_match('#'. $regex .'#',"$dir/$value")) {continue;}                  
         if(is_dir("$dir/$value") && is_readable("$dir/$value")) {              
                $result[]="$dir/$value";                  
                foreach($this->find_all_files("$dir/$value",$regex) as $value)                 
                {      if(!$regex) {                            
                             $result[]="$value";                                                        
                         }    
                         else  if(! preg_match('#'. $regex .'#',"$dir/$value")) {                            
                            $result[]="$value";
                        }
                       
                }
           }
    }
      if(isset($result)) return $result;
      return array(); 
  } 
    
    public function getMedia($id,$namespace="") {  
          if($namespace) {
              $id = "$namespace:$id";
          }
          $path =  metaFN($id,'.meta');
           
  
          if(@file_exists($path)) {
              $inf_str = file_get_contents($path);
              $inf = @unserialize($inf_str);         
              if($inf['current']['relation']['media']) {
                   return array_keys($inf['current']['relation']['media']);                         
              }
              
              $filename = wikiFN($id);
              if(@file_exists($filename)) {
                 $str = file_get_contents($filename );
                 if(strpos($str,'{{') === false) return "0";
                 preg_match_all('/{{(.*?)}}/ms',$str,$matches);
                 $media = array();
                 foreach($matches[1] as $file) {
                    $result = explode('|', $file);
                    $result = explode('?',$result[0]);
                    $result = trim($result[0]);
                    if(strpos($result,'http://')=== false && strpos($result,'>') === false ){
                        $media[$result] = 1;
                    }
                }
                $media = array_keys($media); 
                if(!empty($media) ) {
                    return $media;
                }
              }
              
              return "no media data in $path";             
          }       
          return "no data for $id";
    }
}