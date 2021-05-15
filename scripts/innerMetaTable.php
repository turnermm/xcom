<?php
if(!defined('DOKU_INC')) {
    define('DOKU_INC', realpath(dirname(__FILE__)) . '/../../../../');
}
require_once(DOKU_INC.'inc/init.php');
global $xcom_timezone, $xcom_current,$xcom_prefix,$conf;
define ('PAGES', realpath(DOKU_INC . $conf['savedir']));
$xcom_timezone = 'UTC'; // default timezone is set to Coordinated Univeral Time. You can reset your timezone here

function xcom_GetMetaData($id) {
    global $xcom_timezone, $xcom_current,$xcom_prefix,$conf;
    $contents="";
    date_default_timezone_set($xcom_timezone); 
    $xcom_prefix = preg_replace("/.*?\/data\/meta/", "", $conf['metadir']);
    $xcom_prefix = ($depth = str_replace('/', ':', $xcom_prefix)) ? $depth : '';
    if($id === ':' || preg_match("/\:\*$/",$id)) {        
        $id = rtrim($id,':*');
        $ns =  $conf['metadir'] . $id;  
        chdir($ns);    
        //ob_start();
        recurse('.',$contents);
    }
    else {
      
        $file = metaFN($id,'.meta');
        get_data($file,$id,$contents);
    }


    $contents = str_replace("<table.*?>\n</table>","",$contents);
 

}


function recurse($dir,&$contents) {
    global $xcom_prefix;
    $dh = opendir($dir);
    if (!$dh) return;
    $cur_dir = '/pages' . preg_replace('#^.*?data/meta#',"", getcwd());   
    while (($file = readdir($dh)) !== false) {
        if ($file == '.' || $file == '..') continue;
        
        if (is_dir("$dir/$file")) {           
          recurse("$dir/$file",$contents);
         }
        if (preg_match("/\.meta$/", $file)) {          
            $store_name = preg_replace('/^\./', $xcom_prefix, "$dir/$file");    
            $id_name = PAGES ."$cur_dir${store_name}";
            $id_name = preg_replace('/\.meta$/', '.txt',$id_name);           
            get_data("$dir/$file",$id_name,$contents);
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
    $contents .= "\n" . '<table style="border-top:2px solid">' ."\n";
    $contents .= "<tr><td colspan='2'>$id_path</td></tr>\n";
    $contents .= "<tr><td colspan='2'>$file</td></tr>\n";
    $xcom_current = $data_array['current'];
    $keys =  array('title','date','creator','last_change','relation');
    foreach ($keys AS $header) {
        switch($header) {
            case 'title':               
                 $title = getcurrent($header, null);
                 $contents .= "<tr><td colspan='2'>Title: <b>$title</b></td></tr>\n";
                 break;                     
                
            case 'date':                        
                 process_dates(getcurrent('date', 'created'),getcurrent('date', 'modified'),$contents);  
                 break;                 
            case 'user':
                if($creator || $creator_id) break; 
            case 'creator':
                $creator = getcurrent('creator', null);
                $creator_id = getcurrent('user', null);
                process_users($creator,$creator_id,$contents);  
                 break;
           
            case 'last_change':                                           
                $last_change = getSimpleKeyValue(getcurrent($header, null),"last_change",$contents);
                 if($last_change) {
                    $contents .= "<tr><td colspan='2'>Last Change</td>\n"; 
                    $contents .= "<td>$last_change</td></tr>\n"; 
                }
                break;              
            case 'contributor':       
                 $contributors = getSimpleKeyValue(getcurrent($header, null),$contents);
                 break;   
            case 'relation':                
                $isreferencedby = getcurrent($header,'isreferencedby');
                $references = getcurrent($header,'references');
                $media = getcurrent($header,'media');
                $firstimage = getcurrent($header,'firstimage');
                $haspart = getcurrent($header,'haspart');
                $subject = getcurrent($header,'subject');
                process_relation($isreferencedby,$references,$media,$firstimage,$haspart,$subject,$contents);
                break;
            default:

                 break;
            }

        }  
       $contents .= "\n</table>\n";
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
    foreach ($ar As $key=>$val) {       
        if(!empty($val)) {           
           if($which == 'last_change')  {  
               if($key == 'date') {
                   $val = date("r", $val);
                }
                if($key == 'type')  {
                    $val = $types[$val];  
                }
           }

           $retv .= "<tr><td>$key:</td><td>$val</td></tr>\n";
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
    if($list) $contents .= "<tr><td>$type</td><td>$list</td></tr>\n";
}
function process_relation($isreferencedby,$references,$media,$firstimage,$haspart,$subject,&$contents) {
  
    if(!empty($isreferencedby)) {         
        $list = create_list(array_keys($isreferencedby),$contents);
        insertListInTable($list,'Backlinks',$contents);
    }
    if(!empty($references)) {           
       $list = create_list(array_keys($references),$contents);
       insertListInTable($list,'Links',$contents);           
    }
    if(!empty($media)) {          
       $list = create_list(array_keys($media),$contents);
       insertListInTable($list,'Media',$contents);           
    }
    if(!empty($firstimage)) {
       $contents .= "<tr><td>First Image</td><td colspan='2'>$firstimage</td></tr>";      
    }   
    if(!empty($haspart)) {      
       $list = create_list(array_keys($haspart),$contents); 
       insertListInTable($list,'haspart',$contents);
    }  
    if(!empty($subject)) {
       $list = create_list(array_keys($subject),$contents);
       insertListInTable($list,'Subject',$contents);
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
//echo xcom_GetMetaData(':*');