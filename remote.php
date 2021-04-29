<?php 
//use dokuwiki;
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
include_once(DOKU_INC . 'inc/init.php');
if(file_exists(DOKU_INC . 'inc/Remote/Api.php')) {
require_once(DOKU_INC . 'inc/Remote/ApiCore.php'); 
require_once(DOKU_INC . 'inc/Remote/Api.php');
}
else {
    require_once(DOKU_INC . 'inc/remote.php'); 
    require_once(DOKU_INC . 'inc/RemoteAPICore.php');
}    

class remote_plugin_xcom extends DokuWiki_Remote_Plugin {
    private $api;
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
            'pageVersions' => array(
                'args' => array('string','int'),
                'return' => 'array',
                'doc' => 'returns list of page versions'
            ),             
           'getPageInfo' => array(
                'args' => array('string'),
                'return' => 'array',
                'doc' => 'Returns a struct with info about the page, latest version.',
                'name' => 'pageInfo'
            ),            
        );
    }
     
     function  __construct() {
          $iswin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
          if(!defined('DIRECTORY_SEPARATOR')) {
             $iswin ? define("DIRECTORY_SEPARATOR", "\\") : define("DIREC TORY_SEPARATOR", "/");
           }
         if(class_exists ('Api')) {           
           $this->api = new ApiCore(new Api());
         }
         else $this->api = new RemoteAPICore(new RemoteApi());
     }     
    public function getTime($a) {  
        return date("Y-m-d",$a);
       // if($a[0]) return strftime('%Y. %B %d. %A', $this->getApi()->toDate(time()));
      //  return $this->getApi()->toDate(time());
    }
    
    
     public function listNamespaces($namespace="",$mask="") {  
      global $conf;       
       $rootns =  $conf['savedir'];
       $rootns = ltrim($rootns,'./');
       if($rootns == 'data') {
           $rootns = DOKU_INC . $rootns;
       }
     
      if(!$namespace) {
        $namespace = $rootns; 
      }
       else $namespace = $rootns . '/pages/'. $namespace;      
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
     
     $regex  = '#' . preg_quote($rootns) .'#';  

    for($i=0;$i<count($result); $i++) {
          $result[$i] = preg_replace($regex,"",$result[$i]);
          $result[$i] = preg_replace("/\/?pages/","",$result[$i]);
          $result[$i] = str_replace('/',':',$result[$i]);
          
   }
      return $result;

    
    }    
    
  /**
    *    Based on  find_all_files() by kodlee at kodleeshare dot net 
    *         at  http://ca3.php.net/scandir: 
   */
  function find_all_files($dir,$regex="")
  {     
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
    
          /**
     * Returns a list of available revisions of a given wiki page
     * Number of returned pages is set by $conf['recent']
     * However not accessible pages are skipped, so less than $conf['recent'] could be returned
     *
     * @author Michael Klier <chi@chimeric.de>
     *
     * @param string $id page id
     * @param int $first skip the first n changelog lines
     *                      0 = from current(if exists)
     *                      1 = from 1st old rev
     *                      2 = from 2nd old rev, etc
     * @return array
     */
    public function pageVersions($id, $first = 0)
    {
        header("Access-Control-Allow-Origin: *");
        return  json_encode($this->api->pageVersions($id, $first));
}



    
        /**
     * Return some basic data about a page
     *
     * @param string $id page id
     * @param string|int $rev revision timestamp or empty string
     * @return array
     * @throws AccessDeniedException no access for page
     * @throws RemoteException page not exist
     */
    public function pageInfo($id, $rev = '') {
       return json_encode($this->api-> pageInfo($id, $rev = ''));   
    }
    
    private function resolvePageId($id)
    {
        $id = cleanID($id);
        if (empty($id)) {
            global $conf;
            $id = cleanID($conf['start']);
        }
        return $id;
    }
  
}

//$rem = new remote_plugin_xcom();
//print_r($rem->pageVersions('start'));
