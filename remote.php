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
        );
    }

    public function getTime($a) {  
        if($a[0]) return strftime('%Y. %B %d. %A', $this->getApi()->toDate(time()));
        return $this->getApi()->toDate(time());
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
          return array("no data for $path");
    }
}