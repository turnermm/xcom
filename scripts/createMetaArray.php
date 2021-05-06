<?php
define ('PAGES', '/home/samba/html/mturner/devel/data/pages');
global $timezone, $current;

$timezone = 'UTC'; // default timezone is set to Coordinated Univeral Time. You can reset your timezone here
date_default_timezone_set($timezone);
if ($argc > 1) {
    echo $argv[1].
    "\n";
    chdir($argv[1]);
}

$realpath = realpath('.');
$prefix = preg_replace("/.*?\/data\/meta/", "", $realpath);
$prefix = ($depth = str_replace('/', ':', $prefix)) ? $depth : '';

recurse('.');

function recurse($dir) {
    global $prefix;
    static $count;
    $dh = opendir($dir);
    if (!$dh) return;
    if (!isset($count)) $count = 1;

    while (($file = readdir($dh)) !== false) {
        if ($file == '.' || $file == '..') continue;
        if (is_dir("$dir/$file")) recurse("$dir/$file");
        if (preg_match("/\.meta$/", $file)) {            
            $store_name = preg_replace('/^\./', $prefix, "$dir/$file");
            $id_name = PAGES . preg_replace("/\.meta$/","",$store_name) . '.txt';
            echo "ID NAME $id_name\n";
            if(!file_exists($id_name)) continue;
            echo "NEW FILE: $dir/$file\n";
            get_data("$dir/$file");
            echo "\n";
        }
    }

    closedir($dh);
}
function get_data($file) {
    global $current;
    $data = file_get_contents($file);
    $data_array = @unserialize(file_get_contents($file));   
    $creator =""; $creator_id="";
  
    if ($data_array === false || !is_array($data_array)) return; 
    if (!isset($data_array['current'])) return;
   
    $current = $data_array['current'];
    $keys = array_keys($data_array['current']);
 //   echo "Headers\n" . print_r($keys,1) ."\n";
    $keys =  array('title','date','creator');
    foreach ($keys AS $header) {
        switch($header) {
            case 'title':
                 echo "========= Title ======\n";
                 $title = getcurrent($header, null);
                 echo "<h2>$title</h2><br />\n";
                 break;                     
                
            case 'date':
                  // echo "========= Date ======\n";          
                 process_dates(getcurrent('date', 'created'),getcurrent('date', 'modified'));  
                 break;                 
            case 'user':
			    if($creator || $creator_id) break; 
            case 'creator':                         
              //  if($creator || $creator_id) break;  
                echo "=========Creator======\n";            
                $creator = getcurrent('creator', null);
                $creator_id = getcurrent('user', null);
                process_users($creator,$creator_id);  
                 break;

            
            case 'last_change': 
                //echo "=========Last_Change======\n";
               // $last_change = getSimpleKeyValue(getcurrent($header, null));
                break;              
            case 'contributor':
              //   echo "=========Contributor======\n";
              //   $contributors = getSimpleKeyValue(getcurrent($header, null));
                 break;   
            case 'relation': 
               // echo "=====Relation======\n";
                $isreferencedby = getcurrent($header,'isreferencedby');
                $references = getcurrent($header,'references');
                $media = getcurrent($header,'media');
                $firstimage = getcurrent($header,'firstimage');
                $haspart = getcurrent($header,'haspart');
                $subject = getcurrent($header,'subject');
              //  process_relation($isreferencedby,$references,$media,$firstimage,$haspart,$subject);
                break;
            default:

                 break;
            }

        }  
 
  echo "========END OUTPUT==================\n\n";   
    $current = array();
}


function process_users($creator,$user) {
        echo "Created by: $creator  (userid: $user)\n";
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