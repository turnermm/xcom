<?php
/**
 * DokuWiki Plugin xcom (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Myron Turner <turnermm02@shaw.ca>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_xcom extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {

       $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'handle_dokuwiki_started');
	   $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this,'handle_meta_headers'); 
   
    }

    /**
     * [Custom event handler which performs action]
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */

    public function handle_dokuwiki_started(Doku_Event &$event, $param) {
        global $JSINFO, $INFO;
        $JSINFO['pwdhide'] = $this->getLang('pwdhide');
        $JSINFO['pwdview'] = $this->getLang('pwdview');
        $JSINFO['savelocalfile'] = $this->getLang('savelocalfile');       
        $local_url = $this->getConf('local_url') ;
        if(!empty($local_url))  {
            $JSINFO['url'] = $local_url;
        }
        else   $JSINFO['url'] = DOKU_URL;     
    
        $inidir = trim($this->getConf('inidir'));
         if(!$inidir) {
               $inidir = DOKU_PLUGIN . 'xcom/scripts/';
         }             
         else {
             $inidir = rtrim($inidir,'/') . '/';
         }      
        $ini_file = $inidir . 'xcom.ini';
       
        $JSINFO['xcom_sites'] = array();
        $ini = parse_ini_file($ini_file,1);
        foreach ($ini as $name=>$site) {
            $JSINFO['xcom_sites'][$name] = array('url'=>'','user'=>'','pwd'=>'');   
            foreach($site as $item=>$val) {
                $JSINFO['xcom_sites'][$name][$item] = $val;       
            }
    }
       /* tooltips  for  function select menu */
        $JSINFO['xcom_qtitles'] = array(
             'dokuwiki.getPagelist'=>'get pages in given namespace',
            'dokuwiki.search'=>'fulltext search',
            'dokuwiki.getTitle'=>'Wiki title',
            'dokuwiki.appendPage'=>'Append text to wiki page',
            'wiki.aclCheck'=>false,
            'wiki.getPage'=>'get raw wiki text',
            'wiki.getPageVersion'=>'get wiki text for specific revision ',
            'wiki.getPageVersions'=>'available versions of a wiki page',
            'wiki.getPageInfo'=>false,
            'wiki.getPageHTML'=>'get XHTML body of wiki page',
            'wiki.putPage'=>'Save page',
            'wiki.listLinks'=>'all links in page',
            'wiki.getAllPages'=>'all wiki pages in remote wiki',
			'wiki.getBackLinks'=>'list of backlinks to selected page',
			'wiki.getRecentChanges'=>'list of recent changes since given timestamp',
            'wiki.getAttachments'=>'list media files in namespace',
            'wiki.getAttachmentInfo'=>'info about a media file',
            'plugin.acl.addAcl'=>false,
            'plugin.acl.delAcl'=>false,
            'plugin.xcom.getMedia'=>'list of all media in wiki page',
            'plugin.xcom.listNamespaces'=>'list all namespaces, or sub-namespaces of ID'
        );
        
    }

    public function handle_meta_headers(Doku_Event &$event, $param){
	    $event->data["script"][] = array (
           "type" => "text/javascript",
           "src" => DOKU_BASE."lib/plugins/xcom/scripts/xcom_latinize-cmpr.js",
            "_data" => ""
          );
 	      $event->data["script"][] = array (
           "type" => "text/javascript",
           "src" => DOKU_BASE."lib/plugins/xcom/scripts/safeFN_class-cmpr.js",
            "_data" => ""
         );
	}		
}

// vim:ts=4:sw=4:et:
