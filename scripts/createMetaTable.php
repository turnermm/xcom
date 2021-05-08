<?php
if(!defined('DOKU_INC')) {
    define('DOKU_INC', realpath(dirname(__FILE__)) . '/../../../../');
}
require_once(DOKU_INC.'inc/init.php');
global $timezone, $current,$prefix,$conf;
session_write_close();
define ('PAGES', realpath(DOKU_INC . $conf['savedir']));
$timezone = 'UTC'; // default timezone is set to Coordinated Univeral Time. You can reset your timezone here
date_default_timezone_set($timezone);
$prefix = preg_replace("/.*?\/data\/meta/", "", $conf['metadir']);
$prefix = ($depth = str_replace('/', ':', $prefix)) ? $depth : '';

function xcom_GetMetaData($id) {
    global $conf;
    if($id === ':' || preg_match("/\:\*$/",$id)) {        
    $id = rtrim($id,':*');
    $ns =  $conf['metadir'] . $id;  
    chdir($ns);

    ob_start();
    recurse('.');
    }
    else {
        ob_start();
        $file = metaFN($id,'.meta');
        get_data($file,$id);
    }
    $contents = ob_get_contents();
    ob_end_clean();
    $contents = str_replace("<table.*?>\n</table>","",$contents);
    return $contents;
}
function recurse($dir) {
    global $prefix;
    $dh = opendir($dir);
    if (!$dh) return;
    $cur_dir = '/pages' . preg_replace('#^.*?data/meta#',"", getcwd());   
    while (($file = readdir($dh)) !== false) {
        if ($file == '.' || $file == '..') continue;
        
        if (is_dir("$dir/$file")) {           
          recurse("$dir/$file");
         }
        if (preg_match("/\.meta$/", $file)) {            
            $store_name = preg_replace('/^\./', $prefix, "$dir/$file");
            $id_name = PAGES ."$cur_dir${store_name}";
            $id_name = preg_replace('/\.meta$/', '.txt',$id_name);                  
            get_data("$dir/$file",$id_name);
            echo "\n";
        }
    }

    closedir($dh);
}
function get_data($file,$id_path) {
    global $current;
    $data = file_get_contents($file);
    $data_array = @unserialize(file_get_contents($file));   
    $creator =""; $creator_id="";
  
    if ($data_array === false || !is_array($data_array)) return; 
    if (!isset($data_array['current'])) return;
    echo "\n" . '<table style="border-top:2px solid">' ."\n";
    echo "<tr><td colspan='2'>$id_path</td></tr>\n";
    echo "<tr><td colspan='2'>$file</td></tr>\n";
    $current = $data_array['current'];
    $keys =  array('title','date','creator','last_change','relation');
    foreach ($keys AS $header) {
        switch($header) {
            case 'title':               
                 $title = getcurrent($header, null);
                 echo "<tr><td colspan='2'>Title: <b>$title</b></td></tr>\n";
                 break;                     
                
            case 'date':                        
                 process_dates(getcurrent('date', 'created'),getcurrent('date', 'modified'));  
                 break;                 
            case 'user':
                if($creator || $creator_id) break; 
            case 'creator':
                $creator = getcurrent('creator', null);
                $creator_id = getcurrent('user', null);
                process_users($creator,$creator_id);  
                 break;
           
            case 'last_change':                                           
                $last_change = getSimpleKeyValue(getcurrent($header, null),"last_change");
                 if($last_change) {
                    echo "<tr><td colspan='2'>Last Change</td>\n"; 
                    echo "<td>$last_change</td></tr>\n"; 
                }
                break;              
            case 'contributor':       
                 $contributors = getSimpleKeyValue(getcurrent($header, null));
                 break;   
            case 'relation':                
                $isreferencedby = getcurrent($header,'isreferencedby');
                $references = getcurrent($header,'references');
                $media = getcurrent($header,'media');
                $firstimage = getcurrent($header,'firstimage');
                $haspart = getcurrent($header,'haspart');
                $subject = getcurrent($header,'subject');
                process_relation($isreferencedby,$references,$media,$firstimage,$haspart,$subject);
                break;
            default:

                 break;
            }

        }  
       echo "\n</table>\n";
       $current = array();
}

/*
*  @param array $ar metadata field
*  @param string $which which field  
*/
function getSimpleKeyValue($ar,$which="") {
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

function process_users($creator,$user) {
        if(empty($creator)) {
            echo "\n"; return;
         }
        echo "<tr><td>Created by:</td><td> $creator (userid: $user)</tr></td>\n";
}

function process_dates($created, $modified) {   
    $retv = "";

    if ($created) {
        $rfc_cr = date("r", $created);
        echo "<tr><td>Date created:</td><td>".$rfc_cr.
        "</td><td>$created</td></tr>\n";
        }
   
    if ($modified) {
        $rfc_mod = date("r", $modified);
        echo "<tr><td>Last modified:</td><td>" . $rfc_mod .
        "</td><td>$modified</td></tr>\n"; 
     }

}

function insertListInTable($list,$type) {
    if($list) echo "<tr><td>$type</td><td>$list</td></tr>\n";
}
function process_relation($isreferencedby,$references,$media,$firstimage,$haspart,$subject) {
  
    if(!empty($isreferencedby)) {         
        $list = create_list(array_keys($isreferencedby));
        insertListInTable($list,'Backlinks');
    }
    if(!empty($references)) {           
       $list = create_list(array_keys($references));
       insertListInTable($list,'Links');           
    }
    if(!empty($media)) {          
       $list = create_list(array_keys($media));
       insertListInTable($list,'Media');           
    }
    if(!empty($firstimage)) {
       echo "<tr><td>First Image</td><td colspan='2'>$firstimage</td></tr>";      
    }   
    if(!empty($haspart)) {      
       $list = create_list(array_keys($haspart)); 
       insertListInTable($list,'haspart');
    }  
    if(!empty($subject)) {
       $list = create_list(array_keys($subject));
       insertListInTable($list,'Subject');
    }       
 
}

function create_list($ar) {
    $list = "\n<ol>\n";
    for($i=0; $i<count($ar); $i++) {
        $list .= '<li>'. $ar[$i] . "</li>\n";
    }
     $list .= "</ol>\n";
     return $list;
}   
function getcurrent($which, $other) {
    global $current;
    if (!isset($current)) return "";
    if ($other) {
        if (isset($current[$which][$other])) {
            return $current[$which][$other];
        }
    }
    if (isset($current[$which]) && $other === null) {
        return $current[$which];
    }
    return "";
}
echo xcom_GetMetaData();