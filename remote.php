<?php 

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
include_once(DOKU_INC . 'inc/init.php');
if(file_exists(DOKU_INC . 'inc/Remote/Api.php')) {
require_once(DOKU_INC . 'inc/Remote/ApiCore.php'); 
require_once(DOKU_INC . 'inc/Remote/Api.php');
require_once(DOKU_INC . 'inc/IXR_Library.php');
}
else {
    require_once(DOKU_INC . 'inc/remote.php'); 
    require_once(DOKU_INC . 'inc/RemoteAPICore.php');
}    
//require_once('./scripts/createMetaTable.php');
global $xcom_timezone, $xcom_current,$xcom_prefix,$conf;
define ('PAGES', realpath(DOKU_INC . $conf['savedir']));
$xcom_timezone = 'UTC'; // default timezone is set to Coordinated Univeral Time. You can reset your timezone here
class remote_plugin_xcom extends DokuWiki_Remote_Plugin {
    private $api, $server;
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
           ' GetMetaData' => array(
                'args' => 'string',
                'return' => 'string',
                'doc' => 'Returns metadata of one or more wiki pages',
                'name' => 'GetMetaData'
            ),          
        );
    }
     
     function  __construct() {
          $iswin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
          if(!defined('DIRECTORY_SEPARATOR')) {
             $iswin ? define("DIRECTORY_SEPARATOR", "\\") : define("DIREC TORY_SEPARATOR", "/");
           }
          if(class_exists("dokuwiki\Remote\APICore")) {
              $this->api = new dokuwiki\Remote\APICore(new dokuwiki\Remote\Api());
         }
         else $this->api = new RemoteAPICore(new RemoteApi());
     }     
    public function getTime($a) {  
        //return date("Y-m-d s",$a); 
        return date("Y-m-d H:i:s",$a); 
        
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
       $info =  $this->api-> pageInfo($id, $rev = '');
       $info['lastModified'] = $this->getTime($info['lastModified']);         
       return json_encode($info);     
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
  
   function GetMetaData($id) {
    global $xcom_timezone, $xcom_current,$xcom_prefix,$conf;
    $contents="";
    date_default_timezone_set($xcom_timezone); 
    $xcom_prefix = preg_replace("/.*?\/data\/meta/", "", $conf['metadir']);
    $xcom_prefix = ($depth = str_replace('/', ':', $xcom_prefix)) ? $depth : '';
    if($id === ':' || preg_match("/\:\*$/",$id)) {        
        $id = rtrim($id,':*');
        $ns =  $conf['metadir'] .'/'. $id;  
        
        chdir($ns);           
        $this->recurse('.',$contents);
    }
    else {
      
        $file = metaFN($id,'.meta');
        $this->get_data($file,$id,$contents);
    }


    $contents = str_replace("<table.*?>\n</table>","",$contents);
     return $contents;

}


function recurse($dir,&$contents) {
    global $xcom_prefix;
    $dh = opendir($dir);
    if (!$dh) return;
    $cur_dir = '/pages' . preg_replace('#^.*?data/meta#',"", getcwd());   
    while (($file = readdir($dh)) !== false) {
        if ($file == '.' || $file == '..') continue;
        
        if (is_dir("$dir/$file")) {           
          $this->recurse("$dir/$file",$contents);
         }
        if (preg_match("/\.meta$/", $file)) {          
            $store_name = preg_replace('/^\./', $xcom_prefix, "$dir/$file");    
            $id_name = PAGES ."$cur_dir${store_name}";
            $id_name = preg_replace('/\.meta$/', '.txt',$id_name);           
            $this->get_data("$dir/$file",$id_name,$contents);
            $contents .= "\n";
        }
    }

    closedir($dh);
}
function get_data($file,$id_path,&$contents) {
    global $xcom_current;
    $data = file_get_contents($file);
    $data_array = @unserialize(file_get_contents($file));   
    $creator =""; $creator_id="";
  
    if ($data_array === false || !is_array($data_array)) return; 
    if (!isset($data_array['current'])) return;
      $contents .= "\n<p>\n";
    $contents .= "\n" . '<table style="border-top:2px solid">' ."\n";
    $contents .= "<tr><td colspan='2'>$id_path</td></tr>\n";
    $contents .= "<tr><td colspan='2'>$file</td></tr>\n";
    $xcom_current = $data_array['current'];
    $keys =  array('title','date','creator','last_change','relation');
    foreach ($keys AS $header) {
        switch($header) {
            case 'title':               
                 $title = $this->getcurrent($header, null);
                 $contents .= "<tr><td colspan='2'>Title: <b>$title</b></td></tr>\n";
                 break;                     
                
            case 'date':                        
                 $this->process_dates($this->getcurrent('date', 'created'),$this->getcurrent('date', 'modified'),$contents);  
                 break;                 
            case 'user':
                if($creator || $creator_id) break; 
            case 'creator':
                $creator = $this->getcurrent('creator', null);
                $creator_id = $this->getcurrent('user', null);
                $this->process_users($creator,$creator_id,$contents);  
                 break;
           
            case 'last_change':                                           
                $last_change = $this->getSimpleKeyValue($this->getcurrent($header, null),"last_change",$contents);
                 if($last_change) {
                    $contents .=  "<tr><td colspan='3'  ></td>\n";
                    $contents .= "<tr><td colspan='2' style='border-left: 2px solid #4169E1; color:#4169E1' ><b>Last Change</b></td>\n"; 
                    $contents .= "<td>$last_change</td></tr>\n"; 
                }
                break;              
            case 'contributor':       
                 $this->contributors = $this->getSimpleKeyValue($this->getcurrent($header, null),$contents);
                 break;   
            case 'relation':                
                $isreferencedby = $this->getcurrent($header,'isreferencedby');
                $references = $this->getcurrent($header,'references');
                $media = $this->getcurrent($header,'media');
                $firstimage = $this->getcurrent($header,'firstimage');
                $haspart = $this->getcurrent($header,'haspart');
                $subject = $this->getcurrent($header,'subject');
                $this->process_relation($isreferencedby,$references,$media,$firstimage,$haspart,$subject,$contents);
                break;
            default:

                 break;
            }

        }  
       $contents .= "\n</table></p>\n";
       $xcom_current = array();
}

/*
*  @param array $ar metadata field
*  @param string $which which field  
*/
function getSimpleKeyValue($ar,$which="",&$contents) {
    $retv = "";
    $types = array('C'=>'<u>C</u>reate','E'=>'<u>E</u>dit','e' =>'minor <u>e</u>dit','D'=>'<u>D</u>elete',
    'R'=>'<u>R</u>evert');
    if(!is_array($ar)) return false;         
    $border = "";    
    foreach ($ar As $key=>$val) {       
        if(!empty($val)) {           
           if($which == 'last_change')  {  
               $border = " style='border-left: 2px solid #4169E1;'"; 
               if($key == 'date') {
                   $val = date("r", $val);
                }
                if($key == 'type')  {
                    $val = $types[$val];  
                }
           }

          if(empty($val))  {
               $retv .= "<tr><td  $border>$key:</td><td>$val</td></tr>\n";
            }
            else  $retv .= "<tr><td $border>$key:</td><td>$val</td></tr>\n";
       }
    }
    return $retv;
}

function process_users($creator,$user,&$contents) {
        if(empty($creator)) {
            $contents .= "\n"; return;
         }
        $contents .= "<tr><td>Created by:</td><td> $creator (userid: $user)</tr></td>\n";
}

function process_dates($created, $modified,&$contents) {   
    $retv = "";

    if ($created) {
        $rfc_cr = date("r", $created);
        $contents .= "<tr><td>Date created:</td><td>".$rfc_cr.
        "</td><td>$created</td></tr>\n";
        }
   
    if ($modified) {
        $rfc_mod = date("r", $modified);
        $contents .= "<tr><td>Last modified:</td><td>" . $rfc_mod .
        "</td><td>$modified</td></tr>\n"; 
     }

}

function insertListInTable($list,$type,&$contents) {
     $border = " style='border-left: 2px solid green;'";
    if($list) $contents .= "<tr><td $border>$type</td><td>$list</td></tr>\n";
}
function process_relation($isreferencedby,$references,$media,$firstimage,$haspart,$subject,&$contents) {
      if(!empty($isreferencedby) || !empty($references) || !empty($media) || !empty($firstimage)
           && !empty($haspart ) &&!empty($subject)) {          
                $border = " style='border-left: 2px solid green;'";
                $contents .=  "<tr><td colspan='3'  ></td>\n";
                $contents .= "<tr><td colspan='2'  $border><b><span style='color:green'>Relation</span></b></td>\n";
           }
           else $border = "";
    if(!empty($isreferencedby)) {         
        $list =  $this->create_list(array_keys($isreferencedby),$contents);
        $this->insertListInTable($list,'Backlinks (isreferencedby)',$contents);
    }
    if(!empty($references)) {           
       $list =  $this->create_list(array_keys($references),$contents);
        $this->insertListInTable($list,'Links (references)',$contents);           
    }
    if(!empty($media)) {          
       $list =  $this->create_list(array_keys($media),$contents);
        $this->insertListInTable($list,'Media',$contents);           
    }
    if(!empty($firstimage)) {
       $contents .= "<tr><td   $border>First Image</td><td>$firstimage</td></tr>";      
    }   
    if(!empty($haspart)) {      
       $list =  $this->create_list(array_keys($haspart),$contents); 
        $this->insertListInTable($list,'haspart',$contents);
    }  
    if(!empty($subject)) {
       $list =  $this->create_list(array_keys($subject),$contents);
        $this->insertListInTable($list,'Subject',$contents);
    }       
 
}

function create_list($ar,&$contents) {
    $list = "\n<ol>\n";
    for($i=0; $i<count($ar); $i++) {
        $list .= '<li>'. $ar[$i] . "</li>\n";
    }
     $list .= "</ol>\n";
     return $list;
}   
function getcurrent($which, $other) {
    global $xcom_current;
    if (!isset($xcom_current)) return "";
    if ($other) {
        if (isset($xcom_current[$which][$other])) {
            return $xcom_current[$which][$other];
        }
    }
    if (isset($xcom_current[$which]) && $other === null) {
        return $xcom_current[$which];
    }
    return "";
}
}


