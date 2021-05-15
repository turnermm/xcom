<?php
require_once('../remote.php');

$rem = new remote_plugin_xcom();

$result = json_decode($rem->pageVersions("events:event_handlers"));

for($i=0; $i<count($result);$i++) {;
   $result[$i]->modified = date("r",$result[$i]->modified);
}
 
echo print_r($result,1) ."\n";