#!/usr/bin/php

<?php
///home/samba/html/mturner/devel/data/pages
define ('PAGES', '/home/samba/html/mturner/devel/data/pages');
echo "<pre>";
global $timezone, $current;

$timezone = 'UTC'; // default timezone is set to Coordinated Univeral Time. You can reset your timezone here , for instance "America/Chicago", "Europe/Berlin"
date_default_timezone_set($timezone);
if ($argc > 1) {
    echo $argv[1].
    "\n";
    chdir($argv[1]);
}
global $prefix;
$realpath = realpath('.');
echo "realpath=$realpath\n";
$prefix = preg_replace("/.*?\/data\/meta/", "", $realpath);
$prefix = ($depth = str_replace('/', ':', $prefix)) ? $depth : '';

echo "prefix = $prefix\n";

recurse('.');


function get_data($file) {
    global $current;
    $data = file_get_contents($file);
    $data_array = @unserialize(file_get_contents($file));
   // echo print_r($data_array); return;
    $creator =""; $creator_id="";
  
    if ($data_array === false || !is_array($data_array)) return; 
    if (!isset($data_array['current'])) return;
  
    $current = $data_array['current'];
    echo "---------START OUTPUT--------------------\n\n";   

    $keys = array_keys($data_array['current']);
    echo "Headers\n" . print_r($keys,1) ."\n";

    foreach ($keys AS $header) {
        switch($header) {
            case 'title':
                 echo "========= Title ======\n";
                 $title = getcurrent($header, null);
                 echo "$title\n";
                 break;                     
                
            case 'date':
                   echo "========= Date ======\n";          
                 process_dates(getcurrent('date', 'created'),getcurrent('date', 'modified'));  
                 break;                 
            case 'user':
            case 'creator': 
                if($creator || $creator_id) break;             
                $creator = getcurrent('creator', null);
                $creator_id = getcurrent('user', null);
                process_users($creator,$creator_id);  
                 break;
          //  case  'plugin_move':
           // case 'description':  break;
           // case 'internal':  break;     
            
            case 'last_change': 
                echo "=========Last_Change======\n";
                $last_change = processLastChange(getcurrent($header, null));
                break;              
            case 'contributor':
                 echo "=========Contributor======\n";
                 break;             
            case 'title':     
                echo "=========Title======\n";
                 break;                
            case 'relation': 
              //  echo "=====Relation======\n";
                $isreferencedby = getcurrent($header,'isreferencedby');
                $references = getcurrent($header,'references');
                $media = getcurrent($header,'media');
                $firstimage = getcurrent($header,'firstimage');
                $haspart = getcurrent($header,'haspart');
                $subject = getcurrent($header,'subject');
                process_relation($isreferencedby,$references,$media,$firstimage,$haspart,$subject);
                break;
            default:
               //  echo "----> $header START<----- \n";
                // echo print_r($current[$header],1) . "\n";
                // echo "----> $header END <----- \n";
                 break;
            }

        }  
    /*
    
    $creator = getcurrent('creator', null);
    $creator_id = getcurrent('user', null);
    echo "Created by: $creator  (userid: $creator_id)\n";

    $contributors = getcurrent('contributor', null);
    if (is_array($contributors)) {
        echo "Contributors:\n";
        print_key_values($contributors);
    }
    $last_change = getcurrent('last_change', null);
    if (is_array($last_change)) {
        echo "Last Change: \n";
        print_key_values($last_change);
    }

    $relation = isset($data_array['current']['relation']) ? $data_array['current']['relation'] : array();
    if (!empty($relation) && !empty($relation['references'])) {
        echo "Internal links:\n";
        print_key_values($relation['references'], true);
    } 
    */
  echo "========END OUTPUT==================\n\n";   
    $current = array();
}

function processLastChange($ar) {
    foreach ($ar As $key=>$val) {
        echo "$key: $val\n";
    }
}
function process_relation($isreferencedby,$references,$media,$firstimage,$haspart,$subject) {
   
    if(!empty($isreferencedby)) {
        echo "--Backlinks--\n";    
        $list = create_list(array_keys($isreferencedby));
        echo $list;
    }
    if(!empty($references)) {
       echo "--Links--\n";      
       $list = create_list(array_keys($references));
       echo $list;       
    }
    if(!empty($media)) {
       echo "--Media--\n";      
       $list = create_list(array_keys($media));
        echo $list;        
    }
    if(!empty($firstimage)) {
       echo "--First Image--\n";
       echo print_r($firstimage,1) . "\n";
    }   
    if(!empty($haspart)) {
       echo "-- haspart --\n";
       echo print_r($haspart,1) . "\n";
    }  
    if(!empty($subject)) {
       echo "-- Subject --\n";
       echo print_r($subject,1) . "\n";
    }       
    
}

function create_list($ar) {
    $list = "<ol>\n";
    for($i=0; $i<count($ar); $i++) {
        $list .= '<li>'. $ar[$i] . "</li>\n";
    }
     $list .= "</ol>\n";
     return $list;
}   

function process_dates($created, $modified) {   
    if ($created) {
        $rfc_cr = date("r", $created);
        echo "Date created: ".$rfc_cr.
        " (".$created.
        ")\n";
    }
   
    if ($modified) {
        $rfc_mod = date("r", $modified);
        echo "Last modified: ".
        "$rfc_mod  (".$modified.
        ")\n";
    }
}

function process_users($creator,$user) {
        echo "Created by: $creator  (userid: $user)\n";
}
function print_key_values($ar, $keys_only = false) {
    foreach($ar as $key => $val) {
        if ($keys_only) {
            echo "\t$key\n";
        } else echo "\t$key => $val\n";
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
            echo "storage name = $store_name\n";
           // $store_name = str_replace('/', ':', $store_name);
           // echo "storage name = $store_name\n";
            echo "($count) $dir/$file\n";
            $count++;
            echo "NEW FILE: $dir/$file\n";
            get_data("$dir/$file");
            echo "\n";
        }
    }

    closedir($dh);
}
echo "</pre>";
